#!/bin/bash
# monitor.sh — continuous health monitoring, metric history, and alerting.
#
# Run from cPanel cron every 5 minutes (see deploy/README.md). Each tick:
#   1. runs healthcheck.sh (read-only probe),
#   2. appends a sample to ~/deploy/metrics/health.jsonl (bounded history),
#   3. writes ~/deploy/metrics/health.prom (Prometheus text) and status.json,
#   4. publishes a sanitised ~/wp.design.gabochie.com/health-status.json,
#   5. emails + posts a webhook on NEW criticals, on recovery, and as a
#      repeating reminder while a critical persists (default: every 6 hours),
#   6. optionally pushes metrics to an external TSDB (see metrics.env).
#
# Usage:
#   bash ~/deploy/monitor.sh            # cron mode (quiet, logs to monitor.log)
#   bash ~/deploy/monitor.sh --status   # print current state, change nothing
#
# Optional server-only config files (mode 600, never committed):
#   ~/deploy/alert.env    ALERT_WEBHOOK_URL=...        (Slack-compatible)
#   ~/deploy/metrics.env  METRICS_PUSH_URL=...
#                         METRICS_PUSH_FORMAT=graphite|influx|json
#                         METRICS_PUSH_AUTH="Authorization: Basic ..."
set -uo pipefail

export HOME=/home/gabozvqd
export PATH=/usr/local/sbin:/usr/local/bin:/usr/bin:/bin

BASE=/home/gabozvqd
DIR="$BASE/deploy"
METRICS="$DIR/metrics"
HISTORY="$METRICS/health.jsonl"
STATE="$METRICS/state.json"
PROM="$METRICS/health.prom"
STATUS="$METRICS/status.json"
DECISION="$METRICS/decision.json"
LOCK="$DIR/monitor.lock"
LOG="$DIR/monitor.log"
STAGING_DOCROOT="$BASE/wp.design.gabochie.com"
PUBLIC_STATUS="$STAGING_DOCROOT/health-status.json"

ALERT_REPEAT_SECONDS="${ALERT_REPEAT_SECONDS:-21600}"
HISTORY_MAX_BYTES="${HISTORY_MAX_BYTES:-5242880}"
HISTORY_KEEP_LINES="${HISTORY_KEEP_LINES:-20000}"
PROBE_TIMEOUT="${PROBE_TIMEOUT:-90}"

mkdir -p "$METRICS" 2>/dev/null || exit 1

if [ -f "$LOG" ] && [ "$(wc -c < "$LOG" 2>/dev/null || echo 0)" -gt 1048576 ]; then
  tail -n 500 "$LOG" > "$LOG.1" 2>/dev/null
  : > "$LOG"
fi

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" >> "$LOG"; }

if [ "${1:-}" = "--status" ]; then
  if [ -f "$STATUS" ]; then
    python3 -c 'import json,sys; d=json.load(open(sys.argv[1])); print(json.dumps(d, indent=2))' "$STATUS"
  else
    echo "no status yet: run monitor.sh once"
  fi
  exit 0
fi

if ! mkdir "$LOCK" 2>/dev/null; then
  exit 0
fi
trap 'rmdir "$LOCK" 2>/dev/null' EXIT

ALERT_WEBHOOK_URL="${ALERT_WEBHOOK_URL:-}"
METRICS_PUSH_URL="${METRICS_PUSH_URL:-}"
METRICS_PUSH_FORMAT="${METRICS_PUSH_FORMAT:-graphite}"
METRICS_PUSH_AUTH="${METRICS_PUSH_AUTH:-}"
# shellcheck disable=SC1090
[ -f "$DIR/alert.env" ] && . "$DIR/alert.env" 2>/dev/null || true
# shellcheck disable=SC1090
[ -f "$DIR/metrics.env" ] && . "$DIR/metrics.env" 2>/dev/null || true

# Load the shared email helper without running anything else.
# shellcheck disable=SC1091
[ -f "$DIR/deploy-common.sh" ] && . "$DIR/deploy-common.sh" >/dev/null 2>&1 || true

if [ -f "$HISTORY" ] && [ "$(wc -c < "$HISTORY" 2>/dev/null || echo 0)" -gt "$HISTORY_MAX_BYTES" ]; then
  tail -n "$HISTORY_KEEP_LINES" "$HISTORY" > "$HISTORY.trim" 2>/dev/null && mv -f "$HISTORY.trim" "$HISTORY"
