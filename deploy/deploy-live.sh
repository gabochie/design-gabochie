#!/bin/bash
# deploy-live.sh <tag> — deploy a tagged release to LIVE (public_html).
# Called by webhook.php on refs/tags/v* or refs/tags/auto-* pushes. Uses dedicated live clone.
# Keeper files (untracked, live-owned) are excluded from the rsync scan so
# --delete never wipes them.
# BLOCKED unless the tagged commit passed staging QA (see deploy-common.sh).
set -euo pipefail
export HOME=/home/gabozvqd

BASE=/home/gabozvqd
CLONE="$BASE/gabochie-deploy"
DOCROOT="$BASE/public_html"
LOG="$BASE/deploy/deploy-live.log"
LOCK="$BASE/deploy/deploy-live.lock"
SRC="$(cd "$(dirname "$0")" && pwd)"
source "$SRC/deploy-common.sh"

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG"; }

if [ -f "$LOCK" ]; then log "ALREADY RUNNING — exiting"; exit 0; fi
touch "$LOCK"
trap 'rm -f "$LOCK"' EXIT

TAG="${1:-}"
if [ -z "$TAG" ]; then log "FAIL: no tag argument"; exit 1; fi
case "$TAG" in
  v[0-9]*.* | auto-* ) ;;
  * ) log "FAIL: tag '$TAG' does not match vX.Y.Z or auto-* pattern"; exit 1;;
esac

cd "$CLONE"
log "===== deploy-live.sh $TAG start ====="
git fetch origin --tags --force
git checkout --force "refs/tags/$TAG" || { log "FAIL: checkout $TAG"; exit 1; }
TAG_COMMIT=$(git rev-parse HEAD)
log "checked out $TAG ($(git log --oneline -1))"

# --- QA gate: block live unless this exact commit passed staging QA ---
if qa_gate_check "$TAG_COMMIT"; then
  log "gate: deploy token OK for $TAG ($TAG_COMMIT)"
else
  log "gate: LIVE DEPLOY BLOCKED for $TAG ($TAG_COMMIT)"
  exit 3
fi

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
log "deploy ok $TAG"

# Notify live site for Ama voice agent
_deploy_site_notify "$TAG" "$TAG" "Live deploy $TAG complete"

# Write deploy status JSON (dashboard reads this)
deploy_status_write live "deploy_ok" "{\"tag\":\"$TAG\",\"commit\":\"$TAG_COMMIT\",\"short\":\"$(git log --oneline -1)\",\"time\":\"$(date -u '+%Y-%m-%dT%H:%M:%SZ')\"}"
