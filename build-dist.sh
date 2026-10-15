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
# Logs und settings.php sind ignoriert und kommen nicht mit)
git -C "$ROOT" archive HEAD jaco | tar -x -C "$WORK"

# Installationsanleitung und Lizenz aus dem Arbeitsverzeichnis dazu (evtl. uncommitted)
cp "$ROOT/INSTALL.md" "$WORK/INSTALL.md"
cp "$ROOT/LICENSE" "$WORK/LICENSE"
cp "$ROOT/docs/nginx-jaco.conf.example" "$WORK/nginx-jaco.conf.example"

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
rm -f "$WORK/jaco/phpunit.xml" "$WORK/jaco/.phpunit.cache" "$WORK/jaco/.phpunit.result.cache"
rm -f "$WORK/jaco/composer.json" "$WORK/jaco/composer.lock"
rm -f "$WORK/jaco/settings.php"
rm -f "$WORK/jaco/api.http" "$WORK/jaco/phpstan.neon" "$WORK/jaco/deptrac.yaml"
find "$WORK" -name ".DS_Store" -delete

# leere Laufzeitverzeichnisse sicherstellen (braucht die App zum Schreiben)
mkdir -p "$WORK/jaco/_backup" "$WORK/jaco/runtime/log"

# upload/ ist Nutzerdaten-Verzeichnis und kommt nicht mit: der Installer legt
# es samt Schutzdatei an, wo immer es fehlt.

OUT="$ROOT/jaco-dist-$VERSION.zip"
rm -f "$OUT"
(cd "$WORK" && zip -qr "$OUT" jaco INSTALL.md LICENSE nginx-jaco.conf.example)

echo "gebaut: $OUT ($(du -h "$OUT" | cut -f1))"
