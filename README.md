# Jaco CMS

**The flat-file CMS that manages static HTML files directly in the web directory.**

No database, no build step — just PHP 8.2+ on any shared host. Mark regions of your
existing pages with `data-jaco-*` attributes, then edit them inline on the live
site: rich text, images, links, embedded videos, reorderable content blocks.
Every overwrite or delete keeps a timestamped backup, restoring is one click.

## Features

**Editing**

- Inline editor on the live site: rich text, single lines, links, images, YouTube embeds
- Page manager: create from templates, copy, rename, delete — with backups
- Content blocks: editors add, remove and reorder recurring sections by drag and drop
- Image galleries: thumbnails link to generated full versions
- Stylesheet editor with backups, upload manager with extension and size checks

**Operating**

- The pages are the database: plain HTML files, portable and versionable
- Token login (JWT) with brute-force throttle, optional TOTP second factor, maintenance mode
- Automatic backups before every change, one-click restore
- Admin interface in English, German, French and Italian
- Hardened web root (`.htaccess` ships, Nginx example included) with status checks on the System page
- Freeware, including commercial use — no licence validation, no phone home

## Who Jaco is for

Jaco fits small static sites whose owners — not developers — keep the content
up to date, typically on cheap shared hosting:

- Restaurants, cafés, hairdressers and other local businesses: opening hours, menus, price lists and photos, edited in place on the live page
- Clubs and associations: news, events and member info maintained by volunteers, no HTML knowledge needed
- Small companies and freelancers: company site or portfolio with a contact page, editable without a backend workflow
- Private individuals: family or hobby sites that should stay online for years with minimal care

The pattern is always the same: a developer (or the site template) marks the
editable regions once, then editors work directly on the page. If you need
user accounts, comments or a shop, Jaco is the wrong tool — it edits static
pages, nothing more.

## Requirements

- PHP `^8.2` with the `dom`, `libxml` and `openssl` extensions
- Composer (only for installing dependencies, not needed on the server with the dist archive)
- Apache or Nginx with PHP; `php -S` for development only

## Install in five minutes

Full guide with markers reference: [`INSTALL.md`](INSTALL.md).

1. Download `jaco-dist-<version>.zip` from the [releases page](https://github.com/tbreuss/jaco-cms/releases) (dependencies included) and extract it next to your `index.html`.
2. Open `https://example.com/jaco/install.php` and create your login: username, password (≥ 8 characters), language, upload directory (`upload` by default), two-factor authentication on. The installer writes `jaco/settings.php` and deletes itself.
3. Grant write permissions: editable `.html` files, `jaco/_backup/`, `upload/`, `jaco/runtime/log/`.
4. Mark editable regions in your HTML and log in at `/jaco/`:

```html
<h1 data-jaco-editable-line>My headline</h1>
<div data-jaco-editable><p>My paragraph.</p></div>
```

Heads-up for servers: on Nginx copy the blocks from `docs/nginx-jaco.conf.example`
into the server context (`.htaccess` is Apache-only). With a dist archive on
Apache, one `Authorization` forwarding rule belongs in the site root
`.htaccess` — see “Protect the internals” in [`INSTALL.md`](INSTALL.md).

## Development Environment

### Clone project

    git clone https://github.com/tbreuss/jaco-cms.git
    cd jaco-cms

### Create Docker Image

Create Docker image based on the latest supported PHP version 8.5

    docker build -t jaco .

Optionally you can also use an older PHP version

    docker build --build-arg PHP_VERSION=8.2 -t jaco .
    docker build --build-arg PHP_VERSION=8.3 -t jaco .
    docker build --build-arg PHP_VERSION=8.4 -t jaco .

### Run Development Environment

Install packages

    docker run --rm -it -v .:/app jaco composer install -d /app/jaco/

Run website

    docker run --rm -v .:/app -p 8888:8888 jaco php -S 0.0.0.0:8888 -t /app

Debug website using Xdebug

    docker run --rm -e XDEBUG_CONFIG="client_host=172.17.0.1" -e XDEBUG_MODE=debug -e XDEBUG_SESSION_START=true -v .:/app -p 8888:8888 jaco php -S 0.0.0.0:8888 -t /app

Run CLI (`-w` is required, cli.php loads the autoloader relative to the working directory)

    docker run --rm -v .:/app -w /app/jaco jaco php cli.php sys:version
    docker run --rm -v .:/app -w /app/jaco jaco php cli.php sys:log -s 20
    docker run --rm -v .:/app -w /app/jaco jaco php cli.php auth:token -u demo -p demo

With two-factor login, append the current code: `php cli.php auth:token -u demo -p demo -c 123456`.

### Static Code Analyses & Unit Tests

The dev tools are Composer dev dependencies; inside Docker they run through
the image built above (which already contains the installed packages):

PHP CS Fixer (check only, `cs-fix` to fix)

    docker run --rm -v .:/app jaco composer cs-check -d /app/jaco

PHPStan

    docker run --rm -v .:/app jaco composer phpstan -d /app/jaco

Deptrac (architecture rules, see `jaco/deptrac.yaml`)

    docker run --rm -v .:/app jaco composer deptrac -d /app/jaco

List what points at third party libraries (they are outside the layers)

    docker run --rm -v .:/app jaco composer deptrac -d /app/jaco -- --report-uncovered

PHPUnit (extra options after `--`, e.g. `-- --filter AuthTest`)

    docker run --rm -v .:/app jaco composer test -d /app/jaco

Everything at once

    docker run --rm -v .:/app jaco composer check -d /app/jaco

Code coverage with the gate. Needs a coverage driver, so run it with the xdebug image or with pcov: the gate fails when lines or methods drop below the minimum in `jaco/test/coverage-gate.php`, the class ratio is not checked because the marker exceptions have no lines.

    docker run --rm -e XDEBUG_MODE=coverage -v .:/app jaco composer coverage -d /app/jaco

Composer audit (security advisories; one known php-jwt low is ignored in composer.json)

    docker run --rm -v .:/app jaco composer audit -d /app/jaco

Composer validate (strict)

    docker run --rm -v .:/app jaco composer validate --strict -d /app/jaco

JavaScript syntax check (needs node >= 22, vendored assets excluded)

    find jaco/frontend jaco/test/js \( -name '*.js' -o -name '*.mjs' \) -not -path '*/asset/*' -exec node --check {} \;

QUnit (frontend, runs in headless Chrome, needs node >= 22)

    php -S 127.0.0.1:8899 -t . &
    node jaco/test/js/runner.mjs

## License

Freeware, including commercial websites you build with it — no licence
validation, no phone home. If no stable release appears for 365 consecutive
days, the source automatically falls back to MIT terms. Details in
[`LICENSE`](LICENSE).

## Links

- Features and docs: `features.html`, `docs.html` (German versions in `de/`)
- Installation: [`INSTALL.md`](INSTALL.md)
- Releases (dist archives): <https://github.com/tbreuss/jaco-cms/releases>