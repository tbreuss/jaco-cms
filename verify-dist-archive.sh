#!/bin/sh
# Prüft ein jaco-dist-*.zip auf erwartete Release-Inhalte.
#   ./verify-dist-archive.sh [path/to/archive.zip]
# Default: jaco-dist-dev.zip im Repository-Root.
set -eu

ROOT="$(cd "$(dirname "$0")" && pwd)"
ZIP="${1:-$ROOT/jaco-dist-dev.zip}"

command -v unzip >/dev/null || { echo "unzip fehlt" >&2; exit 1; }

if [ ! -f "$ZIP" ]; then
  echo "Archiv nicht gefunden: $ZIP" >&2
  exit 1
fi

LIST="$(mktemp)"
trap 'rm -f "$LIST"' EXIT INT TERM
unzip -l "$ZIP" > "$LIST"

must_contain() {
  if ! grep -Fq "$1" "$LIST"; then
    echo "dist check failed: missing $1 in $ZIP" >&2
    exit 1
  fi
}

must_not_contain() {
  if grep -Fq "$1" "$LIST"; then
    echo "dist check failed: must not ship $1 in $ZIP" >&2
    exit 1
  fi
}

must_contain " INSTALL.md"
must_contain " LICENSE"
must_contain " jaco/vendor/autoload.php"
must_contain " jaco/api.php"

must_not_contain " jaco/test/"
must_not_contain " docs/install.md"
must_not_contain " jaco/composer.json"
must_not_contain " jaco-data/settings.php"
must_not_contain " jaco/_backup/"
must_contain " jaco-data/.htaccess"
# the installer is the only way into a fresh installation, and jaco/.htaccess
# grants it: an archive without it cannot be installed on Apache
must_contain " jaco/install.php"
must_not_contain " media/"

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK" "$LIST"' EXIT INT TERM
unzip -p "$ZIP" INSTALL.md > "$WORK/INSTALL.md"
if ! cmp -s "$WORK/INSTALL.md" "$ROOT/docs/install.md"; then
  echo "dist check failed: INSTALL.md differs from docs/install.md" >&2
  exit 1
fi

echo "dist check ok: $ZIP"
