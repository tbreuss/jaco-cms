#!/bin/sh
# Builds the release archive jaco-dist-<version>.zip: app + vendor,
# without dev files, user data and version control.
#
#   scripts/build-dist.sh [version]   (default: dev)
#
# Next to it sits jaco-dist-<version>.zip.sha256: the self-updater installs
# only an archive whose checksum matches the published release.
#
# Requires: git, composer, zip, php (with ext-zip).
set -eu

VERSION="${1:-dev}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT INT TERM

command -v git >/dev/null || { echo "git missing" >&2; exit 1; }
command -v composer >/dev/null || { echo "composer missing" >&2; exit 1; }
command -v zip >/dev/null || { echo "zip missing" >&2; exit 1; }
# ext-zip is only suggested for the runtime (the updater needs it, the CMS does
# not), but building and checking the archive is easier with it available.
php -r 'exit(extension_loaded("zip") ? 0 : 1);' || { echo "php-ext zip missing" >&2; exit 1; }

# only tracked files from jaco/: no .git, no user data (backups,
# logs and settings.php are ignored and stay out). jaco-data/ ships
# as an empty structure so the application can write.
git -C "$ROOT" archive HEAD jaco | tar -x -C "$WORK"
mkdir -p "$WORK/jaco-data/templates/page" "$WORK/jaco-data/templates/content" \
         "$WORK/jaco-data/templates/site" "$WORK/jaco-data/backups"
cp "$ROOT/website/jaco-data/.htaccess" "$WORK/jaco-data/.htaccess"
cp "$ROOT/website/jaco-data/backups/.gitignore" "$WORK/jaco-data/backups/.gitignore"
mkdir -p "$WORK/jaco-data/app-backups"
cp "$ROOT/website/jaco-data/app-backups/.gitignore" "$WORK/jaco-data/app-backups/.gitignore"
for dir in page content site; do
    cp "$ROOT/website/jaco-data/templates/$dir/.gitignore" "$WORK/jaco-data/templates/$dir/.gitignore"
done

# Add the installation guide and license from the working directory (possibly uncommitted).
# Source in the repo: docs/install.md; in the ZIP still INSTALL.md at the archive root.
cp "$ROOT/docs/install.md" "$WORK/INSTALL.md"
cp "$ROOT/LICENSE" "$WORK/LICENSE"

# Install dependencies for the release (needs composer.json).
# Pin the platform to 8.2 so the resolution also picks packages
# on newer build versions that run on PHP 8.2 and above.
composer config --working-dir "$WORK/jaco" platform.php 8.2
composer install --working-dir "$WORK/jaco" --no-dev --prefer-dist --no-progress --optimize-autoloader

# Remove dev files (same list as .github/workflows/jobs.yml,
# plus composer metadata: the release is pre-installed)
rm -rf "$WORK/jaco/test"
rm -f "$WORK/.editorconfig" "$WORK/.gitignore" "$WORK/Dockerfile"
rm -f "$WORK/jaco/.php-cs-fixer.php" "$WORK/jaco/.php-cs-fixer.cache"
rm -f "$WORK/jaco/.deptrac.cache"
# Old operator data folders are no longer part of the release.
rm -rf "$WORK/jaco/_backup" "$WORK/jaco/_template"
rm -f "$WORK/jaco/phpunit.xml" "$WORK/jaco/.phpunit.cache" "$WORK/jaco/.phpunit.result.cache"
rm -f "$WORK/jaco/composer.json" "$WORK/jaco/composer.lock"
rm -f "$WORK/jaco-data/settings.php"
rm -f "$WORK/jaco/api.http" "$WORK/jaco/phpstan.neon" "$WORK/jaco/deptrac.yaml"
find "$WORK" -name ".DS_Store" -delete

# Ensure empty runtime directories (the app needs them for writing).
# runtime/ lives under jaco-data/ (operator data), not under jaco/ (app):
mkdir -p "$WORK/jaco-data/runtime/log"

# jaco-data/ (templates and backups) and media/ (uploads) are user data and
# stay out: the installer creates them together with their protection file where missing.

OUT="$ROOT/jaco-dist-$VERSION.zip"
rm -f "$OUT"
(cd "$WORK" && zip -qr "$OUT" jaco jaco-data INSTALL.md LICENSE)

# Same format as sha256sum in the release workflow, so the updater reads both.
OUTSUM="$OUT.sha256"
rm -f "$OUTSUM"
(cd "$ROOT" && php -r 'printf("%s  %s\n", hash_file("sha256", $argv[1]), basename($argv[1]));' "jaco-dist-$VERSION.zip") > "$OUTSUM"

echo "built: $OUT ($(du -h "$OUT" | cut -f1))"
echo "checksum: $OUTSUM"
