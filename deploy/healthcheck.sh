#!/bin/bash
# healthcheck.sh — read-only production/staging health probe.
#
# Prints a single-line JSON document and exits non-zero when a CRITICAL check
# fails. Consumed by .github/workflows/nightly.yml (and safe to run by hand).
#
# Usage:
#   bash ~/deploy/healthcheck.sh            # JSON to stdout
#   bash ~/deploy/healthcheck.sh --human    # readable output
#   bash ~/deploy/healthcheck.sh --prometheus  # Prometheus text format
set -uo pipefail
export HOME=/home/gabozvqd
BASE=/home/gabozvqd
DEPLOY="$BASE/deploy"
STAGING_URL="https://wp.design.gabochie.com"
LIVE_URL="https://design.gabochie.com"
MAX_BACKUP_AGE_HOURS=36
MIN_FREE_PCT=10

HUMAN=0
PROMETHEUS=0
case "${1:-}" in
  --human) HUMAN=1 ;;
  --prometheus) PROMETHEUS=1 ;;
esac

CRITICAL=0
WARN=0
TOTAL=0
FIELDS=""
PROM_CHECKS=""
PROM_VALUES=""

add() { # <key> <value> <critical|warn|ok>
  local key="$1" value="$2" level="${3:-ok}"
  TOTAL=$((TOTAL + 1))
  case "$level" in
    critical) CRITICAL=$((CRITICAL + 1)) ;;
    warn) WARN=$((WARN + 1)) ;;
  esac
  [ -n "$FIELDS" ] && FIELDS="$FIELDS,"
  FIELDS="$FIELDS\"$key\":\"$value\",\"${key}_level\":\"$level\""
  if [ "$PROMETHEUS" = "1" ]; then
    [ -n "$PROM_CHECKS" ] && PROM_CHECKS="$PROM_CHECKS
"
    PROM_CHECKS="$PROM_CHECKS"$(printf 'gabochie_health_check_level{check="%s",level="%s"} 1' "$key" "$level")
    case "$value" in
      ''|*[!0-9.]*) ;;
      *)
        [ -n "$PROM_VALUES" ] && PROM_VALUES="$PROM_VALUES
"
        PROM_VALUES="$PROM_VALUES"$(printf 'gabochie_health_value{check="%s"} %s' "$key" "$value")
        ;;
    esac
  fi
  if [ "$HUMAN" = "1" ]; then printf '%-6s %-28s %s\n' "[$level]" "$key" "$value"; fi
}

code() { curl -s -o /dev/null -w '%{http_code}' -L --max-time 30 "$1" 2>/dev/null || echo 000; }
ttfb() { curl -s -o /dev/null -w '%{time_starttransfer}' -L --max-time 30 "$1" 2>/dev/null || echo 0; }

# --- HTTP ---
for pair in "live:$LIVE_URL" "staging:$STAGING_URL"; do
  name="${pair%%:*}"; url="${pair#*:}"
  c="$(code "$url/")"
  if [ "$c" = "200" ]; then add "${name}_http" "$c" ok; else add "${name}_http" "$c" critical; fi
  t="$(ttfb "$url/")"
  if awk "BEGIN { exit !($t > 0 && $t < 5) }"; then add "${name}_ttfb_s" "$t" ok
  elif awk "BEGIN { exit !($t >= 5) }"; then add "${name}_ttfb_s" "$t" warn
  else add "${name}_ttfb_s" "unknown" warn; fi
done

# --- deploy token (QA gate state) ---
if [ -f "$DEPLOY/qa-state.txt" ]; then
  qa_status="$(sed -n 's/^STATUS=//p' "$DEPLOY/qa-state.txt" | head -1)"
  qa_commit="$(sed -n 's/^COMMIT=//p' "$DEPLOY/qa-state.txt" | head -1)"
  qa_time="$(sed -n 's/^TIME=//p' "$DEPLOY/qa-state.txt" | head -1)"
  if [ "$qa_status" = "pass" ]; then add qa_status "$qa_status @ ${qa_commit:0:8} ($qa_time)" ok
  elif [ "$qa_status" = "needs-qa" ] || [ "$qa_status" = "running" ]; then add qa_status "$qa_status" warn
  else add qa_status "${qa_status:-unknown}" critical; fi
else
  add qa_status "missing" critical
fi

# --- auto-promote timer ---
if [ -f "$DEPLOY/.auto-promote" ]; then
  ap_commit="$(sed -n 's/^COMMIT=//p' "$DEPLOY/.auto-promote" | head -1)"
  ap_pid="$(sed -n 's/^PID=//p' "$DEPLOY/.auto-promote" | head -1)"
  if [ -n "$ap_pid" ] && kill -0 "$ap_pid" 2>/dev/null; then
    add auto_promote "pending ${ap_commit:0:8} (pid $ap_pid)" warn
  else
    add auto_promote "stale state file, timer dead" warn
  fi
else
  add auto_promote "none pending" ok
fi

