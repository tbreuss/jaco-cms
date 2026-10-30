# Jaco CMS

[![CI](https://github.com/tbreuss/jaco-cms/actions/workflows/jobs.yml/badge.svg)](https://github.com/tbreuss/jaco-cms/actions/workflows/jobs.yml)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![License: Freeware](https://img.shields.io/badge/License-Freeware-green.svg)](LICENSE)
[![Release](https://img.shields.io/github/v/release/tbreuss/jaco-cms)](https://github.com/tbreuss/jaco-cms/releases)

**The CMS that turns static HTML into editable pages.**

Mark regions of your pages with `data-jaco-*` attributes, then edit them inline
on the live site — rich text, images, links, videos, reorderable blocks.
Every change is backed up automatically; restore with one click.

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

- Secure login with optional two-factor authentication, login protection and maintenance mode
- Automatic backups before every change, one-click restore
- Hardened web root (`.htaccess` ships, Nginx example included)

## Who Jaco is for

Small static sites — existing or new — where a developer marks editable regions
once, then owners and editors keep content up to date on cheap shared hosting:
restaurants, cafés, hairdressers, tradespeople, clubs, freelancers, private sites.

If you need a shop, a database-backed application or real-time features,
Jaco is the wrong tool — it edits static pages, nothing more.

## Requirements

- PHP `^8.2` with `dom`, `libxml`, `openssl` (plus `zip` for the self-updater)
- Composer (only for installing from git; not needed with the dist archive)
- Apache or Nginx with PHP; `php -S` for development only

## Install in five minutes

Download `jaco-dist-<version>.zip` from the [releases page](https://github.com/tbreuss/jaco-cms/releases),
extract it next to your `index.html`, open `/jaco/install.php` and create your
login.

Mark editable regions in your HTML and log in at `/jaco/`:

```html
<h1 data-jaco-line>My headline</h1>
<div data-jaco-block><p>My paragraph.</p></div>
```

For server setup, markers and recurring content blocks, see the [full guide](docs/install.md).

## Development

[Working from git](docs/development.md) · [Contributing](docs/contributing.md) · [Doc index](docs/readme.md)

## License

Freeware — use it commercially, no licence validation, no phone home.
If no stable release ships for 365 days, the source falls back to MIT.
See [LICENSE](LICENSE).

## Links

[Documentation](docs/readme.md) · [Installation guide](docs/install.md) · [Development](docs/development.md) · [Releases](https://github.com/tbreuss/jaco-cms/releases)
