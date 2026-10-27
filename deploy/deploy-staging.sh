#!/bin/bash
# deploy-staging.sh — deploy main branch to STAGING (wp.design.gabochie.com).
# Called by webhook.php on refs/heads/main pushes. Uses dedicated staging clone.
# Keeper files excluded so rsync --delete never wipes them.
# After the deploy it runs the QA gate (run-qa.sh) which records the deploy token.
# If QA passes, starts a 15-minute auto-promote timer (unless [skip-live] in commit).
set -euo pipefail
export HOME=/home/gabozvqd

BASE=/home/gabozvqd
CLONE="$BASE/gabochie-deploy-staging"
DOCROOT="$BASE/wp.design.gabochie.com"
LOG="$BASE/deploy/deploy-staging.log"
LOCK="$BASE/deploy/deploy-staging.lock"

# Source shared helpers (QA gate + auto-promote)
SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SRC/deploy-common.sh"

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG"; }

if [ -f "$LOCK" ]; then log "ALREADY RUNNING — exiting"; exit 0; fi
touch "$LOCK"
trap 'rm -f "$LOCK"' EXIT

# Cancel any pending auto-promote from a previous deploy
auto_promote_cancel

cd "$CLONE"
log "===== deploy-staging.sh start ====="
git fetch origin main --force
git checkout --force origin/main
DEPLOY_COMMIT=$(git rev-parse HEAD)
log "checked out $(git log --oneline -1)"

rsync -rl --delete --chmod=D755,F644 \
  --exclude='.git' --exclude='.github' --exclude='/deploy/' --exclude='.gitattributes' \
  --exclude='/composer.json' --exclude='/composer.lock' --exclude='/package.json' \
  --exclude='/package-lock.json' --exclude='/phpcs.xml.dist' --exclude='/phpstan.neon' \
  --exclude='/phpstan-baseline.neon' --exclude='/phpunit.xml.dist' --exclude='/tests/' \
  --exclude='/AGENTS.md' --exclude='/README.md' \
  --exclude='wp-config.php' \
  --exclude='wp-content/uploads/' --exclude='wp-content/cache/' \
  --exclude='node_modules/' --exclude='.env' --exclude='local-xdebuginfo.php' \
  --exclude='llms.txt' --exclude='deploy.sh' --exclude='webhook.php' \
  --exclude='.htaccess.bk' --exclude='ads.txt' --exclude='.litespeed_flag' \
  --exclude='BingSiteAuth.xml' --exclude='wordfence-waf.php' \
  --exclude='.well-known/' --exclude='cgi-bin/' \
  --exclude='/wp-admin/' --exclude='/wp-includes/' \
  --exclude='/index.php' --exclude='/license.txt' --exclude='/readme.html' \
  --exclude='/robots.txt' --exclude='/xmlrpc.php' --exclude='/wp-*.php' \
  ./ "$DOCROOT/"

chmod 755 "$DOCROOT"
find "$DOCROOT" -type d -exec chmod 755 {} +
find "$DOCROOT" -type f -exec chmod 644 {} +
# wp lives in /usr/local/sbin, which non-interactive shells don't include (same reason qa.sh exports it).
export PATH=/usr/local/sbin:/usr/local/bin:/usr/bin:/bin
log "core: $(wp core version --path="$DOCROOT" --allow-root 2>/dev/null | tail -1 || echo unknown) (WordPress-managed, excluded from rsync)"
log "deploy ok main"

# --- QA gate: run the staging QA suite and record the deploy token ---
log "gate: running QA on staging ($DEPLOY_COMMIT)"
if bash "$BASE/deploy/run-qa.sh" "$DEPLOY_COMMIT" >> "$LOG" 2>&1; then
  log "gate: QA PASS - deploy token issued for $DEPLOY_COMMIT"

  # Write deploy status JSON (dashboard reads this)
  deploy_status_write staging "deploy_pass" "{\"commit\":\"$DEPLOY_COMMIT\",\"short\":\"$(git log --oneline -1)\",\"qa\":\"pass\",\"time\":\"$(date -u '+%Y-%m-%dT%H:%M:%SZ')\"}"

  # --- Auto-promote: start 15-minute timer (unless [skip-live]) ---
  auto_promote_start "$DEPLOY_COMMIT" >> "$LOG" 2>&1
else
  log "gate: QA FAIL - live deploy of $DEPLOY_COMMIT is BLOCKED"
  deploy_status_write staging "deploy_fail" "{\"commit\":\"$DEPLOY_COMMIT\",\"short\":\"$(git log --oneline -1)\",\"qa\":\"fail\",\"time\":\"$(date -u '+%Y-%m-%dT%H:%M:%SZ')\"}"
fi
