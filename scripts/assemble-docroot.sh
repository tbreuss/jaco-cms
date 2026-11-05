#!/bin/sh
# Assembles a throw-away web root in .docroot/ for tests that need a real server
# without Docker: the site from website/ (with jaco-data/ and media/) plus jaco/.
# Jaco derives its web root from the location of jaco/, so the application has to
# sit inside the directory that is served; a symlink would not do, PHP resolves it.
#
#   scripts/assemble-docroot.sh
#   php -S 127.0.0.1:8899 -t .docroot
set -eu

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DOCROOT="$ROOT/.docroot"

rm -rf "$DOCROOT"
mkdir -p "$DOCROOT"

cp -a "$ROOT/website/." "$DOCROOT/"
(cd "$ROOT" && tar -c \
    --exclude='jaco/test/e2e/node_modules' \
    --exclude='jaco/test/e2e/playwright-report' \
    --exclude='jaco/test/e2e/test-results' \
    --exclude='jaco/test/runtime' \
    jaco) | tar -x -C "$DOCROOT"
