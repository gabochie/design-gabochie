#!/bin/bash
# qa.sh - read-only QA suite for Gabochie Design (staging or live).
# Deployed to ~/deploy/qa.sh via scp (NOT in the git repo, like the other deploy scripts).
# Run manually:  bash ~/deploy/qa.sh                  # staging by default
#                bash ~/deploy/qa.sh --target=live
#                bash ~/deploy/qa.sh --lenient        # demote debug.log non-fatal lines to WARN
#                bash ~/deploy/qa.sh --json           # also print a JSON summary
# Exit code: 0 all mandatory checks PASS, 1 any FAIL, 2 usage/environment error.
# Logs to ~/deploy/qa-<target>.log; on each webhook deploy the async runner may call this.

set -u
HOME=/home/gabozvqd
export PATH=/usr/local/sbin:/usr/local/bin:/usr/bin:/bin
export LC_ALL=C

TARGET=staging
SITE_URL=""
DOCROOT=""
LENIENT=0
JSON=0
for arg in "$@"; do
  case "$arg" in
    --target=*)  TARGET="${arg#--target=}" ;;
    --site-url=*) SITE_URL="${arg#--site-url=}" ;;
    --docroot=*)  DOCROOT="${arg#--docroot=}" ;;
    --lenient)    LENIENT=1 ;;
    --json)       JSON=1 ;;
    *) echo "Unknown arg: $arg (--target=staging|live --site-url= --docroot= --lenient --json)"; exit 2 ;;
  esac
done

STAGING_ROOT=/home/gabozvqd/wp.design.gabochie.com
LIVE_ROOT=/home/gabozvqd/public_html
case "$TARGET" in
  staging) DOCROOT="${DOCROOT:-$STAGING_ROOT}"; SITE_URL="${SITE_URL:-https://wp.design.gabochie.com}" ;;
  live)    DOCROOT="${DOCROOT:-$LIVE_ROOT}";    SITE_URL="${SITE_URL:-https://design.gabochie.com}" ;;
  *) echo "Unknown --target=$TARGET (staging|live)"; exit 2 ;;
esac

if [ ! -d "$DOCROOT/wp-content" ]; then
  echo "ERROR: no wp-content at $DOCROOT - is this the right docroot?"; exit 2
fi

LOG="$HOME/deploy/qa-$TARGET.log"
mkdir -p "$(dirname "$LOG")"
: > "$LOG"
# Print to stdout (SSH) AND append to the log file.
exec > >(tee "$LOG") 2>&1

PASS=0; FAIL=0; WARN=0
FAILURES=()

p(){ printf 'PASS  %s\n' "$*"; PASS=$((PASS+1)); }
f(){ printf 'FAIL  %s\n' "$*"; FAIL=$((FAIL+1)); FAILURES+=("$*"); }
w(){ printf 'WARN  %s\n' "$*"; WARN=$((WARN+1)); }

echo "======================================================"
echo "QA suite : $TARGET ($SITE_URL)"
echo "Docroot  : $DOCROOT"
echo "Date     : $(date -u '+%Y-%m-%d %H:%M:%S UTC')"
echo "======================================================"

http_code() { # url -> code (no redirect follow, so 301/302 are visible)
  curl -s -o /dev/null -w '%{http_code}' --max-time 40 "$1"
}

http_headers() { # url -> header block
  curl -s -I -L --max-time 40 "$1"
}

echo "## 1. HTTP STATUS MATRIX"
HOME_CODE=$(http_code "$SITE_URL/")
[ "$HOME_CODE" = "200" ] && p "homepage / -> $HOME_CODE" || f "homepage / -> $HOME_CODE (want 200)"

BLOG_CODE=$(http_code "$SITE_URL/blog/")
[ "$BLOG_CODE" = "200" ] && p "blog index /blog/ -> $BLOG_CODE" || f "blog index /blog/ -> $BLOG_CODE (want 200)"

SEARCH_CODE=$(http_code "$SITE_URL/?s=architecture")
[ "$SEARCH_CODE" = "200" ] && p "search /?s=architecture -> $SEARCH_CODE" || f "search -> $SEARCH_CODE (want 200)"

