#!/bin/bash
# deploy-common.sh - shared helpers for the Gabochie Design deploy scripts.
# Sourced by deploy-staging.sh and deploy-live.sh (server-side ~/deploy/).
#
# QA GATE MODEL (implemented 2026-08-12):
#   - Every STAGING deploy (main push) runs qa.sh --target=staging AFTER rsync and
#     records STATUS=pass + COMMIT=<sha> in qa-state.txt (the "deploy token").
#   - LIVE (tag) deploys call qa_gate_check <commit> BEFORE rsync to public_html.
#     The deploy is BLOCKED unless staging QA passed at exactly that commit.
#   - Fail-closed: no state, STATUS=needs-qa/running/fail, or commit mismatch all
#     refuse the live deploy.
#
# AUTO-PROMOTE MODEL (implemented 2026-08-24):
#   - After staging QA passes, a background timer waits AUTO_PROMOTE_DELAY seconds
#     (default 900 = 15 min), then auto-creates a tag and pushes it to origin.
#   - The tag push fires webhook.php -> deploy-live.sh -> live deploy.
#   - Cancel: new staging deploy kills previous timer; or touch ~/deploy/skip-live.
#   - Skip: commit message containing [skip-live] prevents the timer from starting.
#   - Email notification sent on timer start and auto-promote fire.

QA_STATE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
QA_STATE="$QA_STATE_DIR/qa-state.txt"
AUTO_PROMOTE_STATE="$QA_STATE_DIR/.auto-promote"
AUTO_PROMOTE_DELAY="${AUTO_PROMOTE_DELAY:-900}"  # 15 minutes default
SKIP_LIVE_FILE="$QA_STATE_DIR/skip-live"
NOTIFY_EMAIL="${DEPLOY_NOTIFY_EMAIL:-admin@design.gabochie.com}"

# Shared secret for POST /wp-json/gabochie/v1/deploy-status (gabochie_deploy_status_permissions
# in the theme's inc/client-portal-verify.php refuses writes without it).
# Lives outside the docroot in a 600 file and is never committed to the repo.
DEPLOY_STATUS_SECRET_FILE="$QA_STATE_DIR/deploy-secret.env"
if [ -f "$DEPLOY_STATUS_SECRET_FILE" ]; then
  # shellcheck disable=SC1090
  . "$DEPLOY_STATUS_SECRET_FILE"
fi
DEPLOY_STATUS_SECRET="${DEPLOY_STATUS_SECRET:-}"

# --- QA gate ---

qa_gate_mark() { # <status> <commit>
  local status="$1" commit="${2:-unknown}"
  printf 'STATUS=%s\nCOMMIT=%s\nTIME=%s\n' \
    "$status" "$commit" "$(date -u '+%Y-%m-%dT%H:%M:%SZ')" > "$QA_STATE"
}

qa_gate_show() { # -> current state (or "no state")
  if [ -f "$QA_STATE" ]; then cat "$QA_STATE"; else echo "no qa-state.txt"; fi
}

qa_gate_check() { # <commit> -> 0 if staging QA passed at exactly <commit>, else 1
  local require_commit="${1:-}"
  if [ ! -f "$QA_STATE" ]; then
    echo "gate: NO qa-state.txt - refusing live deploy (no staging QA run yet)"
    return 1
  fi
  local status commit
  status="$(sed -n 's/^STATUS=//p' "$QA_STATE")"
  commit="$(sed -n 's/^COMMIT=//p' "$QA_STATE")"
  if [ "$status" != "pass" ]; then
    echo "gate: staging QA status=$status (need pass) - refusing live deploy"
    return 1
  fi
  if [ -n "$require_commit" ] && [ "${commit:0:8}" != "${require_commit:0:8}" ]; then
    echo "gate: commit mismatch (staging QA=$commit, tag=$require_commit) - refusing live deploy"
    return 1
  fi
  echo "gate: staging QA passed @ $commit - deploy token OK"
  return 0
}

# --- Auto-promote helpers ---

