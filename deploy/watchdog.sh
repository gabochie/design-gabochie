#!/bin/bash
# watchdog.sh - self-healing fallback for the gabochie-design deploy pipeline.
#
# WHY THIS EXISTS
#   The pipeline's only trigger is the GitHub webhook -> ~/public_html/webhook.php.
#   cPanel's PHP exec() backgrounding is unreliable here: it has repeatedly killed
#   the 15-minute auto-promote timer mid-flight (see AGENTS.md, many "timer DIED
#   again" entries). When that happens a QA-passed commit strands on staging and
#   nothing retries. Run from cPanel cron every 5 minutes, this repairs both cases.
#
# WHAT IT DOES
#   1. Fires a due auto-promotion. The deadline lives in the state file (FIRE_AT),
#      so a promotion still happens when cPanel kills the background timer. It
#      never re-arms a timer, which previously restarted the 15-minute window on
#      every 5-minute tick and stranded releases indefinitely.
#   2. Re-runs deploy-staging.sh when origin/main has moved past the deployed,
#      QA-passed commit - which re-runs the QA gate and sets a fresh deadline.
#
# WHAT IT WILL NEVER DO
#   - It never writes to live (~/public_html). Live only moves via deploy-live.sh,
#     which stays fail-closed on the staging QA token and only runs on a v*/auto-*
#     tag push from GitHub.
#   - It only pushes the promotion tag once the QA-passed deadline has passed,
#     using the exact commit recorded in the state file.
#   - It never re-deploys a commit that is already deployed and QA-passed, and a
#     10-minute brake (WATCHDOG_MIN_INTERVAL) keeps a webhook/watchdog race from
#     running the QA suite twice.
#
# Usage:  bash ~/deploy/watchdog.sh            # cron mode: silent, logs to watchdog.log
#         bash ~/deploy/watchdog.sh --status   # report what it would do, change nothing

set -uo pipefail

export HOME=/home/gabozvqd
export PATH="$PATH:/usr/local/bin:/opt/cpanel/ea-php82/root/usr/bin"

BASE=/home/gabozvqd
DIR="$BASE/deploy"
CLONE="$BASE/gabochie-deploy-staging"
LOG="$DIR/watchdog.log"
LOCK="$DIR/watchdog.lock"
STAGING_LOG="$DIR/deploy-staging.log"
MIN_INTERVAL="${WATCHDOG_MIN_INTERVAL:-600}"

STATUS_ONLY=0
if [ "${1:-}" = "--status" ]; then STATUS_ONLY=1; fi

mkdir -p "$DIR" 2>/dev/null

if [ -f "$LOG" ] && [ "$(wc -c < "$LOG" 2>/dev/null || echo 0)" -gt 1048576 ]; then
  tail -n 500 "$LOG" > "$LOG.1" 2>/dev/null
  : > "$LOG"
fi

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" >> "$LOG"; }

if [ -f "$LOCK" ]; then exit 0; fi
touch "$LOCK" 2>/dev/null || exit 0
trap 'rm -f "$LOCK"' EXIT

# Proof-of-life for cron monitoring: epoch seconds of the current run.
date +%s > "$DIR/watchdog.lastrun" 2>/dev/null

if ! source "$DIR/deploy-common.sh" 2>/dev/null; then
  log "FATAL: cannot source $DIR/deploy-common.sh"
  exit 1
fi

cd "$CLONE" 2>/dev/null || { log "FATAL: staging clone missing at $CLONE"; exit 1; }
git fetch origin main --force --quiet 2>/dev/null

TIP="$(git rev-parse origin/main 2>/dev/null)"
if [ -z "$TIP" ]; then log "FATAL: cannot resolve origin/main"; exit 1; fi
TIP_SHORT="${TIP:0:8}"

QA_COMMIT="$(sed -n 's/^COMMIT=//p' "$QA_STATE" 2>/dev/null)"
QA_STATUS="$(sed -n 's/^STATUS=//p' "$QA_STATE" 2>/dev/null)"

TIMER_STATE=absent
timer_pid=""
timer_commit=""
timer_tag=""
if [ -f "$AUTO_PROMOTE_STATE" ]; then
  TIMER_STATE=dead
  timer_pid="$(sed -n 's/^PID=//p' "$AUTO_PROMOTE_STATE" 2>/dev/null)"
  timer_commit="$(sed -n 's/^COMMIT=//p' "$AUTO_PROMOTE_STATE" 2>/dev/null)"
  timer_tag="$(sed -n 's/^TAG=//p' "$AUTO_PROMOTE_STATE" 2>/dev/null)"
  if [ -n "$timer_pid" ] && kill -0 "$timer_pid" 2>/dev/null; then
    TIMER_STATE=running
  fi
