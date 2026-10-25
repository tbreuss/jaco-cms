#!/bin/bash
# The PHPUnit tests write into fixed directories below jaco/test/asset/. They are
# part of the repository only through their .gitignore placeholders: git does not
# store empty directories, so one missing placeholder means a fresh clone - as in
# CI - has no directory to write into, while a working copy still has one left
# over. Nine tests failed that way once the data directory moved out of the
# application directory.
#
# Run this against a fresh clone before pushing:
#
#   git clone . /tmp/check && cd /tmp/check && bash jaco/test/check-fixtures.sh
set -u

missing=0

while IFS= read -r directory; do
    if [ ! -d "$directory" ]; then
        echo "missing: $directory"
        missing=1
    fi
done <<'DIRECTORIES'
jaco/test/asset/jaco
jaco/test/asset/jaco-data/backups
jaco/test/asset/jaco-data/templates/page
jaco/test/asset/jaco-data/templates/content
jaco/test/asset/jaco-data/templates/site
jaco/test/asset/media
DIRECTORIES

if [ "$missing" -ne 0 ]; then
    echo "One of these directories has no .gitignore placeholder, so a fresh clone lacks it."
    exit 1
fi

echo "All test fixture directories exist."