auto_promote_cancel() {
  # Kill any pending auto-promote timer by removing the state file
  if [ -f "$AUTO_PROMOTE_STATE" ]; then
    local old_pid
    old_pid="$(sed -n 's/^PID=//p' "$AUTO_PROMOTE_STATE" 2>/dev/null)"
    if [ -n "$old_pid" ] && kill -0 "$old_pid" 2>/dev/null; then
      kill "$old_pid" 2>/dev/null || true
    fi
    rm -f "$AUTO_PROMOTE_STATE"
    echo "auto-promote: previous timer cancelled"
    deploy_status_write staging "auto_promote_cancelled" '{"time":"'"$(date -u '+%Y-%m-%dT%H:%M:%SZ')"'"}'
  fi
}

auto_promote_should_skip() { # <commit> -> 0 if [skip-live] found in commit message
  local commit="${1:-HEAD}"
  local msg
  msg="$(git log -1 --format='%B' "$commit" 2>/dev/null || echo '')"
  if echo "$msg" | grep -qi '\[skip-live\]'; then
    return 0
  fi
  return 1
}

_ap_get() { # <key> -> value from the auto-promote state file
  sed -n "s/^$1=//p" "$AUTO_PROMOTE_STATE" 2>/dev/null | head -1
}

auto_promote_execute() { # <commit> <tag> -> create + push the promotion tag
  local commit="$1" tag="$2"
  local short="${commit:0:8}"
  local clone="$HOME/gabochie-deploy-staging"

  if [ -f "$SKIP_LIVE_FILE" ]; then
    rm -f "$SKIP_LIVE_FILE" "$AUTO_PROMOTE_STATE"
    echo "auto-promote: skip-live file found — aborting"
    return 0
  fi

  if [ ! -d "$clone/.git" ]; then
    echo "auto-promote: staging clone missing at $clone — aborting"
    return 1
  fi

  cd "$clone" || return 1
  git fetch origin main --force --quiet 2>/dev/null || true

  if ! git rev-parse -q --verify "${commit}^{commit}" >/dev/null; then
    echo "auto-promote: commit $commit not found in clone — aborting"
    rm -f "$AUTO_PROMOTE_STATE"
    return 1
  fi

  # Already pushed by a previous attempt (webhook and watchdog can race).
  if git ls-remote --exit-code --tags origin "refs/tags/$tag" >/dev/null 2>&1; then
    echo "auto-promote: tag $tag already on origin — clearing state"
    rm -f "$AUTO_PROMOTE_STATE"
    return 0
  fi

  # Tag the exact commit; never touch the working tree (a staging deploy may be running).
  git tag -f -a "$tag" "$commit" -m "Auto-promote after staging QA pass" 2>/dev/null || return 1
  if ! git push origin "refs/tags/$tag" 2>/dev/null; then
    echo "auto-promote: FAILED to push tag $tag"
    return 1
  fi

  echo "auto-promote: tagged and pushed $tag ($short)"
  deploy_status_write staging "auto_promote_fired" "{\"tag\":\"$tag\",\"commit\":\"$commit\",\"short\":\"$short\",\"time\":\"$(date -u '+%Y-%m-%dT%H:%M:%SZ')\"}"
  _auto_promote_notify_fire "$commit" "$tag"
  rm -f "$AUTO_PROMOTE_STATE"
  return 0
}

auto_promote_tick() {
  # Fires a due promotion. Safe to call from cron: state on disk (FIRE_AT) is the
  # source of truth, so a killed background process can never strand a release.
  [ -f "$AUTO_PROMOTE_STATE" ] || return 0

  local commit tag fire_at now
  commit="$(_ap_get COMMIT)"
  tag="$(_ap_get TAG)"
  fire_at="$(_ap_get FIRE_AT)"
  now="$(date +%s)"

  if [ -z "$commit" ] || [ -z "$tag" ]; then
    echo "auto-promote: malformed state file — clearing"
    rm -f "$AUTO_PROMOTE_STATE"
    return 0
  fi

  # Backward compatibility with state files written before FIRE_AT existed.
  if [ -z "$fire_at" ]; then
    fire_at=$(( now + 1 ))
    echo "auto-promote: state file has no FIRE_AT — treating as due now"
  fi

  if [ "$now" -lt "$fire_at" ]; then
    return 0
  fi

  if [ -f "$SKIP_LIVE_FILE" ]; then
    rm -f "$SKIP_LIVE_FILE" "$AUTO_PROMOTE_STATE"
    echo "auto-promote: skip-live file found at fire time — promotion cancelled"
    deploy_status_write staging "auto_promote_cancelled" '{"time":"'"$(date -u '+%Y-%m-%dT%H:%M:%SZ')"'"}'
    return 0
  fi

  if ! qa_gate_check "$commit" >/dev/null 2>&1; then
    echo "auto-promote: QA token no longer valid for ${commit:0:8} — clearing state"
    rm -f "$AUTO_PROMOTE_STATE"
    return 0
  fi

  if auto_promote_should_skip "$commit"; then
    echo "auto-promote: [skip-live] in commit message — clearing state"
    rm -f "$AUTO_PROMOTE_STATE"
    return 0
  fi

  auto_promote_execute "$commit" "$tag"
}

