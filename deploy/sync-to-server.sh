#!/bin/bash
# sync-to-server.sh — install the versioned deploy scripts onto the web host.
#
# This is how the repository becomes the source of truth for ~/deploy. It never
# copies environment/secret files (~/deploy/*.env) and never touches the
# database. Every replaced file is backed up first.
#
# Usage:
#   ./deploy/sync-to-server.sh --check     # report drift only, exit 1 on drift
#   ./deploy/sync-to-server.sh             # install ~/deploy scripts
#   ./deploy/sync-to-server.sh --webhook   # also install public_html/webhook.php
#
# Environment:
#   DEPLOY_HOST, DEPLOY_PORT, DEPLOY_USER, DEPLOY_SSH_KEY
set -euo pipefail

HOST="${DEPLOY_HOST:-server178.web-hosting.com}"
PORT="${DEPLOY_PORT:-21098}"
USER_NAME="${DEPLOY_USER:-gabozvqd}"
REMOTE_DIR="${DEPLOY_REMOTE_DIR:-/home/gabozvqd/deploy}"
REMOTE_DOCROOT="${DEPLOY_REMOTE_DOCROOT:-/home/gabozvqd/public_html}"
SSH_KEY="${DEPLOY_SSH_KEY:-$HOME/.ssh/gabochiedesign_deploy_ed25519}"

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

FILES=(
  deploy-common.sh
  deploy-staging.sh
  deploy-live.sh
  run-qa.sh
  qa.sh
  promote.sh
  backup.sh
  watchdog.sh
  review-probe.sh
  rollback-live.sh
  healthcheck.sh
  ci-ssh.sh
  ci-promote.sh
  ci-verify-tag.sh
  lib/smoke-test.sh
)

MODE="install"
WITH_WEBHOOK=0

while [ $# -gt 0 ]; do
  case "$1" in
    --check) MODE="check" ;;
    --webhook) WITH_WEBHOOK=1 ;;
    -h|--help) sed -n '2,16p' "$0"; exit 0 ;;
    *) echo "unknown argument: $1" >&2; exit 2 ;;
  esac
  shift
done

SSH_OPTS=(-p "$PORT" -o BatchMode=yes -o ConnectTimeout=15)
SCP_OPTS=(-P "$PORT" -o BatchMode=yes -o ConnectTimeout=15)
if [ -f "$SSH_KEY" ]; then SSH_OPTS+=(-i "$SSH_KEY"); SCP_OPTS+=(-i "$SSH_KEY"); fi

ssh_cmd() { ssh "${SSH_OPTS[@]}" "$USER_NAME@$HOST" "$@"; }
scp_cmd() { scp "${SCP_OPTS[@]}" "$@"; }

for f in "${FILES[@]}"; do
  [ -f "$HERE/$f" ] || { echo "missing local file: $HERE/$f" >&2; exit 1; }
done

if [ "$MODE" = "check" ]; then
  drift=0
  for f in "${FILES[@]}"; do
    local_sum="$(sha256sum "$HERE/$f" | awk '{print $1}')"
    remote_sum="$(ssh_cmd "sha256sum '$REMOTE_DIR/$f' 2>/dev/null | awk '{print \$1}'" || true)"
    if [ "$local_sum" = "$remote_sum" ]; then
      echo "OK      $f"
    else
      echo "DRIFT   $f (remote: ${remote_sum:-missing})"
      drift=1
    fi
  done
  exit "$drift"
fi

TS="$(date +%Y%m%d-%H%M%S)"
echo "== syncing scripts to $USER_NAME@$HOST:$REMOTE_DIR (backup: backups/scripts-$TS) =="
ssh_cmd "mkdir -p '$REMOTE_DIR/lib' '$REMOTE_DIR/backups/scripts-$TS'"

for f in "${FILES[@]}"; do
  remote_path="$REMOTE_DIR/$f"
  echo "-- $f"
  scp_cmd "$HERE/$f" "$USER_NAME@$HOST:$REMOTE_DIR/.incoming-$(basename "$f")" >/dev/null
  ssh_cmd "if [ -f '$remote_path' ]; then cp -p '$remote_path' '$REMOTE_DIR/backups/scripts-$TS/$(basename "$f")'; fi"
  ssh_cmd "mv -f '$REMOTE_DIR/.incoming-$(basename "$f")' '$remote_path'; chmod 755 '$remote_path'"
done

echo "== syntax checking installed shell scripts =="
if ssh_cmd "for f in $(printf "'%s' " "${FILES[@]}"); do case \"\$f\" in *.sh) bash -n \"$REMOTE_DIR/\$f\" || exit 1;; esac; done"; then
  echo "OK      bash -n"
else
  echo "FAIL    bash -n on server — restoring from backups/scripts-$TS" >&2
  ssh_cmd "for f in $(printf "'%s' " "${FILES[@]}"); do b='$REMOTE_DIR/backups/scripts-$TS/'\"\$(basename \"\$f\")\"; [ -f \"\$b\" ] && cp -p \"\$b\" \"$REMOTE_DIR/\$f\"; done"
  exit 1
fi

echo "== php -l webhook (local copy) =="
if command -v php >/dev/null 2>&1; then
  php -l "$HERE/webhook.php" >/dev/null && echo "OK      webhook.php"
fi

if [ "$WITH_WEBHOOK" = "1" ]; then
  echo "== installing webhook.php into $REMOTE_DOCROOT =="
  ssh_cmd "mkdir -p '$REMOTE_DIR/backups/scripts-$TS'"
  scp_cmd "$HERE/webhook.php" "$USER_NAME@$HOST:$REMOTE_DIR/.incoming-webhook.php" >/dev/null
  ssh_cmd "if [ -f '$REMOTE_DOCROOT/webhook.php' ]; then cp -p '$REMOTE_DOCROOT/webhook.php' '$REMOTE_DIR/backups/scripts-$TS/webhook.php.docroot'; fi"
  ssh_cmd "mv -f '$REMOTE_DIR/.incoming-webhook.php' '$REMOTE_DOCROOT/webhook.php'; chmod 644 '$REMOTE_DOCROOT/webhook.php'"
  echo "NOTE    GitHub webhook secret must match $REMOTE_DIR/webhook-secret.env"
else
  echo "NOTE    webhook.php not installed (use --webhook)"
fi

echo "== done =="
