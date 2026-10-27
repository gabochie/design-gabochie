#!/bin/bash
# smoke-test.sh — post-deploy verification for staging or live.
#
# Usage:
#   bash lib/smoke-test.sh live
#   bash lib/smoke-test.sh staging
#   bash lib/smoke-test.sh --url https://example.com
#
# Exit 0 only when every mandatory check passes. Designed to be safe to run
# against production: read-only HTTP requests, no writes, no cache purges.
set -uo pipefail

TARGET="live"
CUSTOM_URL=""

while [ $# -gt 0 ]; do
  case "$1" in
    staging|live) TARGET="$1"; shift ;;
    --url) CUSTOM_URL="${2:-}"; shift 2 ;;
    -h|--help) sed -n '2,10p' "$0"; exit 0 ;;
    *) echo "unknown argument: $1" >&2; exit 2 ;;
  esac
done

if [ -n "$CUSTOM_URL" ]; then
  SITE_URL="${CUSTOM_URL%/}"
  TARGET="custom"
elif [ "$TARGET" = "staging" ]; then
  SITE_URL="https://wp.design.gabochie.com"
else
  SITE_URL="https://design.gabochie.com"
fi

FAILURES=0
CHECKS=0

pass() { CHECKS=$((CHECKS + 1)); printf 'PASS  %s\n' "$*"; }
fail() { CHECKS=$((CHECKS + 1)); FAILURES=$((FAILURES + 1)); printf 'FAIL  %s\n' "$*"; }
warn() { printf 'WARN  %s\n' "$*"; }

# --- helpers -------------------------------------------------------------

http_code() { curl -s -o /dev/null -w '%{http_code}' -L --max-time 40 "$1" 2>/dev/null || echo 000; }
http_code_noredirect() { curl -s -o /dev/null -w '%{http_code}' --max-time 40 "$1" 2>/dev/null || echo 000; }
ttfb() { curl -s -o /dev/null -w '%{time_starttransfer}' -L --max-time 40 "$1" 2>/dev/null || echo 0; }
body() { curl -s -L --max-time 40 "$1" 2>/dev/null || true; }

expect_200() { # <label> <url>
  local code
  code="$(http_code "$2")"
  if [ "$code" = "200" ]; then pass "$1 -> $code"; else fail "$1 -> $code (want 200)"; fi
}

expect_redirect_or_200() { # <label> <url>
  local code
  code="$(http_code_noredirect "$2")"
  case "$code" in
    200|301|302) pass "$1 -> $code" ;;
    *) fail "$1 -> $code (want 200/301/302)" ;;
  esac
}

echo "== smoke test: $TARGET ($SITE_URL) =="

# --- core pages ----------------------------------------------------------
expect_200 "homepage" "$SITE_URL/"
expect_200 "blog index" "$SITE_URL/blog/"
expect_redirect_or_200 "wp-login" "$SITE_URL/wp-login.php"

# --- REST API ------------------------------------------------------------
expect_200 "REST root" "$SITE_URL/wp-json/"
expect_200 "REST posts" "$SITE_URL/wp-json/wp/v2/posts?per_page=1"

USERS_ME="$(http_code "$SITE_URL/wp-json/wp/v2/users/me")"
if [ "$USERS_ME" = "401" ]; then
  pass "REST users/me unauthenticated -> 401"
else
  fail "REST users/me unauthenticated -> $USERS_ME (want 401)"
fi

# --- SEO / machine-readable ---------------------------------------------
expect_200 "robots.txt" "$SITE_URL/robots.txt"
expect_200 "sitemap" "$SITE_URL/sitemap_index.xml"

# --- content sanity ------------------------------------------------------
HOME_HTML="$(body "$SITE_URL/")"
if [ -z "$HOME_HTML" ]; then
  fail "homepage body is empty"
else
  pass "homepage body returned ($(printf '%s' "$HOME_HTML" | wc -c | tr -d ' ') bytes)"
  if printf '%s' "$HOME_HTML" | grep -qiE 'Fatal error|Parse error|There has been a critical error'; then
    fail "PHP error text found on homepage"
  else
    pass "no PHP fatal/parse error text on homepage"
  fi
  if printf '%s' "$HOME_HTML" | grep -qiE '(^|[^a-z])localhost(:[0-9]+)?([^a-z]|$)'; then
    fail "localhost reference in homepage HTML"
  else
    pass "no localhost reference in homepage HTML"
  fi
fi

# --- first image asset ---------------------------------------------------
IMG="$(printf '%s' "$HOME_HTML" | grep -oE 'src="[^"]+\.(jpg|jpeg|png|webp|avif|svg)[^"]*"' | head -1 | sed -E 's/^src="([^"]+)"/\1/')"
if [ -n "$IMG" ]; then
  case "$IMG" in
    http://*|https://*) IMG_URL="$IMG" ;;
    //*) IMG_URL="https:${IMG}" ;;
    /*) IMG_URL="$SITE_URL${IMG}" ;;
    *) IMG_URL="$SITE_URL/${IMG}" ;;
  esac
  expect_200 "first image" "$IMG_URL"
else
  warn "no image found on homepage (not fatal)"
fi

# --- TLS / transport -----------------------------------------------------
if [ "${SITE_URL#https://}" != "$SITE_URL" ]; then
  TLS_CODE="$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "$SITE_URL/" 2>/dev/null || echo 000)"
  if [ "$TLS_CODE" = "200" ]; then pass "TLS handshake + HTTP 200"; else fail "TLS check -> $TLS_CODE"; fi
fi

# --- latency guard -------------------------------------------------------
START="$(ttfb "$SITE_URL/")"
if awk "BEGIN { exit !($START > 0 && $START < 5) }"; then
  pass "homepage TTFB ${START}s"
elif awk "BEGIN { exit !($START >= 5) }"; then
  fail "homepage TTFB ${START}s (>= 5s)"
else
  warn "homepage TTFB unavailable"
fi

# --- API health used by the deploy dashboard ----------------------------
STATUS_CODE="$(http_code "$SITE_URL/wp-json/gabochie/v1/deploy-status")"
case "$STATUS_CODE" in
  200) pass "deploy-status endpoint available" ;;
  401|403) pass "deploy-status endpoint protected -> $STATUS_CODE" ;;
  *) fail "deploy-status endpoint -> $STATUS_CODE" ;;
esac

echo "== $TARGET: $((CHECKS - FAILURES))/$CHECKS passed =="

if [ "$FAILURES" -gt 0 ]; then
  echo "SMOKE TEST: FAIL ($FAILURES)"
  exit 1
fi

echo "SMOKE TEST: PASS"
exit 0
