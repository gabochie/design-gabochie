#!/bin/bash
# backup.sh - Nightly DB + uploads backup for live + staging (gabochie-design).
# Installed as a cPanel cron job; also safe to run by hand:  bash ~/deploy/backup.sh
# Retention: keeps the newest 7 backups per target, deletes older ones.
set -u

DEPLOY="$HOME/deploy"
LOG="$DEPLOY/backup.log"
BACKUP_DIR="$DEPLOY/backups"
RETENTION=7
TS=$(date +%Y%m%d-%H%M%S)

mkdir -p "$BACKUP_DIR"

say()  { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" >> "$LOG"; }
fail() { say "FAIL: $1"; echo "backup FAILED: $1" >> "$LOG"; exit 1; }

say "===== backup.sh start ====="

if [ ! -f "$DEPLOY/live.env" ] || [ ! -f "$DEPLOY/staging.env" ]; then
  fail "Missing live.env or staging.env in $DEPLOY"
fi
# shellcheck disable=SC1091
source "$DEPLOY/live.env"
# shellcheck disable=SC1091
source "$DEPLOY/staging.env"

# ---- live DB ----
LIVE_BASE="$BACKUP_DIR/live-db-$TS.sql.gz"
say "Dumping live DB ($LIVE_DB_NAME) -> $(basename "$LIVE_BASE")"
wp --path="$LIVE_DOCROOT" db export - 2>>"$LOG" | gzip > "$LIVE_BASE" \
  || fail "Live DB dump failed."
[ -s "$LIVE_BASE" ] || fail "Live DB dump empty."

# ---- staging DB ----
STAGE_BASE="$BACKUP_DIR/staging-db-$TS.sql.gz"
say "Dumping staging DB ($STAGING_DB_NAME) -> $(basename "$STAGE_BASE")"
wp --path="$STAGING_DOCROOT" db export - 2>>"$LOG" | gzip > "$STAGE_BASE" \
  || fail "Staging DB dump failed."
[ -s "$STAGE_BASE" ] || fail "Staging DB dump empty."

# ---- live uploads (files not in git) ----
UP_BASE="$BACKUP_DIR/live-uploads-$TS.tar.gz"
say "Tarballing live uploads -> $(basename "$UP_BASE")"
tar -czf "$UP_BASE" -C "$LIVE_DOCROOT/wp-content" uploads >>"$LOG" 2>&1 \
  || fail "Live uploads tarball failed."
[ -s "$UP_BASE" ] || fail "Live uploads tarball empty."

# ---- retention: keep newest RETENTION per target ----
for prefix in live-db- staging-db- live-uploads-; do
  # shellcheck disable=SC2012
  ls -1t "$BACKUP_DIR"/"$prefix"*.sql.gz "$BACKUP_DIR"/"$prefix"*.tar.gz 2>/dev/null \
    | tail -n +$((RETENTION + 1)) \
    | while read -r old; do
        say "Pruning old backup: $(basename "$old")"
        rm -f "$old"
      done
done

# ---- disk check ----
USED_PCT=$(df -h "$DEPLOY" | awk 'NR==2 {print $5}' | tr -d '%')
say "Backup complete. Disk used: ${USED_PCT}% of /home."
echo "backup OK - $TS" >> "$LOG"