NOTFOUND_CODE=$(http_code "$SITE_URL/?p=987654321987")
if [ "$NOTFOUND_CODE" = "404" ]; then
  p "404 page -> $NOTFOUND_CODE"
else
  f "404 check -> $NOTFOUND_CODE (want 404, got $NOTFOUND_CODE)"
fi

LOGIN_CODE=$(http_code "$SITE_URL/wp-login.php")
[ "$LOGIN_CODE" = "200" ] && p "wp-login.php -> $LOGIN_CODE" || f "wp-login.php -> $LOGIN_CODE (want 200)"

ADMIN_CODE=$(http_code "$SITE_URL/wp-admin/")
case "$ADMIN_CODE" in
  301|302) p "wp-admin -> $ADMIN_CODE (redirects to login, expected)" ;;
  200)     p "wp-admin -> 200 (expected if allowed)" ;;
  *)       f "wp-admin -> $ADMIN_CODE (want 301/302)" ;;
esac

# single post + page from wp-cli
POST_URL=$(wp --path="$DOCROOT" post list --post_type=post --post_status=publish --posts_per_page=1 --field=url --allow-root 2>/dev/null | head -1)
if [ -n "$POST_URL" ]; then
  PC=$(http_code "$POST_URL")
  [ "$PC" = "200" ] && p "single post $POST_URL -> $PC" || f "single post $POST_URL -> $PC (want 200)"
else
  w "no published post found to test single template"
fi

PAGE_URL=$(wp --path="$DOCROOT" post list --post_type=page --post_status=publish --posts_per_page=1 --field=url --allow-root 2>/dev/null | head -1)
if [ -n "$PAGE_URL" ]; then
  PG=$(http_code "$PAGE_URL")
  case "$PG" in 200) p "single page $PAGE_URL -> $PG" ;; 301|302) p "single page $PAGE_URL -> $PG (redirect, acceptable)" ;; *) f "single page $PAGE_URL -> $PG (want 200)" ;; esac
else
  w "no published page found to test page template"
fi

# archives
CAT_SLUG=$(wp --path="$DOCROOT" term list category --field=slug --allow-root 2>/dev/null | head -1)
if [ -n "$CAT_SLUG" ]; then
  CAT_CODE=$(http_code "$SITE_URL/category/$CAT_SLUG/")
  [ "$CAT_CODE" = "200" ] && p "category archive /category/$CAT_SLUG/ -> $CAT_CODE" || f "category archive -> $CAT_CODE (want 200)"
else
  w "no categories to test archive template"
fi

TAG_SLUG=$(wp --path="$DOCROOT" term list post_tag --field=slug --allow-root 2>/dev/null | head -1)
if [ -n "$TAG_SLUG" ]; then
  TAG_CODE=$(http_code "$SITE_URL/tag/$TAG_SLUG/")
  [ "$TAG_CODE" = "200" ] && p "tag archive /tag/$TAG_SLUG/ -> $TAG_CODE" || f "tag archive -> $TAG_CODE (want 200)"
else
  w "no post tags to test tag template"
fi

PAG_CODE=$(http_code "$SITE_URL/page/2/")
[ "$PAG_CODE" = "200" ] && p "pagination /page/2/ -> $PAG_CODE" || w "pagination /page/2/ -> $PAG_CODE (only 1 page of posts is fine)"

if [ "$TARGET" = "staging" ]; then
  COMM_CODE=$(http_code "$SITE_URL/community/")
  [ "$COMM_CODE" = "200" ] && p "community /community/ -> $COMM_CODE" || f "community /community/ -> $COMM_CODE (want 200)"
fi

echo "## 2. SEO (robots / sitemap / canonical)"
ROB_CODE=$(http_code "$SITE_URL/robots.txt")
if [ "$ROB_CODE" = "200" ]; then
  p "robots.txt -> 200"
  if curl -s --max-time 30 "$SITE_URL/robots.txt" | grep -q '^Sitemap:'; then
    p "robots.txt declares sitemap"
  else
    w "robots.txt does not declare a Sitemap line"
  fi