fi

# Heartbeat: at most one line every 30 minutes, so watchdog.log proves cron is firing
# without filling up with no-op ticks.
now="$(date +%s)"
last_beat=0
if [ -f "$DIR/watchdog.heartbeat" ]; then last_beat="$(cat "$DIR/watchdog.heartbeat" 2>/dev/null || echo 0)"; fi
if [ $(( now - last_beat )) -ge 1800 ]; then
  echo "$now" > "$DIR/watchdog.heartbeat" 2>/dev/null
  log "watchdog: alive (main=$TIP_SHORT deployed=${QA_COMMIT:0:8} qa=${QA_STATUS:-none} timer=$TIMER_STATE)"
fi

promote_if_due() {
  [ -f "$AUTO_PROMOTE_STATE" ] || return 0

  if [ "$timer_commit" != "$TIP" ]; then
    log "auto-promote: stale state for ${timer_commit:0:8}, main is now $TIP_SHORT - clearing (staging catch-up will re-arm)"
    rm -f "$AUTO_PROMOTE_STATE"
    return 0
  fi

  if [ -f "$SKIP_LIVE_FILE" ]; then
    log "auto-promote: skip-live is pending for ${timer_commit:0:8} - leaving it for a human"
    return 0
  fi

  local fire_at remaining
  fire_at="$(_ap_get FIRE_AT)"
  if [ -n "$fire_at" ]; then
    remaining=$(( fire_at - $(date +%s) ))
    if [ "$remaining" -gt 0 ]; then
      if [ "$TIMER_STATE" != "running" ]; then
        log "auto-promote: timer process is ${TIMER_STATE} but deadline is ${remaining}s away - cron will fire it"
      fi
      return 0
    fi
    log "auto-promote: deadline passed for ${timer_commit:0:8} (tag=${timer_tag:-none}, timer=${TIMER_STATE}) - firing"
  else
    log "auto-promote: no FIRE_AT recorded for ${timer_commit:0:8} - firing now (legacy state)"
  fi

  auto_promote_tick >> "$LOG" 2>&1
  log "auto-promote: tick exit=$?"
}

staging_age() {
  local mtime now
  mtime="$(stat -c %Y "$STAGING_LOG" 2>/dev/null || echo 0)"
  now="$(date +%s)"
  echo $(( now - mtime ))
}

staging_catchup() {
  [ "$QA_COMMIT" != "$TIP" ] || return 0

  local age
  age="$(staging_age)"
  if [ "$age" -lt "$MIN_INTERVAL" ]; then
    log "staging: behind (${QA_COMMIT:0:8} -> $TIP_SHORT) but the last staging deploy was ${age}s ago - waiting out the ${MIN_INTERVAL}s brake"
    return 0
  fi

  log "staging: deployed=${QA_COMMIT:0:8} origin/main=$TIP_SHORT - running deploy-staging.sh (QA gate + promo deadline)"
  bash "$DIR/deploy-staging.sh" >> "$LOG" 2>&1
  local rc=$?
  log "staging: deploy-staging.sh rc=$rc"
}

if [ "$STATUS_ONLY" = "1" ]; then
  echo "origin/main tip:        $TIP"
  echo "deployed + QA token:    ${QA_COMMIT:-none} (STATUS=${QA_STATUS:-none})"
  echo "auto-promote timer:     $TIMER_STATE (pid=${timer_pid:-none} tag=${timer_tag:-none} commit=${timer_commit:0:8})"
  fire_at="$(_ap_get FIRE_AT)"
  if [ -n "$fire_at" ]; then
    remaining=$(( fire_at - $(date +%s) ))
    if [ "$remaining" -gt 0 ]; then echo "auto-promote deadline:  in ${remaining}s"; else echo "auto-promote deadline:  PASSED (would fire now)"; fi
  fi
  echo "seconds since last staging deploy: $(staging_age)"
  if [ "$QA_COMMIT" != "$TIP" ]; then echo "would: re-run deploy-staging.sh to catch staging up"; else echo "staging is up to date"; fi
  exit 0
fi

promote_if_due
staging_catchup
exit 0