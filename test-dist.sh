#!/bin/sh
# Baut das Release-Archiv und prüft es (lokal oder in CI).
#   ./test-dist.sh [version-label]
set -eu

ROOT="$(cd "$(dirname "$0")" && pwd)"
VERSION="${1:-ci-test}"
ZIP="$ROOT/jaco-dist-$VERSION.zip"

cd "$ROOT"
./build-dist.sh "$VERSION"
./verify-dist-archive.sh "$ZIP"
rm -f "$ZIP"

echo "test-dist ok"
