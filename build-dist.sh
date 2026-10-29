#!/bin/sh
# Baut das Release-Archiv jaco-dist-<version>.zip: App + vendor,
# ohne Dev-Dateien, Nutzerdaten und Versionsverwaltung.
#
#   ./build-dist.sh [version]   (default: dev)
#
# Benötigt: git, composer, zip.
set -eu

VERSION="${1:-dev}"
ROOT="$(cd "$(dirname "$0")" && pwd)"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT INT TERM

command -v git >/dev/null || { echo "git fehlt" >&2; exit 1; }
command -v composer >/dev/null || { echo "composer fehlt" >&2; exit 1; }
command -v zip >/dev/null || { echo "zip fehlt" >&2; exit 1; }

# nur getrackte Dateien aus jaco/: kein .git, keine Nutzerdaten (Backups,
# Logs und settings.php sind ignoriert und kommen nicht mit). jaco-data/ wird
# als leere Struktur mitgeliefert, damit die Anwendung schreiben kann.
git -C "$ROOT" archive HEAD jaco | tar -x -C "$WORK"
mkdir -p "$WORK/jaco-data/templates/page" "$WORK/jaco-data/templates/content" \
         "$WORK/jaco-data/templates/site" "$WORK/jaco-data/backups"
cp "$ROOT/jaco-data/.htaccess" "$WORK/jaco-data/.htaccess"
cp "$ROOT/jaco-data/backups/.gitignore" "$WORK/jaco-data/backups/.gitignore"
mkdir -p "$WORK/jaco-data/app-backups"
cp "$ROOT/jaco-data/app-backups/.gitignore" "$WORK/jaco-data/app-backups/.gitignore"
for dir in page content site; do
    cp "$ROOT/jaco-data/templates/$dir/.gitignore" "$WORK/jaco-data/templates/$dir/.gitignore"
done

# Installationsanleitung und Lizenz aus dem Arbeitsverzeichnis dazu (evtl. uncommitted).
# Quelle im Repo: docs/install.md; im ZIP weiterhin INSTALL.md im Archiv-Root.
cp "$ROOT/docs/install.md" "$WORK/INSTALL.md"
cp "$ROOT/LICENSE" "$WORK/LICENSE"

# Dependencies für die Auslieferung installieren (braucht composer.json).
# Plattform auf 8.2 pinnen, damit die Auflösung auch auf neueren
# Build-Versionen Pakete wählt, die ab PHP 8.2 laufen.
composer config --working-dir "$WORK/jaco" platform.php 8.2
composer install --working-dir "$WORK/jaco" --no-dev --prefer-dist --no-progress --optimize-autoloader

# Dev-Dateien entfernen (gleiche Liste wie .github/workflows/jobs.yml,
# plus composer-Metadaten: die Auslieferung ist vorinstalliert)
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

# leere Laufzeitverzeichnisse sicherstellen (braucht die App zum Schreiben).
# runtime/ liegt unter jaco-data/ (Betriebsdaten), nicht unter jaco/ (App):
mkdir -p "$WORK/jaco-data/runtime/log"

# jaco-data/ (Vorlagen und Backups) und media/ (Uploads) sind Nutzerdaten und
# kommen nicht mit: der Installer legt sie samt Schutzdatei an, wo sie fehlen.

OUT="$ROOT/jaco-dist-$VERSION.zip"
rm -f "$OUT"
(cd "$WORK" && zip -qr "$OUT" jaco jaco-data INSTALL.md LICENSE)

echo "gebaut: $OUT ($(du -h "$OUT" | cut -f1))"
