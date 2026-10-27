#!/bin/bash
# migrate-domain.sh — one-time migration gabochiedesign.com -> design.gabochie.com (2026-10).
# Run ON THE CPANEL HOST as gabozvqd, bash ~/deploy/migrate-domain.sh [--apply].
# Read-only (checks only) by default; --apply executes the DB/config changes.
#
# Prerequisites that must exist BEFORE --apply:
#   1. gabochiedesign.com renewed at Namecheap (needed for the 301 redirect).
#   2. cPanel: subdomains design.gabochie.com (ALIAS of public_html) and
#      wp.design.gabochie.com created; or design can be a new docroot copy.
#   3. DNS A/AAAA or CNAME records for design. and wp.design. -> cPanel IP.
#   4. AutoSSL/Let's Encrypt certificates issued for the new hostnames.
#   5. Old-domain mailboxes: nothing needed; new sends use design.gabochie.com
#      addresses (no-reply@gabochie.com family uses Zoho if so configured).
set -euo pipefail
export HOME=/home/gabozvqd
export PATH=/usr/local/sbin:/usr/local/bin:/usr/bin:/bin

BASE="$HOME"
OLD_STAGING="$BASE/wp.gabochiedesign.com"
NEW_STAGING="$BASE/wp.design.gabochie.com"
LIVE_DOCROOT="$BASE/public_html"
APPLY=0
[ "${1:-}" = "--apply" ] && APPLY=1

ok()   { echo "ok   : $*"; }
warn() { echo "WARN : $*"; }
die()  { echo "FAIL : $*"; exit 1; }

echo "== migrate-domain.sh (apply=$APPLY) =="

# --- Preflight: DNS for new hosts -------------------------------------------
for host in design.gabochie.com wp.design.gabochie.com; do
  if getent hosts "$host" | grep -q "^[0-9a-fA-F:.]" ; then
    ip="$(getent hosts "$host" | awk '{print $1; exit}')"
    myip="$(hostname -I | awk '{print $1}')"
    if [ "$ip" = "$myip" ]; then ok "$host -> $ip (this server)"
    else warn "$host -> $ip (not this server? expected $myip)"; fi
  else
    die "$host does not resolve yet — create the DNS record in gabochie.com zone first"
  fi
done

wp() { command wp "$@" --allow-root; }

# --- Live: flip canonical URLs in wp-config + DB ----------------------------
# design.gabochie.com is an ALIAS of public_html: same docroot, same code path.
if [ -f "$LIVE_DOCROOT/wp-config.php" ]; then
  cur="$(wp option get siteurl --path="$LIVE_DOCROOT" --format=json 2>/dev/null || echo '?')"
  echo "live siteurl: $cur"
  if [ "$APPLY" = "1" ]; then
    # Safety: dump the DB before touching it.
    dump="$BASE/deploy/backups/pre-domain-$(date +%Y%m%d-%H%M%S).sql"
    mkdir -p "$(dirname "$dump")"
    wp db export --path="$LIVE_DOCROOT" --skip-themes --skip-plugins "$dump" \
      && chmod 600 "$dump" && echo "ok: DB dump saved to $dump" || die "db dump failed — aborting"
    sed -i "s|define( *'WP_HOME'.*|define( 'WP_HOME', 'https://design.gabochie.com' );|" \
      "$LIVE_DOCROOT/wp-config.php" || true
    sed -i "s|define( *'WP_SITEURL'.*|define( 'WP_SITEURL', 'https://design.gabochie.com' );|" \
      "$LIVE_DOCROOT/wp-config.php" || true
    wp search-replace 'https://gabochiedesign.com' 'https://design.gabochie.com' \
      --path="$LIVE_DOCROOT" --precise --report
    wp search-replace 'wp.gabochiedesign.com' 'wp.design.gabochie.com' \
      --path="$LIVE_DOCROOT" --precise --report
    wp option update home 'https://design.gabochie.com' --path="$LIVE_DOCROOT"
    wp option update siteurl 'https://design.gabochie.com' --path="$LIVE_DOCROOT"
    wp rewrite flush --path="$LIVE_DOCROOT"
    # Purge LiteSpeed cache so old-domain cached pages don't leak stale URLs.
    touch "$LIVE_DOCROOT/.litespeed_flag" 2>/dev/null || true
    wp litespeed-option set 'cio' 2>/dev/null || true
    ok "live DB + config flipped to design.gabochie.com"
  else
    echo "live: dry-run (use --apply to flip)"
  fi