else
  f "robots.txt -> $ROB_CODE (want 200)"
fi

SITEMAP_CODE=$(http_code "$SITE_URL/sitemap_index.xml")
if [ "$SITEMAP_CODE" = "200" ]; then
  p "sitemap_index.xml -> 200"
  if curl -s --max-time 30 "$SITE_URL/sitemap_index.xml" | grep -q '<loc>'; then
    p "sitemap_index.xml contains <loc> entries"
  else
    f "sitemap_index.xml is 200 but has no <loc> entries"
  fi
else
  f "sitemap_index.xml -> $SITEMAP_CODE (want 200)"
fi

HOMEPAGE_HTML=$(curl -s --max-time 40 "$SITE_URL/")
if printf '%s' "$HOMEPAGE_HTML" | grep -q '<link rel="canonical"'; then
  p "homepage has canonical link"
else
  f "homepage missing canonical link"
fi

if [ -n "$POST_URL" ]; then
  POST_HTML=$(curl -s --max-time 40 "$POST_URL")
  if printf '%s' "$POST_HTML" | grep -q '<link rel="canonical"'; then
    p "single post has canonical link"
  else
    w "single post missing canonical link"
  fi
  if printf '%s' "$POST_HTML" | grep -q '"@type":\s*"BreadcrumbList"\|"BreadcrumbList"'; then
    p "single post has breadcrumbs (Yoast BreadcrumbList schema)"
  else
    w "single post breadcrumbs not detected (no BreadcrumbList schema)"
  fi
fi

echo "## 3. DEBUG.LOG HYGIENE"
DEBUG_LOG="$DOCROOT/wp-content/debug.log"
if [ ! -f "$DEBUG_LOG" ]; then
  p "no debug.log present (WP_DEBUG off)"
else
  FATALS=$(grep -c 'PHP Fatal error' "$DEBUG_LOG" || true)
  REST=$(grep -cE 'PHP (Notice|Warning|Deprecated)|PHP Deprecated' "$DEBUG_LOG" || true)
  [ "$FATALS" -eq 0 ] && p "debug.log: 0 fatal errors" || f "debug.log: $FATALS fatal error(s)"
  if [ "$REST" -eq 0 ]; then
    p "debug.log: 0 notices/warnings/deprecated"
  elif [ "$LENIENT" = "1" ]; then
    w "debug.log: $REST notices/warnings/deprecated (lenient mode)"
  else
    f "debug.log: $REST notices/warnings/deprecated (use --lenient to demote)"
  fi
  w "debug.log size: $(du -h "$DEBUG_LOG" | cut -f1)"
fi

echo "## 4. LOCALHOST REFS IN CONTENT (DB)"
QA_WP="$HOME/deploy/qa-wp.php"
WP_OUT=$(wp --path="$DOCROOT" eval-file "$QA_WP" --allow-root 2>/dev/null)
LOCAL_SCAN=$(printf '%s\n' "$WP_OUT" | grep '^localhost:' || true)
if [ -z "$LOCAL_SCAN" ]; then
  w "wp eval-file localhost scan returned nothing (DB probe failed?)"
else
  while IFS= read -r line; do
    n="${line#*:}"; n="${n#*=}"
    src="${line#localhost:}"; src="${src%%=*}"
    if [ "$n" -gt 0 ] 2>/dev/null; then
      f "$n localhost refs in $src"
    else
      p "0 localhost refs in $src"
    fi
  done <<< "$LOCAL_SCAN"
fi

OPT_LOCAL=$(printf '%s\n' "$WP_OUT" | grep '^options_localhost=' | sed 's/^options_localhost=//')
if [ -n "$OPT_LOCAL" ]; then
  w "options containing 'localhost' (check manually, known-benign: litespeed.conf.object-host, pmpro_addons): $OPT_LOCAL"
else
  p "0 localhost refs in options"
fi

