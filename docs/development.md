# Development guide

Technical guide for contributors and automated tools. Process and PR expectations:
[contributing.md](contributing.md).

## What this project is

- **Flat-file CMS**: site content lives as `.html` (and assets) in the web root.
- **Admin UI**: Mithril.js SPA under `jaco/frontend/`, no frontend build step.
- **API**: PHP 8.2+ under `jaco/backend/`, entry `jaco/api.php`, CLI `jaco/cli.php`.
- **No database**; backups in `jaco/_backup/`, runtime data in `jaco/runtime/`.

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
| Site root | Public HTML pages, `upload/`, demo content |

Backend naming rules: [backend-naming.md](backend-naming.md).

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

With two-factor login, append the current code: `php cli.php auth:token -u demo -p demo -c 123456`.

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

## JavaScript and QUnit

JavaScript syntax check (Node ≥ 22, vendored assets excluded):

    find jaco/frontend jaco/test/js \( -name '*.js' -o -name '*.mjs' \) -not -path '*/asset/*' -exec node --check {} \;

QUnit (headless Chrome via the runner; Node ≥ 22):

    php -S 127.0.0.1:8899 -t . &
    node jaco/test/js/runner.mjs

While developing, you can also open `jaco/test/js/index.html` in a browser.

The runner clears the login history before it starts. The suite logs in with a deliberately
wrong password and the API counts every failure towards the login throttle — three attempts
block the address for ten minutes. Reloading the HTML test page in a browser bypasses that
reset; unblock again after repeated failures:

    php jaco/cli.php auth:unblock

After frontend behaviour changes, extend `jaco/test/js/tests.js` when a QUnit case is
practical. New view/component modules that use Mithril components must stay on the
import-audit list at the bottom of `tests.js` (see comment there).

After API/backend changes, add or adjust PHPUnit under `jaco/test/`.

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

- Never commit `jaco/settings.php` or real credentials.
- The API is the authority for roles; disabled UI is not a security boundary.
- Upload paths and maintenance mode are documented in [install.md](install.md).

## Further reading

See [readme.md](readme.md) for all maintainer docs. Highlights:

- [install.md](install.md) — deployment, `.htaccess`, Nginx example
- [menu-structure.md](menu-structure.md) — admin navigation and command palette
- [backend-naming.md](backend-naming.md) — PHP function naming
- [features.md](features.md) — product feature notes (maintainer)
- [backlog.md](backlog.md) — open product/tech items