fi

NOW="$(date +%s)"
ISO="$(date -u '+%Y-%m-%dT%H:%M:%SZ')"
SAMPLE_JSON=""
if command -v timeout >/dev/null 2>&1; then
  SAMPLE_JSON="$(timeout "$PROBE_TIMEOUT" bash "$DIR/healthcheck.sh" 2>/dev/null)" || true
else
  SAMPLE_JSON="$(bash "$DIR/healthcheck.sh" 2>/dev/null)" || true
fi
if [ -z "$SAMPLE_JSON" ]; then
  SAMPLE_JSON="{\"generated\":\"$ISO\",\"critical\":1,\"warn\":0,\"probe\":\"failed\",\"probe_level\":\"critical\"}"
  log "probe failed or timed out; recording synthetic critical sample"
fi

export SAMPLE_JSON NOW ISO STATE_PATH="$STATE" STATUS_PATH="$STATUS" PROM_PATH="$PROM" \
  HISTORY_PATH="$HISTORY" DECISION_PATH="$DECISION" ALERT_REPEAT="$ALERT_REPEAT_SECONDS"

python3 - <<'PY'
import json, os, re

sample = json.loads(os.environ["SAMPLE_JSON"])
now = int(os.environ["NOW"])
iso = os.environ["ISO"]
state_path = os.environ["STATE_PATH"]
status_path = os.environ["STATUS_PATH"]
prom_path = os.environ["PROM_PATH"]
history_path = os.environ["HISTORY_PATH"]
decision_path = os.environ["DECISION_PATH"]
repeat = int(os.environ["ALERT_REPEAT"])

def write_atomic(path, text):
    tmp = path + ".tmp"
    with open(tmp, "w") as f:
        f.write(text)
    os.replace(tmp, path)

failed = sorted(k[:-6] for k, v in sample.items()
                if k.endswith("_level") and v == "critical")
warned = sorted(k[:-6] for k, v in sample.items()
                if k.endswith("_level") and v == "warn")
try:
    critical = int(sample.get("critical", len(failed)))
except (TypeError, ValueError):
    critical = len(failed)
try:
    warn = int(sample.get("warn", len(warned)))
except (TypeError, ValueError):
    warn = len(warned)
status = "critical" if critical > 0 else ("warn" if warn > 0 else "ok")

sample_line = {
    "ts": now, "iso": iso, "status": status,
    "critical": critical, "warn": warn,
    "failed": failed, "warned": warned,
}
with open(history_path, "a") as f:
    f.write(json.dumps(sample_line, separators=(",", ":")) + "\n")

def availability(seconds):
    cutoff = now - seconds
    total = ok = 0
    try:
        with open(history_path) as f:
            for line in f:
                try:
                    row = json.loads(line)
                except ValueError:
                    continue
                if int(row.get("ts", 0)) < cutoff:
                    continue
                total += 1
                if int(row.get("critical", 0)) == 0:
                    ok += 1
    except FileNotFoundError:
        return None
    return round(100.0 * ok / total, 3) if total else None

a24 = availability(86400)
a7d = availability(604800)

prev = {}
try:
    with open(state_path) as f:
        prev = json.load(f)
except (FileNotFoundError, ValueError):
    prev = {}
prev_failed = set(prev.get("failed", []))
last_alert = int(prev.get("last_alert_epoch", 0) or 0)

new = sorted(set(failed) - prev_failed)
recovered = sorted(prev_failed - set(failed))
should_alert = bool(new) or (bool(failed) and now - last_alert >= repeat)
should_recover = bool(prev_failed) and not failed

state = {
    "last_run": iso,
    "last_run_epoch": now,
    "status": status,
    "critical": critical,
    "warn": warn,
    "failed": failed,
    "warned": warned,
    "new": new,
    "recovered": recovered,
    "availability_24h": a24,
    "availability_7d": a7d,
    "last_alert_epoch": now if should_alert else last_alert,
    "last_alert_iso": iso if should_alert else prev.get("last_alert_iso", ""),
}
write_atomic(state_path, json.dumps(state, indent=2) + "\n")