echo "## 5. REST API"
REST_CODE=$(http_code "$SITE_URL/wp-json/")
[ "$REST_CODE" = "200" ] && p "wp-json root -> 200" || f "wp-json root -> $REST_CODE (want 200)"
REST_POSTS=$(http_code "$SITE_URL/wp-json/wp/v2/posts?per_page=1")
[ "$REST_POSTS" = "200" ] && p "wp-json posts -> 200" || f "wp-json posts -> $REST_POSTS (want 200)"
USERS_ME=$(http_code "$SITE_URL/wp-json/wp/v2/users/me")
[ "$USERS_ME" = "401" ] && p "wp-json users/me unauthenticated -> 401 (auth enforced)" || f "wp-json users/me -> $USERS_ME (want 401 unauthenticated)"

MCP_CODE=$(http_code "$SITE_URL/wp-json/gabochie-ai/v1/mcp-status")
if [ "$MCP_CODE" = "200" ]; then
  MCP_BODY=$(curl -s --max-time 30 "$SITE_URL/wp-json/gabochie-ai/v1/mcp-status")
  if printf '%s' "$MCP_BODY" | grep -q 'mcp_active'; then
    p "gabochie-ai mcp-status -> 200 + valid JSON"
  else
    w "gabochie-ai mcp-status -> 200 but unexpected body"
  fi
else
  w "gabochie-ai mcp-status -> $MCP_CODE (plugin may be inactive on this target)"
fi

echo "## 6. UPLOADS PERMS + FIRST IMAGE"
DIRS_BAD=$(find "$DOCROOT/wp-content/uploads" -type d ! -perm -755 2>/dev/null | head -20)
FILES_BAD=$(find "$DOCROOT/wp-content/uploads" -type f ! -perm -644 2>/dev/null | head -20)
[ -z "$DIRS_BAD" ] && p "uploads dirs all 755" || f "uploads dirs not 755:\n$DIRS_BAD"
[ -z "$FILES_BAD" ] && p "uploads files all 644" || f "uploads files not 644:\n$FILES_BAD"

IMG_URL=$(wp --path="$DOCROOT" post list --post_type=attachment --post_status=inherit --posts_per_page=1 --field=url --allow-root 2>/dev/null | head -1)
if [ -n "$IMG_URL" ]; then
  IMG_CODE=$(curl -s -o /dev/null -w '%{http_code}' -L --max-time 40 "$IMG_URL")
  [ "$IMG_CODE" = "200" ] && p "first upload $IMG_URL -> $IMG_CODE (following redirect)" || f "first upload $IMG_URL -> $IMG_CODE (want 200 after redirects)"
else
  w "no attachments found to test uploads HTTP serving"
fi

echo "## 7. TIMING (homepage TTFB + total, 3 samples)"
for i in 1 2 3; do
  TIME_OUT=$(curl -s -o /dev/null --max-time 40 -w '%{time_starttransfer} %{time_total}' "$SITE_URL/")
  TTFB=$(echo "$TIME_OUT" | awk '{print $1}')
  TOTAL=$(echo "$TIME_OUT" | awk '{print $2}')
  p "sample $i: TTFB=${TTFB}s total=${TOTAL}s"
done

echo "## 8. OBJECT CACHE HEADER (LiteSpeed/Cloudflare)"
HDRS=$(http_headers "$SITE_URL/")
if printf '%s' "$HDRS" | grep -qi 'x-turbo-charged-by'; then
  p "object cache header x-turbo-charged-by present"
elif printf '%s' "$HDRS" | grep -qi 'x-litespeed-cache'; then
  p "LiteSpeed cache header present"
else
  w "no LiteSpeed/object-cache header detected on homepage (may be Cloudflare-fronted)"
fi

echo "## 9. PHP LINT (theme + our plugins)"
LINT_ERRORS=0
for dir in "$DOCROOT/wp-content/themes/gabochie-value" \
           "$DOCROOT/wp-content/plugins/gabochie-go-live" \
           "$DOCROOT/wp-content/plugins/gabochie-community" \
           "$DOCROOT/wp-content/plugins/gabochie-command-center" \
           "$DOCROOT/wp-content/plugins/gabochie-design-hub" \
           "$DOCROOT/wp-content/plugins/ghbc" \
           "$DOCROOT/wp-content/plugins/gabochie-sync"; do
  if [ -d "$dir" ]; then
    while IFS= read -r file; do
      if ! php -l "$file" > /dev/null 2>&1; then
        echo "  LINT FAIL: $file"
        LINT_ERRORS=$((LINT_ERRORS+1))
      fi
    done < <(find "$dir" -name '*.php' -type f)
  fi
