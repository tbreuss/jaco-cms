# Jaco CMS

PHP 8.2+ CMS that turns static HTML into editable pages. No database, no build step.
Site content lives as `.html` files in the web root (in this repo `website/`); editable
regions carry `data-jaco-*` attributes. Admin SPA (Mithril.js) under `jaco/`, PHP API at
`jaco/api.php`, CLI at `jaco/cli.php`. Operator data (settings, templates, backups,
runtime) lives in `jaco-data/` and is never served over the web. Uploads live in `media/`.
Jaco derives its web root from the location of `jaco/`, so the dev server mounts `jaco/`
into `website/` (`docker-compose.yml`).

Details: [README.md](README.md), [llms.txt](llms.txt). Maintainer docs index:
[docs/readme.md](docs/readme.md).

## Ground rules

- Answer in the user's language. Commit messages and code comments are English.
- Ask before implementing when the requirement is ambiguous. Prefer a small, verified
  change over a large guess ([docs/contributing.md](docs/contributing.md)).
- Keep changes focused: every line traces to the task. No drive-by refactors, no
  formatting sweeps, no speculative features.
- Match the existing style of the file you touch before introducing anything new.
- Never commit or push. Leave changes in the working tree and report what changed and
  why; the human reviews, runs the checks and commits.

## Layout

| Path | Purpose |
|------|---------|
| `jaco/frontend/` | Admin SPA: `app.js` (routes), `view/`, `component/`, `model/`, `lib/`, `editor.js` |
| `jaco/backend/` | PHP, layered `core/` → `domain/` → `api/` / `cli/`; rules in `jaco/deptrac.yaml` |
| `jaco/schema/` | JSON schemas — the API contract, validated by `backend/core/router.php` |
| `jaco/language/{en,de,fr,it}.json` | Admin UI translations |
| `jaco/test/` | PHPUnit; `jaco/test/js/` QUnit; `jaco/test/e2e/` Playwright |
| `website/` | The project site and web root: pages, `assets/`, `.htaccess`, `media/` (uploads), `jaco-data/` (operator data); `jaco/` is mounted into it (`docker-compose.yml`) |
| `docs/` | Maintainer documentation, lowercase hyphenated file names |

## Conventions

- **i18n**: UI text only through `t('Key')` in `jaco/language/` — add every new key to
  all four files. Never hard-code UI text in views.
- **PHP Backend naming**: commands are `verb_object`; queries are `get_`/`find_`/`read_`/`load_`; predicates are `is_`/`has_`/`can_`. File names are `snake_case`.
- **JavaScript Frontend naming**: modules/files are `PascalCase` or `camelCase` by role (views, components, models vs. lib, state); functions are `camelCase`; constants are `UPPER_SNAKE_CASE`.

See [docs/naming.md](docs/naming.md) for both.
- **Editorconfig**: 4 spaces, LF, final newline, no trailing whitespace — 2 spaces in
  JS, NEON and YAML.
- **Routes**: register in `jaco/routes.php`; every route needs an explicit role, an
  omitted role means `admin`.
- **Commit messages**: `fix:`, `feat:`, `style:`, `docs:`, `refactor:`, `test:`, `chore:`
  — short English subject.

## Invariants

- Nothing is modified without a backup in `jaco-data/backups` first.
- `jaco/` is out of reach for page, CSS and upload operations (path guard).
- Only YouTube embed URLs survive sanitizing; event handlers never do.
- Uploads are never overwritten — a collision gets `-1` appended.
- `readOnly` accounts and maintenance mode are enforced in the router, not the UI; the
  API is the authority, a disabled button is not a security boundary.
- Credentials fail closed: no users entry means no login.

Full list: [docs/constraints.md](docs/constraints.md), terms: [docs/glossary.md](docs/glossary.md).

## Checks (CI runs these)

```bash
composer cs-check -d jaco      # PHP CS Fixer
composer phpstan -d jaco       # PHPStan
composer deptrac -d jaco       # Deptrac
composer test -d jaco          # PHPUnit
composer check -d jaco         # the above in one go

find jaco/frontend jaco/test/js \( -name '*.js' -o -name '*.mjs' \) -not -path '*/asset/*' -exec node --check {} \;
sh scripts/assemble-docroot.sh && php -S 127.0.0.1:8899 -t .docroot &   # QUnit needs a server
node jaco/test/js/runner.mjs

npm ci --prefix jaco/test/e2e && php jaco/test/e2e/create-settings.php
npm --prefix jaco/test/e2e run test:e2e
```

Without Docker: `composer install -d jaco` first. Behaviour changes need a test —
PHPUnit for backend, QUnit in `jaco/test/js/tests.js` for frontend (keep new
Mithril modules on the import-audit list there), Playwright for route smoke coverage.
See [docs/development.md](docs/development.md) for Docker commands and details.

## Do not

- Commit `jaco-data/settings.php`, tokens, passwords or other secrets.
- Edit `jaco/frontend/asset/` or `jaco/frontend/vendor/` casually; they are vendored.
- Add a build step, a framework or a database.

## Gotchas that cost a round trip

- **QUnit fixture**: `m.render(el, null)` does not remove nodes the virtual DOM
  never created. Add buttons that stand *next to* a list (position `top`/`bottom`)
  survive a reset — use `clearFixture()` in `jaco/test/js/tests.js`.
- **Login throttle**: the QUnit suite and the E2E suite log in with a wrong
  password; three failures block the address for 10 minutes. Clear it with
  `php jaco/cli.php auth:unblock`.
- **Playwright** needs `channel: 'chrome'`; without Google Chrome, copy the config
  and set `launchOptions.executablePath` instead of installing a browser.
