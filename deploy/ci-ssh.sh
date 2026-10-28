#!/bin/bash
# ci-ssh.sh — forced-command allowlist for the GitHub Actions deploy key.
#
# The CI key must never have a general-purpose shell. Install its public key in
# ~/.ssh/authorized_keys with this script as the forced command:
#
#   command="/home/gabozvqd/deploy/ci-ssh.sh",no-agent-forwarding,no-port-forwarding,no-pty,no-x11-forwarding ssh-ed25519 AAAA... github-actions@gabochie-design
#
# The runner can then only invoke the actions below:
#   ssh ... ping
#   ssh ... health
#   ssh ... health --prometheus
#   ssh ... metrics
#   ssh ... monitor-status
#   ssh ... backup
#   ssh ... qa-state
#   ssh ... main-sha
#   ssh ... verify-tag <tag>
#   ssh ... promote <vX.Y.Z>
#   ssh ... rollback
#   ssh ... logs [lines]
set -uo pipefail
export HOME=/home/gabozvqd
export PATH=/usr/local/sbin:/usr/local/bin:/usr/bin:/bin
DEPLOY=/home/gabozvqd/deploy

# SSH_ORIGINAL_COMMAND is attacker-controlled: split it and dispatch on an exact
# match. Never eval it.
read -r -a ARGS <<< "${SSH_ORIGINAL_COMMAND:-}"
ACTION="${ARGS[0]:-}"
ARG1="${ARGS[1]:-}"

case "$ACTION" in
  ping)
    echo ok
    ;;
  health)
    case "${ARG1:-}" in
      "")
        exec bash "$DEPLOY/healthcheck.sh"
        ;;
      --prometheus)
        bash "$DEPLOY/healthcheck.sh" --prometheus || true
        ;;
      --human)
        bash "$DEPLOY/healthcheck.sh" --human || true
        ;;
      *)
        echo "denied: health accepts --prometheus or --human" >&2
        exit 64
        ;;
    esac
    ;;
  metrics)
    if [ -f "$DEPLOY/metrics/health.prom" ]; then
      cat "$DEPLOY/metrics/health.prom"
    else
      bash "$DEPLOY/healthcheck.sh" --prometheus 2>/dev/null || true
    fi
    ;;
  monitor-status)
    exec bash "$DEPLOY/monitor.sh" --status
    ;;
  backup)
    exec bash "$DEPLOY/backup.sh"
    ;;
  backup-status)
    newest="$(ls -1t "$DEPLOY"/backups/live-db-*.sql.gz 2>/dev/null | head -1)"
    if [ -z "$newest" ]; then echo "MISSING"; exit 1; fi
    age_h=$(( ( $(date +%s) - $(stat -c %Y "$newest") ) / 3600 ))
    size=$(stat -c %s "$newest")
    echo "file=$(basename "$newest") age_hours=$age_h size_bytes=$size"
    [ "$age_h" -le 36 ] || { echo "STALE"; exit 2; }
    [ "$size" -gt 100000 ] || { echo "TOO_SMALL"; exit 3; }
    echo "OK"
    ;;
  updates)
    wp --path="$HOME/wp.design.gabochie.com" plugin list --update=available \
      --format=table --allow-root 2>/dev/null
    echo "---CORE---"
    wp --path="$HOME/wp.design.gabochie.com" core check-update \
      --format=table --allow-root 2>/dev/null
    ;;
  qa-state)
    if [ -f "$DEPLOY/qa-state.txt" ]; then cat "$DEPLOY/qa-state.txt"; else echo "STATUS=missing"; fi
    ;;
  main-sha)
    git -C /home/gabozvqd/gabochie-deploy-staging rev-parse origin/main 2>/dev/null || exit 1
    ;;
  verify-tag)
    exec bash "$DEPLOY/ci-verify-tag.sh" "$ARG1"
    ;;
  promote)
    exec bash "$DEPLOY/ci-promote.sh" "$ARG1"
    ;;
  rollback)
    exec bash "$DEPLOY/rollback-live.sh"
    ;;
  logs)
    LINES="${ARG1:-40}"
    case "$LINES" in ''|*[!0-9]*) LINES=40 ;; esac
    [ "$LINES" -le 500 ] || LINES=500
    tail -n "$LINES" "$DEPLOY/deploy-live.log" 2>/dev/null || true
    ;;
  *)
    echo "denied: allowed actions are ping health metrics monitor-status backup backup-status updates qa-state main-sha verify-tag promote rollback logs" >&2
    exit 64
    ;;
esac
