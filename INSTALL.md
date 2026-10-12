# Jaco CMS – Installation on an existing HTML site

Jaco manages static `.html` files directly in the web directory: no database setup,
no build step. This guide describes the installation on an already existing HTML site.

## Requirements

- PHP `^8.2` with the `dom` and `libxml` extensions
- Composer
- A web server with PHP (Apache/nginx + php-fpm; `php -S` for development only)

## 1. Place the files

Copy the `jaco/` directory of this repository into the root of the existing
site (next to `index.html`):

    /var/www/my-site/
    ├── index.html
    ├── foo.html
    ├── upload/
    └── jaco/          <-- here
        ├── api.php
        ├── backend/
        ├── frontend/
        └── ...

The admin panel is then available at `/jaco/`, the page preview points to `/`.

> Tip: download the ready-made `jaco-dist-<version>.zip` from the
> [releases page](https://github.com/tbreuss/jaco-cms/releases) instead.
> It already contains the dependencies, so step 2 is not needed:
> extract the archive into the site root and continue with step 3.

## 2. Install dependencies

    composer install -d jaco/ --no-dev

## 3. Create the login

Copy `jaco/settings.dist.php` to `jaco/settings.php` and fill it in:

```php
return [
    'username' => 'admin',
    'password' => 'secure-password',
    'secretKey' => 'at-least-32-random-characters',
];
```

Without `settings.php`, username and password fall back to random values
and logging in is not possible.

## 4. Grant write permissions

The PHP process must be allowed to write:

- every `.html` file that should be editable
- `jaco/_backup/` (created with the first backup, create it beforehand if needed)
- `upload/` in the root directory (images from the editor)
- `jaco/runtime/log/` (error log)

## File upload limits

Uploads are limited by PHP, not by Jaco:

- per file: `upload_max_filesize` (default 2 MB)
- per request: `post_max_size` (default 8 MB)

Oversized files are rejected with an error message. To allow larger
files, raise the limits where PHP is configured, e.g. in `php.ini`:

```ini
upload_max_filesize = 20M
post_max_size = 24M
```

## 5. Mark editable regions

Without markers there is nothing to edit inline (only source via Ace).
These attributes designate editable spots in the existing HTML:

- `data-jaco-editable` — text block (Quill editor with full toolbar)
- `data-jaco-editable-line` — single line, e.g. headings
- `data-jaco-editable-link` — link (URL and target via dialog)
- `data-jaco-editable-image` — image (replacement via dialog)
- `data-jaco-editable-image-extended` — image with scaling options

Example:

```html
<h1 data-jaco-editable-line>My headline</h1>
<div data-jaco-editable><p>My paragraph.</p></div>
<a href="page.html" data-jaco-editable-link>My link</a>
<img data-jaco-editable-image src="upload/image.jpg" alt="Image" width="800">
```

## Recurring content blocks

A content list lets editors add prebuilt blocks (e.g. news items) themselves.
Put a fragment file in `jaco/_template/content/` (see the samples there),
then mark where blocks may appear:

```html
<div data-jaco-content-list data-jaco-fragment="sample_news_section.html" data-jaco-title="Add news" data-jaco-pos="top">
    <div data-jaco-content-item>
        <h2 data-jaco-editable-line>Existing headline</h2>
    </div>
</div>
```

- `data-jaco-fragment` names the fragment file from `jaco/_template/content/`
- `data-jaco-title` labels the add button shown in the editor
- `data-jaco-pos` is `top` (new blocks first), `bottom` (new blocks last),
  `any` (a button before every block plus one at the end) or `draggable`
  (like `top`, plus blocks can be reordered with the ⠿ grip)
- existing blocks are wrapped in `<div data-jaco-content-item>` and can be
  deleted in the editor (two-step: ×, then ✓)

## Embedded videos

In a text block (`data-jaco-editable`), the toolbar video button embeds a
video from its URL. Only YouTube embeds survive saving
(`https://www.youtube.com/embed/…` and `https://www.youtube-nocookie.com/embed/…`);
any other iframe source is removed, along with event handler attributes.

## Image gallery

An image with `data-jaco-editable-image-gallery` (in addition to
`data-jaco-editable-image`) links its thumbnail to the full version:

```html
<img data-jaco-editable-image data-jaco-editable-image-gallery width="800" src="upload/thumb.jpg" alt="Gallery">
```

When a file is picked in the editor, the thumbnail follows the `width`/`height`
attributes as usual, while the full version is capped at 1920px wide. On save
both files are stored (`thumb.jpg` and `thumb-full.jpg`) and the thumbnail is
wrapped in `<a href="upload/thumb-full.jpg">`. An explicit parent link always
wins and stays untouched.

The editor sets the attributes `data-jaco-editable-image-file`,
`data-jaco-editable-image-temp` and `data-jaco-is-editing` itself at runtime;
they do not belong in the source HTML. The same holds for
`data-jaco-gallery-full`, which carries the full version to the server on save
and is removed there.

## Keyboard shortcuts

In the edit views, `Cmd/Ctrl+S` saves and `Cmd/Ctrl+Shift+S` saves and
closes (where a Save & Close button exists: page, CSS and JS editor). The
browser's own save dialog is suppressed while an edit view is open.

## 6. Log in

Open `/jaco/` in the browser and log in with the username and password from
`settings.php`. Jaco automatically creates a backup under `jaco/_backup/`
before every save.
