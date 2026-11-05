# Development guide

Technical guide for contributors and automated tools. Process and PR expectations:
[contributing.md](contributing.md).

## What this project is

- **File-based CMS**: site content lives as `.html` files in the web root.
- **Admin UI**: Mithril.js SPA under `jaco/frontend/`, no frontend build step.
- **API**: PHP 8.2+ under `jaco/backend/`, entry `jaco/api.php`, CLI `jaco/cli.php`.
- **No database**: backups in `jaco-data/backups/`, runtime data in `jaco-data/runtime/`.

## Repository layout

| Path | Purpose |
|------|---------|
| `jaco/frontend/` | Admin SPA: `app.js`, `view/`, `component/`, `model/`, `lib/` |
| `jaco/frontend/lib/commands.js` | Command palette actions |
| `jaco/frontend/vendor/` | Composer-installed JS deps (Mithril, Ace, Quill) |
| `jaco/backend/` | PHP application code |
| `jaco/language/*.json` | Admin UI translations (en, de, fr, it) |
| `jaco/test/` | PHPUnit; `jaco/test/js/` QUnit |
| `docs/` | Maintainer documentation; index in [readme.md](readme.md) |
| `website/` | The project site and its web root: public HTML pages, `assets/`, `.htaccess`, `robots.txt`, `sitemap.xml`, `media/` (uploads), `jaco-data/` (templates, backups) |
| `scripts/` | Build, verify and test helper scripts (`build-dist.sh`, `assemble-docroot.sh`, ...) |

Naming rules: [naming.md](naming.md).

## Clone and run locally

    git clone https://github.com/tbreuss/jaco-cms.git
    cd jaco-cms

Install dependencies and run the site:

    docker build -t jaco .
    docker run --rm -it -v .:/app jaco composer install -d /app/jaco/
    docker compose up

The site is served from `website/` at <http://localhost:8888>. Jaco derives its web
root from the location of `jaco/`, so `docker-compose.yml` mounts `jaco/` into `website/`
(Docker creates the empty mount point there; it is git-ignored). A plain
`php -S -t website` on the host does not work for that reason.

For installation details (permissions, `.htaccess`, Nginx), see [install.md](install.md).

Without Docker, `scripts/assemble-docroot.sh` copies `website/` and `jaco/` into the
git-ignored `.docroot/`, which a plain PHP server can serve. It is a snapshot: run the
script again after changing files.

    sh scripts/assemble-docroot.sh
    php -S 127.0.0.1:8899 -t .docroot

## CLI

`-w` is required; `cli.php` loads the autoloader relative to the working directory:

    docker run --rm -v .:/app -w /app/jaco jaco php cli.php sys:version
    docker run --rm -v .:/app -w /app/jaco jaco php cli.php auth:token -u demo -p demo -c 123456
    docker run --rm -v .:/app -w /app/jaco jaco php cli.php auth:password

## Static analysis and PHP tests

Dev tools are Composer dev dependencies; inside Docker they use the image built above:

    docker run --rm -v .:/app jaco composer cs-check -d /app/jaco   # CS Fixer (check only)
    docker run --rm -v .:/app jaco composer phpstan -d /app/jaco   # PHPStan
    docker run --rm -v .:/app jaco composer deptrac -d /app/jaco   # Deptrac architecture rules
    docker run --rm -v .:/app jaco composer test -d /app/jaco      # PHPUnit
    docker run --rm -v .:/app jaco composer check -d /app/jaco      # everything at once

CI runs these steps on every push; see `.github/workflows/jobs.yml`.

## Release archive (`jaco-dist-*.zip`)

    scripts/build-dist.sh [version]             # build (needs git, composer, zip, PHP with ext-zip)
    scripts/verify-dist-archive.sh jaco-dist-dev.zip   # check an existing zip
    scripts/test-dist.sh                        # build and verify in one step

CI runs `build-dist.sh` and `verify-dist-archive.sh` on release tags and attaches
the archive together with its `.sha256` file to the GitHub release.

