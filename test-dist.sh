#!/bin/sh
# Builds the release archive and checks it (locally or in CI).
#   ./test-dist.sh [version-label]
set -eu

ROOT="$(cd "$(dirname "$0")" && pwd)"
VERSION="${1:-ci-test}"
ZIP="$ROOT/jaco-dist-$VERSION.zip"

cd "$ROOT"
./build-dist.sh "$VERSION"
./verify-dist-archive.sh "$ZIP"
rm -f "$ZIP" "$ZIP.sha256"

echo "test-dist ok"