else
  warn "no wp-config in $LIVE_DOCROOT"
fi

# --- Staging: move docroot ~/wp.gabochiedesign.com -> ~/wp.design.gabochie.com
if [ ! -d "$NEW_STAGING" ] && [ -d "$OLD_STAGING" ]; then
  if [ "$APPLY" = "1" ]; then
    cp -a "$OLD_STAGING" "$NEW_STAGING"
    sed -i "s|define( *'WP_HOME'.*|define( 'WP_HOME', 'https://wp.design.gabochie.com' );|" \
      "$NEW_STAGING/wp-config.php" || true
    sed -i "s|define( *'WP_SITEURL'.*|define( 'WP_SITEURL', 'https://wp.design.gabochie.com' );|" \
      "$NEW_STAGING/wp-config.php" || true
    wp search-replace 'https://wp.gabochiedesign.com' 'https://wp.design.gabochie.com' \
      --path="$NEW_STAGING" --precise --report
    wp option update home 'https://wp.design.gabochie.com' --path="$NEW_STAGING"
    wp option update siteurl 'https://wp.design.gabochie.com' --path="$NEW_STAGING"
    ok "staging copied to $NEW_STAGING and re-homed"
    echo "NOTE: keep $OLD_STAGING until verified, then remove: rm -rf $OLD_STAGING"
  else
    echo "staging: dry-run (would copy $OLD_STAGING -> $NEW_STAGING)"
  fi
else
  ok "staging docroot already migrated or source missing"
fi

# --- Old-domain 301 redirect in public_html/.htaccess -----------------------
MARKER="# BEGIN gabochie-domain-redirect"
if ! grep -qF "$MARKER" "$LIVE_DOCROOT/.htaccess" 2>/dev/null; then
  if [ "$APPLY" = "1" ]; then
    cp "$LIVE_DOCROOT/.htaccess" "$LIVE_DOCROOT/.htaccess.pre-domain-migration"
    cat >> "$LIVE_DOCROOT/.htaccess" <<'HT'

# BEGIN gabochie-domain-redirect
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{HTTP_HOST} ^(www\.)?gabochiedesign\.com$ [NC]
RewriteRule ^(.*)$ https://design.gabochie.com%{REQUEST_URI} [R=301,L,QSA]
</IfModule>
# END gabochie-domain-redirect
HT
    ok "301 redirect appended to .htaccess (backup: .htaccess.pre-domain-migration)"
  else
    echo "redirect: dry-run (would append 301 block to $LIVE_DOCROOT/.htaccess)"
  fi
else
  ok ".htaccess redirect block already present"
fi

# --- Post-steps that cannot be automated from the shell ---------------------
cat <<'NOTE'

== Manual follow-ups after --apply ==
 1. GitHub repo -> Settings -> Webhooks: repoint the URL to
    https://design.gabochie.com/webhook.php  (same secret).
 2. cPanel -> Domains: create the 301 route if Alias not used, and confirm
    TLS covers design.gabochie.com and wp.design.gabochie.com (AutoSSL).
 3. Email: create mailboxes no-reply@gabochie.com / design.gabochie.com sends
    per gabochie-go-live checklist; update Zoho/SPF/DKIM for gabochie.com.
 4. Verify: bash ~/deploy/lib/smoke-test.sh live && bash ~/deploy/healthcheck.sh --human
 5. Push domain-migration branch -> staging QA -> auto-promote to live.
 6. Once verified: rm -rf ~/wp.gabochiedesign.com  (old staging docroot).
NOTE
