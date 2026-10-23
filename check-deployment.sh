#!/bin/sh
# Checks a running Jaco installation over HTTP: protection of the internals,
# the API, operator data, the public pages and - after the go-live - that
# indexing is no longer blocked.
#
#   ./check-deployment.sh https://example.com
#   ./check-deployment.sh https://example.com --expect-indexed
#
# Exit code 0 = every check passed, 1 = at least one failed.
#
# The internal checks rely on .htaccess, so run this against Apache or nginx.
# The PHP built-in server ignores those files and would report false failures.
set -u

BASE="${1:-}"
MODE="${2:-}"

if [ -z "$BASE" ]; then
  echo "usage: $0 <base-url> [--expect-indexed]" >&2
  exit 2
fi

BASE="${BASE%/}"
FAILED=0
PASSED=0

# check <erwarteter-code> <pfad> <beschreibung>
check() {
  expected="$1"
  path="$2"
  label="$3"
  actual="$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 "$BASE$path")"
  if [ "$actual" = "$expected" ]; then
    printf 'ok   %-3s %-45s %s\n' "$actual" "$path" "$label"
    PASSED=$((PASSED + 1))
  else
    printf 'FAIL %-3s (expected %s) %-30s %s\n' "$actual" "$expected" "$path" "$label" >&2
    FAILED=$((FAILED + 1))
  fi
}

# check_json <pfad> <beschreibung>: erwartet 200 und eine JSON-Antwort
check_json() {
  path="$1"
  label="$2"
  actual="$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 "$BASE$path")"
  body="$(curl -s --max-time 15 "$BASE$path" | head -c 1)"
  if [ "$actual" = "200" ] && [ "$body" = "{" ]; then
    printf 'ok   200 %-45s %s\n' "$path" "$label"
    PASSED=$((PASSED + 1))
  else
    printf 'FAIL %-3s (expected 200 JSON) %-27s %s\n' "$actual" "$path" "$label" >&2
    FAILED=$((FAILED + 1))
  fi
}

echo "target: $BASE"
echo
echo "internals must be unreachable"
check 403 "/jaco/.htaccess"                 "application deny file"
check 403 "/jaco/settings.php"              "credentials"
check 403 "/jaco/cli.php"                   "cli entry"
check 403 "/jaco/install.php"               "installer"
check 403 "/jaco/backend/.htaccess"         "source deny file"
check 403 "/jaco/language/.htaccess"        "translations deny file"
check 403 "/jaco/vendor/autoload.php"       "third party code"
check 403 "/jaco-data/"                     "operator data"
check 403 "/jaco-data/templates/"           "templates"
check 403 "/jaco-data/backups/"             "backups"

echo
echo "admin entry points must be reachable"
check 200 "/jaco/"                          "admin shell"
check 200 "/jaco/index.html"                "admin shell document"
check 200 "/jaco/frontend/app.js"           "admin script"
check 200 "/jaco/frontend/asset/app.css"    "admin stylesheet"

echo
echo "api must answer"
check_json "/jaco/api.php?action=translations" "translations"
check 401 "/jaco/api.php?action=page/list"  "unauthenticated write/read route"
check 404 "/jaco/api.php?action=does-not-exist" "unknown route"

echo
echo "public site must be reachable"
check 200 "/"                               "start page"
check 200 "/features.html"                  "features"
check 200 "/docs.html"                      "docs"
check 200 "/de/"                            "start page (de)"
check 200 "/robots.txt"                     "robots"
check 200 "/sitemap.xml"                    "sitemap"
check 404 "/this-page-does-not-exist"       "missing page"

echo
echo "media must serve files, never execute"
check 403 "/media/.htaccess"                "media deny file"

if [ "$MODE" = "--expect-indexed" ]; then
  echo
  echo "indexing must be allowed (go-live)"
  header="$(curl -s -I --max-time 15 "$BASE/" | tr -d '\r' | grep -i '^x-robots-tag:' || true)"
  if [ -z "$header" ]; then
    printf 'ok        %-45s %s\n' "X-Robots-Tag" "absent"
    PASSED=$((PASSED + 1))
  else
    printf 'FAIL      %-45s %s\n' "X-Robots-Tag" "still set: $header" >&2
    FAILED=$((FAILED + 1))
  fi
fi

echo
echo "passed: $PASSED, failed: $FAILED"
[ "$FAILED" -eq 0 ]
