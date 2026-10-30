#!/bin/sh
# Smoke test for the self-update mechanism.
#
# Builds a local release from the current code, installs it into a disposable
# site, runs sys:check-update and sys:update, then verifies the app still works.
#
# Usage: ./scripts/smoke-update.sh
#
# Requires: php (>= 8.2 with zip), zip, unzip

set -eu

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT INT TERM

echo "=== Smoke test: self-update ==="
echo "Working dir: $WORK"

# 1. Build a local release from the current code. The archive is installed over
# file://, which trusts the operator's own file, so no checksum is needed here:
# a real HTTPS update verifies jaco-dist-<version>.zip.sha256 next to the archive.
echo "--- Building local release ---"
cd "$ROOT"
./build-dist.sh smoke-test 2>&1 | tail -3
cp "$ROOT/jaco-dist-smoke-test.zip" "$WORK/release.zip"
cp "$ROOT/jaco-dist-smoke-test.zip.sha256" "$WORK/release.zip.sha256"
rm -f "$ROOT/jaco-dist-smoke-test.zip" "$ROOT/jaco-dist-smoke-test.zip.sha256"
cd - >/dev/null
echo "Built: $(du -h "$WORK/release.zip" | cut -f1)"
echo "Checksum: $(cut -d' ' -f1 "$WORK/release.zip.sha256")"

# 2. Extract
echo "--- Extracting ---"
mkdir -p "$WORK/site"
cd "$WORK/site"
unzip -q "$WORK/release.zip"
cd - >/dev/null

# 3. Create settings.php
echo "--- Creating settings.php ---"
HASH=$(php -r 'echo password_hash("smoke-test-pw", PASSWORD_BCRYPT, ["cost" => 12]);')
cat > "$WORK/site/jaco-data/settings.php" <<PHP
<?php
return [
    'users' => [
        'admin' => [
            'password' => '$HASH',
            'role' => 'admin',
        ],
    ],
    'secretKey' => 'smoke-test-secret-key-at-least-32-chars-long',
    'mediaDir' => 'media',
    'dataDir' => 'jaco-data',
];
PHP
mkdir -p "$WORK/site/media"

# 4. Check version before
echo "--- Version before update ---"
cd "$WORK/site/jaco"
php cli.php sys:version
cd - >/dev/null

# 5. Check for updates (will say "already up to date" for same version)
echo "--- Check for updates ---"
cd "$WORK/site/jaco"
php cli.php sys:check-update || true
cd - >/dev/null

# 6. Run update (forced via direct URL, same version is allowed)
echo "--- Running update ---"
cd "$WORK/site/jaco"
php cli.php sys:update -u "file://$WORK/release.zip"
cd - >/dev/null

# 7. Verify after update
echo "--- Version after update ---"
cd "$WORK/site/jaco"
php cli.php sys:version
cd - >/dev/null

# 8. Verify backup exists
echo "--- Checking backup ---"
BACKUP_COUNT=$(ls -d "$WORK/site/jaco-data/app-backups"/jaco-* 2>/dev/null | wc -l)
echo "App backups: $BACKUP_COUNT"
if [ "$BACKUP_COUNT" -lt 1 ]; then
    echo "ERROR: no backup created" >&2
    exit 1
fi

# 9. Verify .htaccess exists
if [ ! -f "$WORK/site/jaco/.htaccess" ]; then
    echo "ERROR: .htaccess missing after update" >&2
    exit 1
fi

echo "=== Smoke test PASSED ==="