done
[ "$LINT_ERRORS" -eq 0 ] && p "PHP lint clean (theme + our plugins)" || f "PHP lint: $LINT_ERRORS file(s) failed"

echo "## 10. MEMORY PROFILE"
MEM_LIMIT=$(printf '%s\n' "$WP_OUT" | grep '^memory:limit=' | sed 's/^memory:limit=//')
MEM_PEAK=$(printf '%s\n' "$WP_OUT" | grep '^memory:peak=' | sed 's/^memory:peak=//')
echo "  memory_limit=$MEM_LIMIT peak=$((MEM_PEAK / 1048576))MB (wp-cli process; page-level budget is a separate Lighthouse concern)"
p "memory reported (limit=$MEM_LIMIT)"

echo "## 11. BROKEN-LINK CRAWL (homepage internal links, max 60)"
LINKS=$(printf '%s' "$HOMEPAGE_HTML" \
  | grep -oE 'href="[^"]+"' \
  | sed -e 's/^href="//' -e 's/"$//' \
  | sed 's/^\/\//https:\/\//' \
  | sort -u)
COUNT=0; BROKEN=0
for link in $LINKS; do
  case "$link" in
    \#*|mailto:*|tel:*|javascript:*|*/cdn-cgi/*|*.css|*.js|*.png|*.jpg|*.jpeg|*.gif|*.svg|*.webp|*.ico|*.woff*|*.ttf|*.xml|*xmlrpc.php*) continue ;;
  esac
  if [ "$COUNT" -ge 60 ]; then break; fi
  if printf '%s' "$link" | grep -qE "^$SITE_URL|^/"; then
    URL="$link"
    case "$link" in
      /*) URL="$SITE_URL$link" ;;
    esac
    CODE=$(http_code "$URL")
    COUNT=$((COUNT+1))
    case "$CODE" in
      200|301|302|304) : ;;
      404|410) echo "  BROKEN $CODE $URL"; BROKEN=$((BROKEN+1)) ;;
      *) w "link $URL -> $CODE" ;;
    esac
  fi
done
[ "$BROKEN" -eq 0 ] && p "crawl: $COUNT internal links, 0 broken" || f "crawl: $COUNT internal links, $BROKEN broken"

echo "## 12. DB INTEGRITY (counts)"
POSTS=$(printf '%s\n' "$WP_OUT" | grep '^counts:posts=' | sed 's/^counts:posts=//')
PAGES=$(printf '%s\n' "$WP_OUT" | grep '^counts:pages=' | sed 's/^counts:pages=//')
PLUGINS=$(printf '%s\n' "$WP_OUT" | grep '^counts:plugins=' | sed 's/^counts:plugins=//')
echo "  published posts=$POSTS pages=$PAGES active plugins=$PLUGINS"
p "DB counts reported"

echo "======================================================"
echo "RESULT: $PASS pass, $FAIL fail, $WARN warn"
if [ "$FAIL" -gt 0 ]; then
  echo "FAILURES:"
  for i in "${FAILURES[@]}"; do echo "  - $i"; done
  echo "QA $TARGET: FAIL"
  [ "$JSON" = "1" ] && printf '{"target":"%s","date":"%s","pass":%d,"fail":%d,"warn":%d,"failures":[%s]}\n' "$TARGET" "$(date -u '+%Y-%m-%dT%H:%M:%SZ')" "$PASS" "$FAIL" "$WARN" "$(printf '"%s",' "${FAILURES[@]}" | sed 's/,$//')"
  exit 1
fi
echo "QA $TARGET: PASS"
[ "$JSON" = "1" ] && printf '{"target":"%s","date":"%s","pass":%d,"fail":%d,"warn":%d}\n' "$TARGET" "$(date -u '+%Y-%m-%dT%H:%M:%SZ')" "$PASS" "$FAIL" "$WARN"
exit 0
