#!/bin/bash
# promote.sh - Push staging -> live (code + DB). Invoked by the "Go Live" button on staging.
# Usage: bash ~/deploy/promote.sh [1]   1 = dry-run / preflight only
set -u

DEPLOY="$HOME/deploy"
LOG="$DEPLOY/promote.log"
STATUS="$DEPLOY/promote.status"
LOCK="$DEPLOY/promote.lock"
DRY="${1:-0}"

if [ ! -f "$DEPLOY/staging.env" ] || [ ! -f "$DEPLOY/live.env" ]; then
  echo '{"status":"failed","message":"Missing staging.env or live.env in ~/deploy"}' > "$STATUS"
  exit 1
fi
source "$DEPLOY/staging.env"
source "$DEPLOY/live.env"

# ---- concurrency lock ----
if [ -f "$LOCK" ]; then
  echo '{"status":"running","message":"A promote is already running."}' > "$STATUS"
  exit 0
fi
touch "$LOCK"

TMP_SQL=""
cleanup() {
  rm -f "$LOCK"
  [ -n "$TMP_SQL" ] && rm -f "$TMP_SQL"
}
trap cleanup EXIT

say()    { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" >> "$LOG"; }
fail()   { say "FAIL: $1"; echo "{\"status\":\"failed\",\"message\":\"$1\"}" > "$STATUS"; exit 1; }
finish() { say "DONE: $1"; echo "{\"status\":\"done\",\"message\":\"$1\",\"finished\":\"$(date '+%Y-%m-%d %H:%M:%S')\"}" > "$STATUS"; exit 0; }

# ---- data-loss guard metrics: echo "total_count newest_ts" for a DB ----
# Signals: WooCommerce orders (HPOS + legacy posts) and PMPro memberships/orders.
# Missing tables (fresh DB) are treated as 0 so the first Go Live always passes.
guard_metrics() { # $1=db $2=user $3=pass
  local DB="$1" U="$2" P="$3"
  local cnt=0 ts=0 out
  for q in \
    "SELECT COUNT(*) FROM wp_wc_orders" \
    "SELECT COALESCE(MAX(UNIX_TIMESTAMP(date_created_gmt)),0) FROM wp_wc_orders" \
    "SELECT COUNT(*) FROM wp_posts WHERE post_type='shop_order'" \
    "SELECT COALESCE(MAX(UNIX_TIMESTAMP(post_date_gmt)),0) FROM wp_posts WHERE post_type='shop_order'" \
    "SELECT COUNT(*) FROM wp_pmpro_memberships_users" \
    "SELECT COALESCE(MAX(UNIX_TIMESTAMP(modified)),0) FROM wp_pmpro_memberships_users" \
    "SELECT COUNT(*) FROM wp_pmpro_membership_orders" \
    "SELECT COALESCE(MAX(UNIX_TIMESTAMP(timestamp)),0) FROM wp_pmpro_membership_orders"; do
    out=$(MYSQL_PWD="$P" mysql -h localhost -u "$U" -N -e "$q" "$DB" 2>/dev/null)
    if [[ "$q" == *"COUNT(*)"* ]]; then
      [ -n "$out" ] && cnt=$((cnt + out))
    else
      [ -n "$out" ] && [ "$out" -gt "$ts" ] && ts=$out
    fi
  done
  echo "$cnt $ts"
}

say "===== promote.sh (dry=$DRY) start ====="

# ---- preflight: live DB reachable ----
say "Preflight: live DB $LIVE_DB_NAME reachable?"
if ! MYSQL_PWD="$LIVE_DB_PASS" mysql -h localhost -u "$LIVE_DB_USER" -e "SELECT 1" "$LIVE_DB_NAME" >/dev/null 2>&1; then
  say "Preflight FAIL: live DB $LIVE_DB_NAME not reachable for user $LIVE_DB_USER."
  echo "{\"status\":\"preflight-fail\",\"message\":\"Live DB '$LIVE_DB_NAME' not reachable. Create the DB + user '$LIVE_DB_USER' in cPanel > MySQL Databases first, then try again.\"}" > "$STATUS"
  exit 1
fi
say "Preflight OK: live DB reachable."

# ---- data-loss guard: block if live has orders/memberships newer than staging ----
# After launch, live accumulates transactions; promote would overwrite them. Requires the
# operator to run pull.sh first (live -> staging) so staging has everything live has.
LIVE_MET=$(guard_metrics "$LIVE_DB_NAME" "$LIVE_DB_USER" "$LIVE_DB_PASS")
STAGE_MET=$(guard_metrics "$STAGING_DB_NAME" "$STAGING_DB_USER" "$STAGING_DB_PASS")
LIVE_CNT=$(echo "$LIVE_MET" | awk '{print $1}'); LIVE_TS=$(echo "$LIVE_MET" | awk '{print $2}')
STAGE_CNT=$(echo "$STAGE_MET" | awk '{print $1}'); STAGE_TS=$(echo "$STAGE_MET" | awk '{print $2}')
say "Data guard: live orders/members count=$LIVE_CNT newest_ts=$LIVE_TS | staging count=$STAGE_CNT newest_ts=$STAGE_TS"
if [ "$LIVE_CNT" -gt "$STAGE_CNT" ] || [ "$LIVE_TS" -gt "$STAGE_TS" ]; then
  fail "DATA GUARD: live has orders/memberships newer than staging (live $LIVE_CNT/$LIVE_TS vs staging $STAGE_CNT/$STAGE_TS). Promoting would wipe live transactions. Run bash ~/deploy/pull.sh first to pull live content into staging, then promote."
fi
say "Data guard OK: no live-only transactions to protect."

if [ "$DRY" = "1" ]; then
  if grep -q 'SMOKE TEST: PASS' "$DEPLOY/deploy.log" 2>/dev/null; then
    say "Preflight OK: last staging smoke was PASS."
  else
    say "WARN: no 'SMOKE TEST: PASS' found in deploy.log yet."
  fi
  echo '{"status":"done","message":"Dry-run OK. Live DB reachable. Ready for Go Live."}' > "$STATUS"
  say "Dry-run complete."
  exit 0
fi

# ---- real promote ----
if ! grep -q 'SMOKE TEST: PASS' "$DEPLOY/deploy.log" 2>/dev/null; then
  fail "Last staging smoke test was not PASS. Deploy a green staging build first."
fi

# ---- backups ----
TS=$(date +%Y%m%d-%H%M%S)
mkdir -p "$DEPLOY/backups"
say "Backing up current live files -> backups/live-files-$TS.tar.gz"
tar -czf "$DEPLOY/backups/live-files-$TS.tar.gz" -C "$LIVE_DOCROOT" \
  --exclude=wp-content/uploads --exclude=wp-content/cache --exclude=wp-content/litespeed . 2>>"$LOG" \
  || say "WARN: live files tar backup incomplete."
say "Backing up current live DB -> backups/live-db-old-$TS.sql.gz"
wp --path="$LIVE_DOCROOT" db export - 2>>"$LOG" | gzip > "$DEPLOY/backups/live-db-old-$TS.sql.gz" \
  || say "WARN: could not dump old live DB."
if [ -f "$LIVE_DOCROOT/wp-config.php" ]; then
  cp "$LIVE_DOCROOT/wp-config.php" "$DEPLOY/backups/wp-config.live-old.$TS.php"
fi

# ---- install target live wp-config (points at new live DB, wp_ prefix) ----
say "Installing live wp-config."
cp "$DEPLOY/wp-config.live.php" "$LIVE_DOCROOT/wp-config.php" && chmod 600 "$LIVE_DOCROOT/wp-config.php" \
  || fail "Could not install live wp-config."

# ---- DB sync: staging -> live (before code push so the live hook smoke runs green) ----
TMP_SQL=$(mktemp "$DEPLOY/staging-export.XXXXXX.sql")
say "Exporting staging DB."
wp --path="$STAGING_DOCROOT" db export "$TMP_SQL" >>"$LOG" 2>&1 || fail "Staging DB export failed."
say "Importing into live DB."
wp --path="$LIVE_DOCROOT" db import "$TMP_SQL" >>"$LOG" 2>&1 || fail "Live DB import failed."
rm -f "$TMP_SQL"; TMP_SQL=""

say "Search-replace $STAGING_URL -> $LIVE_URL"
wp --path="$LIVE_DOCROOT" search-replace "$STAGING_URL" "$LIVE_URL" \
  --all-tables --precise --recurse-objects --skip-columns=guid >>"$LOG" 2>&1 \
  || fail "Search-replace failed."

# ---- PMPro: re-point known domain to live so its services stay active (no
#      "Site URL Change Detected" nag / paused crons on live). Same effect as
#      clicking PMPro's "Update my primary domain and reactivate all services."
wp --path="$LIVE_DOCROOT" option update pmpro_last_known_url "$LIVE_URL" >>"$LOG" 2>&1 || true
say "PMPro known domain set to $LIVE_URL."

# ---- deactivate go-live button on live (it ships active via the staging DB but
#      must never run there; the plugin self-limits, this is belt-and-suspenders) ----
wp --path="$LIVE_DOCROOT" plugin deactivate gabochie-go-live >>"$LOG" 2>&1 || true
say "gabochie-go-live deactivated on live (as required)."

# ---- code deploy ----
say "Pushing code to live.git (may be up-to-date)."
git --git-dir="$DEPLOY/gabochiedesign.git" push live main >>"$LOG" 2>&1 || say "WARN: git push live."
say "Checking out code into $LIVE_DOCROOT."
git --git-dir="$DEPLOY/live.git" --work-tree="$LIVE_DOCROOT" checkout -f main >>"$LOG" 2>&1 \
  || fail "git checkout into live docroot failed."
git --git-dir="$DEPLOY/live.git" --work-tree="$LIVE_DOCROOT" clean -fdq >>"$LOG" 2>&1 \
  || say "WARN: git clean reported issues."
say "Code deployed."

# ---- uploads sync (additive; preserves any extra live uploads) ----
say "Syncing uploads: staging -> live (additive)."
mkdir -p "$LIVE_DOCROOT/wp-content"
cp -a "$STAGING_DOCROOT/wp-content/uploads/." "$LIVE_DOCROOT/wp-content/uploads/" >>"$LOG" 2>&1 \
  || say "WARN: uploads copy incomplete."
find "$LIVE_DOCROOT/wp-content/uploads" -type d -exec chmod 755 {} + 2>>"$LOG"
find "$LIVE_DOCROOT/wp-content/uploads" -type f -exec chmod 644 {} + 2>>"$LOG"
say "Uploads synced."

say "Flushing caches + rewrites."
wp --path="$LIVE_DOCROOT" cache flush >>"$LOG" 2>&1 || true
wp --path="$LIVE_DOCROOT" rewrite flush >>"$LOG" 2>&1 || true

# ---- smoke test ----
CB=$(date +%s)
ALL_OK=1
for path in "/" "/blog/" "/wp-login.php" "/wp-json/"; do
  code=$(curl -sk -o /dev/null -w '%{http_code}' --max-time 30 "${LIVE_URL}${path}?cb=$CB")
  say "Smoke ${path} -> $code"
  [ "$code" = "200" ] || ALL_OK=0
done
IMG=$(curl -sk --max-time 30 "$LIVE_URL/?cb=$CB" \
  | grep -oE 'src="[^"]+"' | head -1 | sed -E 's/src="([^"]+)"/\1/')
if [ -n "$IMG" ]; then
  case "$IMG" in http://*|https://*) : ;; *) IMG="${LIVE_URL}${IMG}" ;; esac
  icode=$(curl -sk -o /dev/null -w '%{http_code}' --max-time 30 "$IMG")
  say "Smoke first image -> $icode ($IMG)"
  [ "$icode" = "200" ] || ALL_OK=0
else
  say "Smoke first image -> no image found on homepage"
  ALL_OK=0
fi
if [ "$ALL_OK" = "1" ]; then
  finish "Go Live complete. Code + DB + uploads synced to $LIVE_URL, smoke test passed."
else
  fail "Live smoke test FAILED after deploy - check $LOG."
fi
