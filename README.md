# Jaco CMS

[![CI](https://github.com/tbreuss/jaco-cms/actions/workflows/jobs.yml/badge.svg)](https://github.com/tbreuss/jaco-cms/actions/workflows/jobs.yml)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![License: Freeware](https://img.shields.io/badge/License-Freeware-green.svg)](LICENSE)
[![Release](https://img.shields.io/github/v/release/tbreuss/jaco-cms)](https://github.com/tbreuss/jaco-cms/releases)

**The flat-file CMS that manages static HTML files directly in the web directory.**

Mark regions of your pages — existing or new — with `data-jaco-*` attributes,
then edit them inline on the live site: rich text, images, links, embedded
videos, reorderable content blocks. Every overwrite or delete keeps a
timestamped backup; restoring is one click.

<!-- Screenshot placeholder: add a screenshot of the inline editor here -->
<!-- ![Jaco CMS inline editor](media/screenshot-editor.png) -->

## Highlights

- **Edit in place** — click a marked region on the live site, change it, save
- **No database** — plain HTML files, portable and versionable
- **No build step** — PHP 8.2+ on cheap shared hosting
- **Self-updating** — one command or one click, with automatic backup
- **Four admin languages** — English, German, French, Italian

## Features

**Editing**

- Inline editor: rich text, single lines, links, images, YouTube embeds
- Media library: upload once, reuse images and PDFs from the image picker
- Site templates: reusable HTML fragments, apply to all marked elements at once
- Page manager: create from templates, copy, delete
- Page settings: title, meta description, robots — without editing HTML
- Content blocks: add, remove, reorder recurring sections by drag and drop
- Stylesheet editor; upload manager with extension and size checks

**Operating**

- Token login (JWT), brute-force throttle, optional TOTP, maintenance mode
- Automatic backups before every change, one-click restore
- Hardened web root (`.htaccess` ships, Nginx example included)
- Freeware, including commercial use — no licence validation, no phone home

## Who Jaco is for

Small static sites — existing or new — where a developer marks editable regions
once, then owners and editors keep content up to date on cheap shared hosting:

- **Restaurants, cafés, hairdressers** — opening hours, menus, price lists, photos
- **Tradespeople and local services** — plumbers, electricians, cleaners: services, prices, contact
- **Clubs and associations** — news, events, member info by volunteers
- **Small companies and freelancers** — company site or portfolio
- **Private individuals** — family, hobby or portfolio sites

If you need a shop, a database-backed application or real-time features,
Jaco is the wrong tool — it edits static pages, nothing more.

## Requirements

- PHP `^8.2` with `dom`, `libxml`, `openssl` (plus `zip` for the self-updater)
- Composer (only for installing from git; not needed with the dist archive)
- Apache or Nginx with PHP; `php -S` for development only

## Install in five minutes

Full guide: [`docs/install.md`](docs/install.md).

1. Download `jaco-dist-<version>.zip` from the [releases page](https://github.com/tbreuss/jaco-cms/releases) (dependencies included) and extract it next to your `index.html`.
2. Open `https://example.com/jaco/install.php` and create your login.
3. Grant write permissions: editable `.html` files, `jaco-data/backups/`, `media/`, `jaco-data/runtime/log/`.
4. Mark editable regions and log in at `/jaco/`:

```html
<h1 data-jaco-line>My headline</h1>
<div data-jaco-block><p>My paragraph.</p></div>
```

Three directories next to your pages: `jaco/` (application, replaced on update),
`jaco-data/` (templates, backups, runtime, credentials — never public),
`media/` (editor uploads). An update replaces `jaco/`; `settings.php`, `backups/`
and `runtime/` stay untouched.

Nginx: copy blocks from [`docs/nginx-jaco.conf.example`](docs/nginx-jaco.conf.example).
Apache: one `Authorization` forwarding rule in the site root `.htaccess` — see
[`docs/install.md`](docs/install.md).

## Development

Working from git: [docs/development.md](docs/development.md) · Contributing: [docs/contributing.md](docs/contributing.md) · Doc index: [docs/readme.md](docs/readme.md).

## License

Freeware, including commercial websites — no licence validation, no phone home.
If no stable release appears for 365 days, the source falls back to MIT.
Details: [`LICENSE`](LICENSE).

## Links

- [Features & docs](features.html) · [Editable elements reference](elements.html)
- [Documentation](docs/readme.md) · [Releases](https://github.com/tbreuss/jaco-cms/releases)