public = {
    "generated": iso,
    "site": "gabochiedesign",
    "status": status,
    "critical": critical,
    "warn": warn,
    "failed": failed,
    "availability_24h": a24,
    "availability_7d": a7d,
}
write_atomic(status_path, json.dumps(public, indent=2) + "\n")

up = 1 if critical == 0 else 0
ok = sum(1 for k, v in sample.items() if k.endswith("_level") and v == "ok")
lines = [
    "# HELP gabochie_health_up 1 when no critical checks failed, 0 otherwise.",
    "# TYPE gabochie_health_up gauge",
    f"gabochie_health_up {up}",
    "# HELP gabochie_health_critical Number of failed critical checks.",
    "# TYPE gabochie_health_critical gauge",
    f"gabochie_health_critical {critical}",
    "# HELP gabochie_health_warn Number of warning checks.",
    "# TYPE gabochie_health_warn gauge",
    f"gabochie_health_warn {warn}",
    "# HELP gabochie_health_ok Number of passing checks.",
    "# TYPE gabochie_health_ok gauge",
    f"gabochie_health_ok {ok}",
    "# HELP gabochie_health_probe_timestamp_seconds Unix time of this probe.",
    "# TYPE gabochie_health_probe_timestamp_seconds gauge",
    f"gabochie_health_probe_timestamp_seconds {now}",
]
for key, value in sample.items():
    if key.endswith("_level"):
        check = key[:-6]
        lines.append(f'gabochie_health_check_level{{check="{check}",level="{value}"}} 1')
for key, value in sample.items():
    if key.endswith("_level") or key in ("generated", "critical", "warn"):
        continue
    if isinstance(value, (int, float)) or (isinstance(value, str) and re.fullmatch(r"[0-9]+(\.[0-9]+)?", value)):
        lines.append(f'gabochie_health_value{{check="{key}"}} {value}')
lines.append("# HELP gabochie_health_availability Availability over the window, percent.")
lines.append("# TYPE gabochie_health_availability gauge")
for name, value in (("24h", a24), ("7d", a7d)):
    if value is not None:
        lines.append(f'gabochie_health_availability{{window="{name}"}} {value}')
if a24 is not None:
    lines.append("# HELP gabochie_health_slo_24h 1 when 24h availability meets the 99.5% target.")
    lines.append("# TYPE gabochie_health_slo_24h gauge")
    lines.append(f"gabochie_health_slo_24h {1 if a24 >= 99.5 else 0}")
write_atomic(prom_path, "\n".join(lines) + "\n")

decision = {
    "status": status,
    "critical": critical,
    "warn": warn,
    "failed": ",".join(failed),
    "warned": ",".join(warned),
    "new": ",".join(new),
    "recovered": ",".join(recovered),
    "availability_24h": "" if a24 is None else a24,
    "availability_7d": "" if a7d is None else a7d,
    "should_alert": 1 if should_alert else 0,
    "should_recover": 1 if should_recover else 0,
}
write_atomic(decision_path, json.dumps(decision, separators=(",", ":")) + "\n")
PY

if [ ! -f "$DECISION" ]; then
  log "decision file missing; probe processing failed"
  exit 1
fi

eval "$(python3 - "$DECISION" <<'PY'
import json, shlex, sys
d = json.load(open(sys.argv[1]))
for key, value in d.items():
    print(f"{key.upper()}={shlex.quote(str(value))}")
PY
)"

if [ -d "$STAGING_DOCROOT" ] && [ -f "$STATUS" ]; then
  cp -f "$STATUS" "$PUBLIC_STATUS.tmp" 2>/dev/null && \
    chmod 644 "$PUBLIC_STATUS.tmp" 2>/dev/null && \
    mv -f "$PUBLIC_STATUS.tmp" "$PUBLIC_STATUS" 2>/dev/null || true
fi

build_body() {
  cat <<EOF
Gabochie Design health monitor

$1

Status: ${STATUS} (critical=${CRITICAL}, warn=${WARN})
Failing: ${FAILED:-none}
Warnings: ${WARNED:-none}
New since last sample: ${NEW:-none}
Recovered: ${RECOVERED:-none}
Availability 24h: ${AVAILABILITY_24H:-n/a}%
Availability 7d: ${AVAILABILITY_7D:-n/a}%

Host: server178.web-hosting.com
Live: https://design.gabochie.com
Staging: https://wp.design.gabochie.com
Details: bash ~/deploy/healthcheck.sh --human
EOF
}

