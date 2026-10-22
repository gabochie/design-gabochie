#!/bin/bash
# rollback-live.sh — roll PRODUCTION code back to a previously deployed tag.
#
# Code-only rollback: the database is never touched. Tag deploys never sync the
# database, so reverting the checkout is the exact inverse of a bad release.
#
# Usage:
#   bash ~/deploy/rollback-live.sh              # roll back to the previous tag
#   bash ~/deploy/rollback-live.sh --to v3.22.3 # roll back to a specific tag
#   bash ~/deploy/rollback-live.sh --list       # show recent tags, newest first
#   bash ~/deploy/rollback-live.sh --dry-run    # show what would happen
#
# Every run first snapshots the current production tree to ~/deploy/backups/ so
# the rollback itself can be undone.
set -euo pipefail
export HOME=/home/gabozvqd

BASE=/home/gabozvqd
CLONE="$BASE/gabochie-deploy"
DOCROOT="$BASE/public_html"
DEPLOY="$BASE/deploy"
LOG="$DEPLOY/deploy-live.log"
LOCK="$DEPLOY/deploy-live.lock"
BACKUP_DIR="$DEPLOY/backups"
KEEP_TAGS=20

SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=deploy-common.sh
source "$SRC/deploy-common.sh"

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG"; }
die() { log "ROLLBACK FAIL: $*"; exit 1; }

TARGET=""
DRY_RUN=0

while [ $# -gt 0 ]; do
  case "$1" in
    --to) TARGET="${2:-}"; [ -n "$TARGET" ] || die "--to needs a tag"; shift 2 ;;
    --list) LIST=1; shift ;;
    --dry-run) DRY_RUN=1; shift ;;
    -h|--help) sed -n '2,16p' "$0"; exit 0 ;;
    *) die "unknown argument: $1" ;;
  esac
done

[ -d "$CLONE/.git" ] || die "live clone missing at $CLONE"
cd "$CLONE"
git fetch origin --tags --force >/dev/null 2>&1 || die "git fetch failed"

mapfile -t TAGS < <(git for-each-ref --sort=-creatordate --format='%(refname:short)' 'refs/tags/v*' 'refs/tags/auto-*' | head -n "$KEEP_TAGS")
[ "${#TAGS[@]}" -gt 0 ] || die "no deploy tags found"

if [ "${LIST:-0}" = "1" ]; then
  printf '%s\n' "${TAGS[@]}"
  exit 0
fi

CURRENT_TAG="$(git describe --tags --exact-match 2>/dev/null || true)"

if [ -z "$TARGET" ]; then
  for tag in "${TAGS[@]}"; do
    if [ "$tag" != "$CURRENT_TAG" ]; then TARGET="$tag"; break; fi
  done
  [ -n "$TARGET" ] || die "could not determine previous tag (current: ${CURRENT_TAG:-none})"
fi

git rev-parse -q --verify "refs/tags/$TARGET^{commit}" >/dev/null || die "tag '$TARGET' does not exist"

TARGET_COMMIT="$(git rev-parse "refs/tags/$TARGET^{commit}")"
log "===== rollback-live.sh start (current=${CURRENT_TAG:-none} -> $TARGET) ====="
log "target commit: $TARGET_COMMIT"

if [ "$DRY_RUN" = "1" ]; then
  log "dry-run: would snapshot $DOCROOT and checkout $TARGET"
  exit 0
fi

if [ -f "$LOCK" ]; then die "a deploy is already running ($LOCK); retry after it finishes"; fi
touch "$LOCK"
trap 'rm -f "$LOCK"' EXIT

# --- snapshot current production before changing anything ---
TS="$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR"
SNAP="$BACKUP_DIR/live-files-prerollback-$TS.tar.gz"
log "snapshot: $SNAP"
tar -czf "$SNAP" -C "$DOCROOT" \
  --exclude=wp-content/uploads --exclude=wp-content/cache --exclude=wp-content/litespeed . \
  2>>"$LOG" || log "WARN: snapshot incomplete (continuing)"
[ -s "$SNAP" ] || die "pre-rollback snapshot is empty; refusing to continue"

# --- checkout + sync ---
git checkout --force "refs/tags/$TARGET" || die "checkout $TARGET failed"
log "checked out $TARGET ($(git log --oneline -1))"

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
  ./ "$DOCROOT/" || die "rsync failed"

chmod 755 "$DOCROOT"
find "$DOCROOT" -type d -exec chmod 755 {} +
find "$DOCROOT" -type f -exec chmod 644 {} +

# --- caches ---
export PATH=/usr/local/sbin:/usr/local/bin:/usr/bin:/bin
wp --path="$DOCROOT" cache flush --allow-root >>"$LOG" 2>&1 || true
wp --path="$DOCROOT" rewrite flush --allow-root >>"$LOG" 2>&1 || true

# --- smoke test (same contract as deploy-live.sh) ---
if bash "$SRC/lib/smoke-test.sh" live >>"$LOG" 2>&1; then
  log "smoke: PASS after rollback to $TARGET"
  deploy_status_write live "rollback_ok" "{\"tag\":\"$TARGET\",\"commit\":\"$TARGET_COMMIT\",\"previous\":\"${CURRENT_TAG:-unknown}\",\"time\":\"$(date -u '+%Y-%m-%dT%H:%M:%SZ')\"}"
  _deploy_send_email "[Gabochie Deploy] ROLLBACK to $TARGET" \
    "Production code rolled back from ${CURRENT_TAG:-unknown} to $TARGET ($TARGET_COMMIT). Smoke test passed."
  exit 0
fi

log "smoke: FAIL after rollback to $TARGET — production needs manual attention"
deploy_status_write live "rollback_fail" "{\"tag\":\"$TARGET\",\"commit\":\"$TARGET_COMMIT\",\"time\":\"$(date -u '+%Y-%m-%dT%H:%M:%SZ')\"}"
_deploy_send_email "[Gabochie Deploy] ROLLBACK FAILED smoke test" \
  "Rollback to $TARGET completed but the smoke test failed. Check https://gabochiedesign.com immediately. Pre-rollback snapshot: $SNAP"
exit 1
