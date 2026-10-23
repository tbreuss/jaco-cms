# Jaco CMS

**The flat-file CMS that manages static HTML files directly in the web directory.**

No database, no build step — just PHP 8.2+ on a typical shared host. Mark regions of
your pages — existing or new — with `data-jaco-*` attributes, then edit them
inline on the live site: rich text, images, links, embedded videos, reorderable
content blocks. Every overwrite or delete keeps a timestamped backup, restoring
is one click.

## Features

**Editing**

- Inline editor on the live site: rich text, single lines, links, images, YouTube embeds
- Media library: upload once and reuse images and PDFs from the image picker
- Site templates: reuse HTML fragments across pages, with separate names/markers for language variants, and apply one to all marked elements at once
- Page manager: create from templates, copy and delete — with backups
- Page settings: title, meta description and robots, without editing HTML by hand
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

Jaco fits small static sites — existing or new — where a developer or template
marks the editable regions once, then owners and editors keep the content up to
date, typically on cheap shared hosting:

- Restaurants, cafés, hairdressers and other local businesses: opening hours, menus, price lists and photos, edited in place on the live page
- Clubs and associations: news, events and member info maintained by volunteers, no HTML knowledge needed
- Small companies and freelancers: company site or portfolio with a contact page, editable without a backend workflow
- Private individuals: family, hobby or portfolio sites that should stay online for years with minimal care

The pattern is always the same: a developer (or the site template) marks the
editable regions once, then editors work directly on the page. A new site can
start from any static HTML template; the same edit markers apply. If you need
user accounts, comments or a shop, Jaco is the wrong tool — it edits static
pages, nothing more.

## Requirements

- PHP `^8.2` with the `dom`, `libxml` and `openssl` extensions
- Composer (only for installing dependencies, not needed on the server with the dist archive)
- Apache or Nginx with PHP; `php -S` for development only

## Install in five minutes

Full guide with markers reference: [`docs/install.md`](docs/install.md).

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
`.htaccess` — see “Protect the internals” in [`docs/install.md`](docs/install.md).

## Development

Working from git uses Docker (PHP 8.2–8.5) and Node ≥ 22 for frontend tests.
Clone, run the site locally, static analysis, PHPUnit, QUnit, and the Playwright
smoke suite: [docs/development.md](docs/development.md). How we expect changes:
[docs/contributing.md](docs/contributing.md). Doc index: [docs/readme.md](docs/readme.md).

## License

Freeware, including commercial websites you build with it — no licence
validation, no phone home. If no stable release appears for 365 consecutive
days, the source automatically falls back to MIT terms. Details in
[`LICENSE`](LICENSE).

## Links

- Features and docs: `features.html`, `docs.html` (German versions in `de/`)
- Documentation: [`docs/readme.md`](docs/readme.md) (installation, contributing, development)
- Releases (dist archives): <https://github.com/tbreuss/jaco-cms/releases>