## JavaScript and QUnit

JavaScript syntax check (Node ≥ 22, vendored assets excluded):

    find jaco/frontend jaco/test/js \( -name '*.js' -o -name '*.mjs' \) \
      -not -path '*/asset/*' -exec node --check {} \;

QUnit (Node ≥ 22; QUnit files are vendored by `composer install`):

    sh scripts/assemble-docroot.sh
    php -S 127.0.0.1:8899 -t .docroot &
    node jaco/test/js/runner.mjs

Open `jaco/test/js/index.html` in a browser while developing. Runner options:

    node jaco/test/js/runner.mjs --url=http://127.0.0.1:8899/jaco/test/js/index.html
    node jaco/test/js/runner.mjs --timeout=60000 --devtools-timeout=60000
    node jaco/test/js/runner.mjs --chrome=/path/to/chrome

The runner clears the login throttle before it starts. The suite logs in with a
deliberately wrong password; three attempts block the address for ten minutes.
Reloading the HTML test page bypasses that reset — clear it with
`php jaco/cli.php auth:unblock`.

After frontend behaviour changes, extend `jaco/test/js/tests.js` when a QUnit case
is practical. New view/component modules must stay on the import-audit list at
the bottom of `tests.js`. After API/backend changes, add or adjust PHPUnit under
`jaco/test/`.

## End-to-end smoke tests (Playwright)

Covers every admin route for admin and editor roles, access-denied pages,
public pages in both languages, the elements reference, and the 404 page.
Needs Node ≥ 22, Google Chrome and a test-only `settings.php`:

    npm ci --prefix jaco/test/e2e
    php jaco/test/e2e/create-settings.php
    npm --prefix jaco/test/e2e run test:e2e

`create-settings.php` writes a gitignored `jaco/test/e2e/settings.php`; the real
`website/jaco-data/settings.php` is never touched. The suite starts its own server on a copy
of the site: `scripts/assemble-docroot.sh` builds the git-ignored `.docroot/` from
`website/` and `jaco/`, so the run never edits your working tree. Test users all use
`test-password`;
the file must never be deployed. CI runs this only on PHP 8.4.

The suite clears the login throttle, creates temporary upload/backup fixtures
and removes them after the run. These are **smoke tests**: they verify that each
route renders or redirects for the current role. Functional tests for page CRUD,
saving, uploads, restores or CSS changes live in `jaco/test/` (PHPUnit) and
`jaco/test/js/tests.js` (QUnit).

Run one spec:

    npm --prefix jaco/test/e2e run test:e2e -- role-pages.spec.mjs --headed
    npm --prefix jaco/test/e2e run test:e2e -- role-pages.spec.mjs --debug

Traces and the HTML report go to `jaco/test/e2e/test-results/` and
`jaco/test/e2e/playwright-report/`.

## Frontend conventions

- **Routing**: hash routes in `jaco/frontend/app.js`; default layout in `layout/Default.js`.
- **Modals**: `ModalMedium`, `ModalSmall`; initial focus on the first field with
  `[autofocus]`.
- **Lists**: table column classes in `asset/app.css` (`col-30`, `col-shrink`, `cell-clip`).
- **Preview iframe**: `lib/iframe.js` — site pages load with root-relative URLs (`/page.html`).
- **i18n**: `import {t} from '../model/i18n.js'` (adjust path); keys in all four JSON files.
- **Icons**: Lucide-derived SVGs via `component/Icon.js`.

UI structure and roles: [menu-structure.md](menu-structure.md).

## Backend conventions

- Respect Deptrac layers; run `composer deptrac -d jaco` before large refactors.
- PHP CS Fixer and PHPStan configs live under `jaco/`; CI runs `cs-check` and `phpstan`.
- API errors and auth: JWT in session storage on the client; server enforces roles.

## Security reminders

- Never commit `jaco-data/settings.php` or real credentials.
- The API is the authority for roles; disabled UI is not a security boundary.
- Upload paths and maintenance mode are documented in [install.md](install.md).

## Further reading