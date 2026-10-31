#!/bin/sh
# Download the JS dependencies as single files / trimmed trees.
# Runs after composer install/update. No npm required.
set -eu

BASE="frontend/vendor"
mkdir -p "$BASE/mithril" "$BASE/quill" "$BASE/ace-builds/src-min"

# Mithril (minified)
curl -fsSL "https://unpkg.com/mithril@2.3.8/mithril.min.js" -o "$BASE/mithril/mithril.min.js"

# Quill (minified)
curl -fsSL "https://unpkg.com/quill@1.3.7/dist/quill.min.js" -o "$BASE/quill/quill.min.js"

# Ace: download the npm tarball and extract only the files Jaco needs.
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
curl -fsSL "https://registry.npmjs.org/ace-builds/-/ace-builds-1.5.3.tgz" -o "$TMP/ace.tgz"
tar -xzf "$TMP/ace.tgz" -C "$TMP" \
    package/src-min/ace.js \
    package/src-min/mode-html.js \
    package/src-min/mode-css.js \
    package/src-min/theme-github.js \
    package/src-min/theme-tomorrow_night.js \
    package/src-min/worker-html.js \
    package/src-min/worker-css.js
for f in ace.js mode-html.js mode-css.js theme-github.js theme-tomorrow_night.js worker-html.js worker-css.js; do
    mv "$TMP/package/src-min/$f" "$BASE/ace-builds/src-min/$f"
done

# QUnit is dev-only: download only when dev packages are present.
if [ -d "vendor/phpunit" ]; then
    mkdir -p "frontend/vendor/qunit"
    curl -fsSL "https://unpkg.com/qunit@2.26.0/qunit/qunit.js" -o "frontend/vendor/qunit/qunit.js"
    curl -fsSL "https://unpkg.com/qunit@2.26.0/qunit/qunit.css" -o "frontend/vendor/qunit/qunit.css"
fi
