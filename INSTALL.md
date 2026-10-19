# Jaco CMS – Installation on an existing HTML site

Jaco manages static `.html` files directly in the web directory: no database setup,
no build step. This guide describes the installation on an already existing HTML site.

## Requirements

- PHP `^8.2` with the `dom`, `libxml` and `openssl` extensions
- A web server with PHP (Apache/nginx + php-fpm; `php -S` for development only)
- Composer, only for the alternative install from git (option B)

## A. Recommended: install from the ZIP archive

1. Download the ready-made `jaco-dist-<version>.zip` from the
   [releases page](https://github.com/tbreuss/jaco-cms/releases).
   It already contains the dependencies, so nothing has to be installed.
2. Extract the archive into the root of the existing site (next to `index.html`):

    /var/www/my-site/
    ├── index.html
    ├── foo.html
    ├── upload/
    └── jaco/          <-- here
        ├── api.php
        ├── backend/
        ├── frontend/
        └── ...

3. Open the installer in the browser: `https://example.com/jaco/install.php`.
   Enter username (anything you like) and password (at least 8 characters),
   pick a language, and leave two-factor authentication enabled unless you
   deliberately want password-only login. Submitting writes `jaco/settings.php`
   (with a generated secret key) and the installer deletes itself afterwards.
   If it cannot delete itself, remove `jaco/install.php` by hand right away.

   The upload directory defaults to `upload` and may be changed, e.g. to
   `bilder/2026` for a site that groups images by year. The path is relative
   to the web root, may contain slashes, and the installer creates it (with
   its `.htaccess`) before writing the settings.
4. Log in at `/jaco/` with the credentials you just created. Jaco automatically
   creates a backup under `jaco/_backup/` before every save.

The installer refuses to run once `jaco/settings.php` exists, so nobody else
can claim the installation afterwards. A lost TOTP secret is replaced through
`php jaco/cli.php auth:totp-setup -u <user>` (needs server access), so keep the secret
from the installer screen in your authenticator app.

## B. Alternative: install from git

1. Copy the `jaco/` directory of this repository into the root of the existing
   site (next to `index.html`, same layout as above).
2. Install dependencies:

    composer install -d jaco/ --no-dev

3. Either run the browser installer as in step A3, or create the login by hand:
   copy `jaco/settings.dist.php` to `jaco/settings.php` and fill it in:

```php
return [
    'users' => [
        'admin' => [
            // a password_hash() digest, never the plaintext password
            'password' => '$2y$...',
            'role' => 'admin',
            // optional: 'language' => 'de', 'totpSecret' => '...',
            // for a public demo: 'passwordChange' => false
        ],
    ],
    'secretKey' => 'at-least-32-random-characters',
    // optional, default 'upload'
    'uploadDir' => 'bilder/2026',
];
```

A public demo account should set `passwordChange` to `false`: its password is
known, and without the flag the first visitor would lock everyone else out by
changing it. The flag is per account, not a role, so ordinary viewers keep the
right to change their own password. A viewer can still try the inline editor,
but its save button is disabled, the save shortcuts are not bound, and nothing
is written.

Generate the password digest with:

    php -r "echo password_hash('your-password', PASSWORD_DEFAULT), PHP_EOL;"

Without `settings.php` there is no user, and logging in is not possible. For
two-factor login without the installer, enrol the named user:

    php jaco/cli.php auth:totp-setup -u admin

Enter the printed secret in an authenticator app. Only that user then asks for
the password first and the six-digit code second; every other account keeps
its own setting. `auth:totp-disable -u admin` removes it again. A token from
the command line needs the code as well:

    php jaco/cli.php auth:token -u admin -p secret -c 123456

## Upgrading from a single login

There is no automatic migration: `settings.php` must list `users`. An old file

```php
return [
    'username' => 'admin',
    'password' => '$2y$...',
    'totpSecret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
    'secretKey' => 'at-least-32-random-characters',
];
```

becomes

```php
return [
    'users' => [
        'admin' => [
            'password' => '$2y$...',
            'role' => 'admin',
            'totpSecret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
        ],
    ],
    'secretKey' => 'at-least-32-random-characters',
];
```

Move the old global `totpSecret` into the record of the one user who used it;
a top-level `totpSecret` is no longer read. Every further account gets its own
record and, if wanted, its own secret. If the old `password` is still plaintext,
replace it with a `password_hash()` digest as shown above.

## Grant write permissions

The PHP process must be allowed to write:

- every `.html` file that should be editable
- `jaco/_backup/` (created with the first backup, create it beforehand if needed)
- the upload directory in the root directory (images from the editor), `upload/` by default
- `jaco/runtime/log/` (error log)

## Protect the internals

Jaco lives entirely in the web root, so the web server must deny direct
access to the internals. On Apache this ships with the project: `jaco/`
and every sensitive subdirectory (`_backup/`, `runtime/`, `vendor/`,
`backend/`, `test/`, `schema/`, `language/`, `_template/`) contain an
`.htaccess` with `Require all denied`, and `upload/` never executes PHP
(the installer writes its `.htaccess` when creating the directory).
Verify that hidden files are deployed (some SCP clients skip dotfiles).

On Nginx `.htaccess` files are ignored: copy the location blocks from
`docs/nginx-jaco.conf.example` of this repository
(<https://github.com/tbreuss/jaco-cms/blob/main/docs/nginx-jaco.conf.example>)
into the server context, and add
`fastcgi_param HTTP_AUTHORIZATION $http_authorization;`
to the PHP location, otherwise every authenticated API call answers 403.

On Apache with a dist archive (`jaco/` extracted into an existing site) one
rule must live in the *site root* `.htaccess`, because the shipped
`jaco/.htaccess` deliberately enables no rewrite engine (it would override
this): without it PHP never sees the `Authorization` header and every
authenticated API call answers 403.

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTP:Authorization} ^(.+)$
    RewriteRule ^ - [E=HTTP_AUTHORIZATION:%1]
</IfModule>
```

The admin panel reports the state under System environment: the four
`.htaccess` checks (app, backup, log, vendor) verify that the protection
files exist. On Nginx they stay green once the files are deployed; the
location blocks themselves cannot be verified from PHP and must be
checked once by hand:

    curl -I https://example.com/jaco/settings.php  # expect 403
    curl -I https://example.com/jaco/_backup/      # expect 403

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

## Mark editable regions

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

## Log in

Open `/jaco/` in the browser and log in with the credentials you created in
the installer (or in `settings.php` for a manual install). Jaco automatically
creates a backup under `jaco/_backup/` before every save.