# --- deploy locks (stuck deploys) ---
for lk in deploy-staging.lock deploy-live.lock promote.lock; do
  k="${lk%.lock}_lock"
  if [ -f "$DEPLOY/$lk" ]; then add "$k" "present" warn; else add "$k" "absent" ok; fi
done

# --- last live deploy result ---
if [ -f "$DEPLOY/deploy-live.log" ]; then
  last_ok="$(grep -c 'deploy ok ' "$DEPLOY/deploy-live.log" 2>/dev/null || echo 0)"
  last_bad="$(grep -cE 'FAIL:|BLOCKED' "$DEPLOY/deploy-live.log" 2>/dev/null || echo 0)"
  add deploy_live_ok_count "$last_ok" ok
  if [ "$last_bad" -gt 0 ] && [ "$last_ok" -eq 0 ]; then add deploy_live_fail_count "$last_bad" critical
  else add deploy_live_fail_count "$last_bad" ok; fi
else
  add deploy_live_log "missing" critical
fi

# --- backups ---
NEWEST="$(ls -1t "$DEPLOY"/backups/live-db-*.sql.gz 2>/dev/null | head -1)"
if [ -n "$NEWEST" ]; then
  AGE_H=$(( ( $(date +%s) - $(stat -c %Y "$NEWEST") ) / 3600 ))
  SIZE=$(stat -c %s "$NEWEST")
  add backup_age_hours "$AGE_H" "$([ "$AGE_H" -le "$MAX_BACKUP_AGE_HOURS" ] && echo ok || echo critical)"
  add backup_newest_size_bytes "$SIZE" "$([ "$SIZE" -gt 100000 ] && echo ok || echo critical)"
else
  add backup_age_hours "none" critical
fi

# --- disk ---
USED_PCT=$(df -P "$DEPLOY" | awk 'NR==2 {gsub("%","",$5); print $5}')
if [ -n "$USED_PCT" ]; then
  FREE=$((100 - USED_PCT))
  add disk_free_pct "$FREE" "$([ "$FREE" -ge "$MIN_FREE_PCT" ] && echo ok || echo critical)"
else
  add disk_free_pct "unknown" warn
fi

# --- secret file permissions (must not be world-readable) ---
for f in deploy-secret.env webhook-secret.env live.env staging.env; do
  k="perm_$(echo "$f" | tr '.' '_')"
  if [ -f "$DEPLOY/$f" ]; then
    perms=$(stat -c %a "$DEPLOY/$f")
    case "$perms" in
      600|400) add "$k" "$perms" ok ;;
      *) add "$k" "$perms (expected 600)" critical ;;
    esac
  else
    add "$k" "missing" warn
  fi
done

# --- code state ---
if [ -d "$BASE/gabochie-deploy/.git" ]; then
  behind="$(git -C "$BASE/gabochie-deploy" rev-list --count HEAD..origin/main 2>/dev/null || echo unknown)"
  add live_clone_head "$(git -C "$BASE/gabochie-deploy" rev-parse --short HEAD 2>/dev/null || echo unknown)" ok
  add live_clone_behind_main "$behind" ok
else
  add live_clone_head "missing" critical
fi

# --- WordPress core ---
export PATH=/usr/local/sbin:/usr/local/bin:/usr/bin:/bin
CORE="$(wp core version --path="$BASE/public_html" --allow-root 2>/dev/null | tail -1)"
add wp_core_version "${CORE:-unknown}" ok

if [ "$HUMAN" = "1" ]; then
  printf '\ncritical=%s warn=%s\n' "$CRITICAL" "$WARN"
elif [ "$PROMETHEUS" = "1" ]; then
  UP=1; [ "$CRITICAL" -eq 0 ] || UP=0
  OK=$((TOTAL - CRITICAL - WARN))
  cat <<EOF
# HELP gabochie_health_up 1 when no critical checks failed, 0 otherwise.
# TYPE gabochie_health_up gauge
gabochie_health_up $UP
# HELP gabochie_health_critical Number of failed critical checks.
# TYPE gabochie_health_critical gauge
gabochie_health_critical $CRITICAL
# HELP gabochie_health_warn Number of warning checks.
# TYPE gabochie_health_warn gauge
gabochie_health_warn $WARN
# HELP gabochie_health_ok Number of passing checks.
# TYPE gabochie_health_ok gauge
gabochie_health_ok $OK
# HELP gabochie_health_probe_timestamp_seconds Unix time of this probe.
# TYPE gabochie_health_probe_timestamp_seconds gauge
gabochie_health_probe_timestamp_seconds $(date +%s)
$PROM_CHECKS
$PROM_VALUES
EOF
else
  printf '{"generated":"%s","critical":%s,"warn":%s,%s}\n' \
    "$(date -u '+%Y-%m-%dT%H:%M:%SZ')" "$CRITICAL" "$WARN" "$FIELDS"
fi

[ "$CRITICAL" -eq 0 ] || exit 1
exit 0