auto_promote_start() { # <commit> - schedule the promotion deadline
  local commit="$1"
  local short="${commit:0:8}"
  local tag="auto-$(date +%Y%m%d-%H%M%S)"
  local now fire_at

  # Cancel any previous timer
  auto_promote_cancel

  # Check [skip-live]
  if auto_promote_should_skip "$commit"; then
    echo "auto-promote: [skip-live] found in commit message — skipping auto-promote"
    return 0
  fi

  now="$(date +%s)"
  fire_at=$(( now + AUTO_PROMOTE_DELAY ))

  # FIRE_AT (epoch) is authoritative: the mechanism no longer depends on a
  # long-lived process surviving cPanel's process reaper.
  printf 'COMMIT=%s\nTAG=%s\nSTART=%s\nFIRE_AT=%s\nDELAY=%s\nPID=\n' \
    "$commit" "$tag" "$(date -u '+%Y-%m-%dT%H:%M:%SZ')" "$fire_at" "$AUTO_PROMOTE_DELAY" \
    > "$AUTO_PROMOTE_STATE"

  echo "auto-promote: timer started — will promote $short as $tag in ${AUTO_PROMOTE_DELAY}s (fire_at=$fire_at)"

  deploy_status_write staging "auto_promote_start" "{\"commit\":\"$commit\",\"short\":\"$short\",\"tag\":\"$tag\",\"delay\":$AUTO_PROMOTE_DELAY,\"fire_time\":\"$(date -u -d "@$fire_at" '+%Y-%m-%dT%H:%M:%SZ' 2>/dev/null || date -u '+%Y-%m-%dT%H:%M:%SZ')\"}"

  _auto_promote_notify_start "$commit" "$tag" "$AUTO_PROMOTE_DELAY"

  # Best-effort prompt firing. If cPanel kills this, watchdog.sh fires it from
  # cron once FIRE_AT has passed (auto_promote_tick).
  export HOME="${HOME:-/home/gabozvqd}"
  nohup bash -c "
    export HOME='${HOME}'
    sleep $AUTO_PROMOTE_DELAY
    source '$QA_STATE_DIR/deploy-common.sh' 2>/dev/null || exit 0
    auto_promote_tick >> '$QA_STATE_DIR/auto-promote.log' 2>&1
  " > /dev/null 2>&1 &

  local bg_pid=$!
  sed -i "s/^PID=.*/PID=$bg_pid/" "$AUTO_PROMOTE_STATE" 2>/dev/null || true
}

auto_promote_status() { # Show current auto-promote state
  if [ -f "$AUTO_PROMOTE_STATE" ]; then
    echo "=== Auto-Promote Pending ==="
    cat "$AUTO_PROMOTE_STATE"
    local pid fire_at remaining
    pid="$(_ap_get PID)"
    fire_at="$(_ap_get FIRE_AT)"
    if [ -n "$fire_at" ]; then
      remaining=$(( fire_at - $(date +%s) ))
      if [ "$remaining" -gt 0 ]; then
        echo "STATUS=due in ${remaining}s (cron-backed; survives process death)"
      else
        echo "STATUS=due now (cron-backed; survives process death)"
      fi
    fi
    if [ -n "$pid" ] && kill -0 "$pid" 2>/dev/null; then
      echo "PROCESS=running (PID $pid)"
    else
      echo "PROCESS=not running (cron will fire it)"
    fi
  else
    echo "auto-promote: no pending promotion"
  fi
}

# --- Email notifications ---

