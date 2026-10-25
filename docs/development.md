# Development guide

Technical guide for contributors and automated tools. Process and PR expectations:
[contributing.md](contributing.md).

## What this project is

- **Flat-file CMS**: site content lives as `.html` (and assets) in the web root.
- **Admin UI**: Mithril.js SPA under `jaco/frontend/`, no frontend build step.
- **API**: PHP 8.2+ under `jaco/backend/`, entry `jaco/api.php`, CLI `jaco/cli.php`.
- **No database**; backups in `jaco-data/backups/`, runtime data in `jaco-data/runtime/`.

## Repository layout

| Path | Purpose |
|------|---------|
| `jaco/frontend/` | Admin SPA: `app.js` (routes), `view/`, `component/`, `model/`, `lib/` |
| `jaco/frontend/lib/commands.js` | Command palette actions (single source of truth) |
| `jaco/frontend/asset/` | Vendored CSS/JS (Mithril, Ace); do not edit casually |
| `jaco/backend/` | PHP application code; see `deptrac.yaml` for layer rules |
| `jaco/language/*.json` | Admin UI translations (en, de, fr, it) |
| `jaco/test/` | PHPUnit; `jaco/test/js/` QUnit + `runner.mjs` |
| `docs/` | Maintainer documentation; index in [readme.md](readme.md) |
| Site root | Public HTML pages, `assets/`, `media/` (uploads), `jaco-data/` (templates and backups), demo content |

Backend naming rules: [backend-naming.md](backend-naming.md), page file names:
[site-pages.md](site-pages.md).

## Clone and Docker image

    git clone https://github.com/tbreuss/jaco-cms.git
    cd jaco-cms

Create Docker image based on the latest supported PHP version 8.5:

    docker build -t jaco .

Optionally use an older PHP version:

    docker build --build-arg PHP_VERSION=8.2 -t jaco .
    docker build --build-arg PHP_VERSION=8.3 -t jaco .
    docker build --build-arg PHP_VERSION=8.4 -t jaco .

## Run locally

Install packages (once):

    docker run --rm -it -v .:/app jaco composer install -d /app/jaco/

Run website:

    docker run --rm -v .:/app -p 8888:8888 jaco php -S 0.0.0.0:8888 -t /app

Debug website using Xdebug:

    docker run --rm -e XDEBUG_CONFIG="client_host=172.17.0.1" -e XDEBUG_MODE=debug -e XDEBUG_SESSION_START=true -v .:/app -p 8888:8888 jaco php -S 0.0.0.0:8888 -t /app

Admin UI: `/jaco/` — the site preview loads in `#iframe` on the start route.

For QUnit only, a bare PHP server on the host is enough:

    php -S 127.0.0.1:8899 -t .

## CLI

`-w` is required; `cli.php` loads the autoloader relative to the working directory:

    docker run --rm -v .:/app -w /app/jaco jaco php cli.php sys:version
    docker run --rm -v .:/app -w /app/jaco jaco php cli.php sys:log -s 20
    docker run --rm -v .:/app -w /app/jaco jaco php cli.php auth:token -u demo -p demo
    docker run --rm -v .:/app -w /app/jaco jaco php cli.php auth:password

With two-factor login, append the current code: `php cli.php auth:token -u demo -p demo -c 123456`.
`auth:password` generates a random password and its `settings.php` digest; with `--ask`
it prompts for a password you choose and prints only the digest.

## Static analysis and PHP tests

Dev tools are Composer dev dependencies; inside Docker they use the image built above
(which already contains the installed packages):

PHP CS Fixer (check only; `cs-fix` to fix):

    docker run --rm -v .:/app jaco composer cs-check -d /app/jaco

PHPStan:

    docker run --rm -v .:/app jaco composer phpstan -d /app/jaco

Deptrac (architecture rules, see `jaco/deptrac.yaml`):

    docker run --rm -v .:/app jaco composer deptrac -d /app/jaco

List what points at third-party libraries (they are outside the layers):

    docker run --rm -v .:/app jaco composer deptrac -d /app/jaco -- --report-uncovered

PHPUnit (extra options after `--`, e.g. `-- --filter AuthTest`):

    docker run --rm -v .:/app jaco composer test -d /app/jaco

Everything at once:

    docker run --rm -v .:/app jaco composer check -d /app/jaco

Code coverage with the gate. Needs a coverage driver (Xdebug image or pcov). The gate
fails when lines or methods drop below the minimum in `jaco/test/coverage-gate.php`; the
class ratio is not checked because the marker exceptions have no lines.

    docker run --rm -e XDEBUG_MODE=coverage -v .:/app jaco composer coverage -d /app/jaco

Composer audit (security advisories; one known php-jwt low is ignored in `composer.json`):

    docker run --rm -v .:/app jaco composer audit -d /app/jaco

Composer validate (strict):

    docker run --rm -v .:/app jaco composer validate --strict -d /app/jaco