send_webhook() {
  local text="$1"
  [ -n "$ALERT_WEBHOOK_URL" ] || return 0
  local payload
  payload="$(MSG="$text" python3 -c 'import json, os; print(json.dumps({"text": os.environ["MSG"]}))' 2>/dev/null)" || return 0
  curl -sS --max-time 10 -X POST -H 'Content-Type: application/json' \
    -d "$payload" "$ALERT_WEBHOOK_URL" >/dev/null 2>&1 || log "webhook post failed"
}

if [ "$SHOULD_ALERT" = "1" ]; then
  subject="[Gabochie OPS] CRITICAL: ${CRITICAL} failing check(s) — ${FAILED:-unknown}"
  body="$(build_body "New critical condition detected.")"
  if declare -F _deploy_send_email >/dev/null 2>&1; then
    _deploy_send_email "$subject" "$body"
  fi
  send_webhook "🚨 $subject
${body}"
  log "ALERT sent: ${FAILED:-unknown} (critical=$CRITICAL warn=$WARN)"
elif [ "$SHOULD_RECOVER" = "1" ]; then
  subject="[Gabochie OPS] RECOVERED — health is ${STATUS}"
  body="$(build_body "All previously failing critical checks have recovered.")"
  if declare -F _deploy_send_email >/dev/null 2>&1; then
    _deploy_send_email "$subject" "$body"
  fi
  send_webhook "✅ $subject
${body}"
  log "RECOVERY sent: ${RECOVERED:-unknown} (status=$STATUS)"
fi

push_metrics() {
  [ -n "$METRICS_PUSH_URL" ] || return 0
  [ -f "$STATUS" ] || return 0
  local -a hdr=(-H "Content-Type: text/plain")
  [ -n "$METRICS_PUSH_AUTH" ] && hdr+=(-H "$METRICS_PUSH_AUTH")
  local data=""
  case "$METRICS_PUSH_FORMAT" in
    graphite)
      data="$(STATUS_PATH="$STATUS" PY_NOW="$NOW" python3 - <<'PY'
import json, os
d = json.load(open(os.environ["STATUS_PATH"]))
ts = os.environ["PY_NOW"]
status_id = {"ok": 0, "warn": 1, "critical": 2}.get(d.get("status"), 3)
print(f"gabochie.health.critical {d.get('critical', 0)} {ts}")
print(f"gabochie.health.warn {d.get('warn', 0)} {ts}")
print(f"gabochie.health.status {status_id} {ts}")
if d.get("availability_24h") is not None:
    print(f"gabochie.health.availability_24h {d['availability_24h']} {ts}")
if d.get("availability_7d") is not None:
    print(f"gabochie.health.availability_7d {d['availability_7d']} {ts}")
PY
)"
      ;;
    influx)
      data="$(STATUS_PATH="$STATUS" PY_NOW="$NOW" python3 - <<'PY'
import json, os
d = json.load(open(os.environ["STATUS_PATH"]))
ns = int(os.environ["PY_NOW"]) * 1_000_000_000
status_id = {"ok": 0, "warn": 1, "critical": 2}.get(d.get("status"), 3)
fields = f"critical={d.get('critical', 0)},warn={d.get('warn', 0)},status_id={status_id}"
if d.get("availability_24h") is not None:
    fields += f",availability_24h={d['availability_24h']}"
print(f"health,host=gabochiedesign {fields} {ns}")
PY
)"
      ;;
    json|*)
      data="$(cat "$STATUS")"
      hdr=(-H "Content-Type: application/json")
      [ -n "$METRICS_PUSH_AUTH" ] && hdr+=(-H "$METRICS_PUSH_AUTH")
      ;;
  esac
  [ -n "$data" ] || return 0
  printf '%s' "$data" | curl -sS --max-time 10 "${hdr[@]}" --data-binary @- "$METRICS_PUSH_URL" >/dev/null 2>&1 \
    || log "metrics push failed ($METRICS_PUSH_FORMAT)"
}

push_metrics

if [ -n "$FAILED" ]; then
  log "status=$STATUS critical=$CRITICAL warn=$WARN failing=$FAILED"
else
  log "status=$STATUS critical=$CRITICAL warn=$WARN"
fi
exit 0