_auto_promote_notify_start() { # <commit> <tag> <delay_seconds>
  local commit="$1" tag="$2" delay="$3"
  local short="${commit:0:8}"
  local mins=$(( delay / 60 ))
  local subject="[Gabochie Deploy] Auto-promote timer started for $tag"
  local body="Auto-promote timer started.

Commit: $short ($commit)
Tag: $tag
Delay: ${mins} minutes
Scheduled fire: $(date -d "+${delay} seconds" '+%Y-%m-%d %H:%M:%S %Z' 2>/dev/null || date '+%Y-%m-%d %H:%M:%S')

Staging: https://wp.design.gabochie.com
Live: https://design.gabochie.com

To cancel: touch ~/deploy/skip-live
Or wait for the next push to main (auto-cancels previous timer)."

  _deploy_send_email "$subject" "$body"
  _deploy_site_notify "$commit" "$tag" "Auto-promote fired: $tag deployed to live"
}

_auto_promote_notify_fire() { # <commit> <tag>
  local commit="$1" tag="$2"
  local short="${commit:0:8}"
  local subject="[Gabochie Deploy] Auto-promote fired: $tag deployed to live"
  local body="Auto-promote has fired.

Commit: $short ($commit)
Tag: $tag
Live: https://design.gabochie.com

The tag was pushed to GitHub and the live deploy webhook has been triggered.
Check ~/deploy/deploy-live.log for deploy status."

  _deploy_send_email "$subject" "$body"
}

_deploy_send_email() { # <subject> <body>
  local subject="$1" body="$2"
  # Try PHP wp_mail first (works on WP server), fall back to mail command
  if command -v php >/dev/null 2>&1; then
    php -r "
      \$to = '$NOTIFY_EMAIL';
      \$subj = '$subject';
      \$body = '$(echo "$body" | sed "s/'/\\\\'/g")';
      \$headers = 'From: deploy@design.gabochie.com';
      mail(\$to, \$subj, \$body, \$headers);
    " 2>/dev/null || echo "$body" | mail -s "$subject" "$NOTIFY_EMAIL" 2>/dev/null || true
  else
    echo "$body" | mail -s "$subject" "$NOTIFY_EMAIL" 2>/dev/null || true
  fi
}

# --- Deploy status JSON (read by dashboard) ---
# Notify live site of deploy (for Ama voice agent)
_deploy_site_notify() {
  local ver="$1" tag="$2" msg="$3"
  if [ -z "${DEPLOY_STATUS_SECRET:-}" ]; then
    echo "site-notify: SKIPPED (no DEPLOY_STATUS_SECRET; see $DEPLOY_STATUS_SECRET_FILE)"
    return 0
  fi
  curl -s -o /dev/null -X POST \
    -H "Content-Type: application/json" \
    -H "X-Gabochie-Deploy-Secret: $DEPLOY_STATUS_SECRET" \
    -d "{\"version\":\"$ver\",\"tag\":\"$tag\",\"message\":\"$msg\"}" \
    --max-time 5 \
    "https://design.gabochie.com/wp-json/gabochie/v1/deploy-status" 2>/dev/null || true
  echo "site-notify: deploy $ver posted to live site"
}

STATUS_FILE="/home/gabozvqd/wp.design.gabochie.com/deploy-status.json"

deploy_status_write() { # <environment> <event> <extra_json>
  local env="$1" event="$2" extra="${3:-{}}"
  local now
  now="$(date -u '+%Y-%m-%dT%H:%M:%SZ')"

  # Read existing file or start fresh
  local existing="{}"
  if [ -f "$STATUS_FILE" ]; then
    existing="$(cat "$STATUS_FILE" 2>/dev/null || echo '{}')"
  fi

  # Build the update using Python (available on cPanel)
  python3 -c "
import json, sys
try:
    data = json.loads('''$existing''')
except:
    data = {}
env = '$env'
event = '$event'
now = '$now'
extra = json.loads('''$extra''')
if env not in data:
    data[env] = {}
data[env].update(extra)
data[env]['last_event'] = event
data[env]['last_update'] = now
data['_meta'] = {'generated': now, 'version': '1.0'}
print(json.dumps(data, indent=2))
" > "$STATUS_FILE" 2>/dev/null || true

  chmod 644 "$STATUS_FILE" 2>/dev/null || true
}