CI runs these steps on every push; see `.github/workflows/jobs.yml`.

## Release archive (`jaco-dist-*.zip`)

Build (needs git, composer, zip on the host):

    ./build-dist.sh [version]

Check an existing zip:

    ./verify-dist-archive.sh jaco-dist-dev.zip

Build and verify in one step (removes the test zip afterwards):

    ./test-dist.sh

Release tags run `build-dist.sh` and `verify-dist-archive.sh` in `.github/workflows/release.yml`
before the archive is attached to the GitHub release.

## JavaScript and QUnit

JavaScript syntax check (Node ≥ 22, vendored assets excluded):

    find jaco/frontend jaco/test/js \( -name '*.js' -o -name '*.mjs' \) -not -path '*/asset/*' -exec node --check {} \;

QUnit (headless Chrome via the runner; Node ≥ 22):

    php -S 127.0.0.1:8899 -t . &
    node jaco/test/js/runner.mjs

While developing, you can also open `jaco/test/js/index.html` in a browser.

QUnit runner options (the defaults work for a local checkout):

    node jaco/test/js/runner.mjs --url=http://127.0.0.1:8899/jaco/test/js/index.html
    node jaco/test/js/runner.mjs --timeout=60000 --devtools-timeout=60000
    node jaco/test/js/runner.mjs --chrome=/path/to/chrome

The runner clears the login history before it starts. The suite logs in with a deliberately
wrong password and the API counts every failure towards the login throttle — three attempts
block the address for ten minutes. Reloading the HTML test page in a browser bypasses that
reset; unblock again after repeated failures:

    php jaco/cli.php auth:unblock

After frontend behaviour changes, extend `jaco/test/js/tests.js` when a QUnit case is
practical. New view/component modules that use Mithril components must stay on the
import-audit list at the bottom of `tests.js` (see comment there).

After API/backend changes, add or adjust PHPUnit under `jaco/test/`.

## End-to-end smoke tests

The Playwright smoke suite covers every admin route for the admin and editor
roles, including the access-denied pages for admin-only routes: start,
pages and page editor, the inline editor and page modals, media and upload
detail, backups and backup detail, CSS, password, the system pages and logout.
It also covers the public English and German pages, the editable elements
reference and the branded 404 page.
It needs Node ≥ 22, an installed Google Chrome (the same browser the QUnit
runner uses) and a test-only `settings.php`:

    npm ci --prefix jaco/test/e2e

Use a disposable checkout, or just generate the separate test settings:

    php jaco/test/e2e/create-settings.php
    npm --prefix jaco/test/e2e run test:e2e

`create-settings.php` writes a gitignored `jaco/test/e2e/settings.php`; the real
`jaco-data/settings.php` is never touched. The test server receives it through
`JACO_SETTINGS_FILE`. Its users all use the password `test-password`; the file
must never be deployed. CI generates it automatically and runs the E2E suite
only on PHP 8.4.

The suite clears the login throttle, creates a temporary upload and backup
fixture for the detail pages and removes the fixtures after the run.

These are **smoke tests**: they verify that each route renders or redirects for
the current role. They do not replace functional tests for page CRUD, saving,
uploads, restores or CSS changes. The media-picker flow is covered by a separate
functional test (`media-picker.spec.mjs`) that runs with the full suite. Every
smoke test carries the `@smoke` tag:

    npm --prefix jaco/test/e2e run test:e2e:smoke
    npm --prefix jaco/test/e2e run test:e2e -- --grep @smoke

Run one spec, headed, or with the Playwright debugger:

    npm --prefix jaco/test/e2e run test:e2e -- role-pages.spec.mjs
    npm --prefix jaco/test/e2e run test:e2e -- role-pages.spec.mjs --headed
    npm --prefix jaco/test/e2e run test:e2e -- role-pages.spec.mjs --debug

The test starts the PHP server through
`jaco/test/e2e/playwright.config.mjs`; stop an existing QUnit server on port 8899
first.

Traces and the HTML report are written to `jaco/test/e2e/test-results/` and
`jaco/test/e2e/playwright-report/`.

## Frontend conventions

- **Routing**: hash routes in `jaco/frontend/app.js`; default layout in `layout/Default.js`.
- **Modals**: `ModalMedium`, `ModalSmall`; initial focus goes to the first field with
  `[autofocus]`, not the modal shell.
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

See [readme.md](readme.md) for all maintainer docs. Highlights:

- [install.md](install.md) — deployment, `.htaccess`, Nginx example
- [menu-structure.md](menu-structure.md) — admin navigation and command palette
- [backend-naming.md](backend-naming.md) — PHP function naming
- [features.md](features.md) — one-sentence feature catalogue
- [constraints.md](constraints.md) — invariants, roles and test map
- [glossary.md](glossary.md) — shared terms
- [backlog.md](backlog.md) — open product/tech items
