# Jaco CMS – Installation on an existing HTML site

Jaco manages static `.html` files directly in the web directory: no database setup,
no build step. This guide describes the installation on an already existing HTML site.

In release archives (`jaco-dist-*.zip`), this file is shipped as **`INSTALL.md`**
in the archive root; in the git repository it lives here as `docs/install.md`.

## Requirements

- PHP `^8.2` with the `dom`, `libxml` and `openssl` extensions
- A web server with PHP (Apache/nginx + php-fpm; `php -S` for development only)
- Composer, only for the alternative install from git (option B)

The `zip` extension is optional: Jaco runs without it, but self-updates need it
to extract a release archive (see [Updating](#updating)).

## A. Recommended: install from the ZIP archive

1. Download the ready-made `jaco-dist-<version>.zip` from the
   [releases page](https://github.com/tbreuss/jaco-cms/releases).
   It already contains the dependencies, so nothing has to be installed.
2. Extract the archive into the root of the existing site (next to `index.html`):

```
/var/www/my-site/
├── index.html
├── foo.html
├── media/         <-- created by the installer
├── jaco-data/     <-- settings, templates, backups and runtime, created by the installer
└── jaco/          <-- here
    ├── api.php
    ├── backend/
    ├── frontend/
    └── ...
```


   The three directories next to your pages have fixed roles:

   | Directory | Role | Access |
   |---|---|---|
   | `jaco/` | The application: admin UI, `api.php`, PHP code and `vendor/` | Only the entry points and `frontend/` assets are reachable, everything else answers 403 |
   | `jaco-data/` | Operator data written by the app: `settings.php`, page, content and site templates, the backups of every save and the runtime logs | Never reachable over the web (`.htaccess`: `Require all denied`, `Options -Indexes`) |
   | `media/` | Uploads from the editor (images, PDFs), referenced from your pages as `media/<file>` | Reachable as static files, but never executed |

   Replacing `jaco/` with a new version never touches `jaco-data/` or `media/`,
   so code updates cannot lose content, backups or credentials. A deployment
   replaces the application in `jaco/`, the public pages next to it and the
   templates under `jaco-data/templates/` - nothing else. Three entries in
   `jaco-data/` are never part of a deployment and are never deleted by it:
   `settings.php`, `backups/` and `runtime/`. They are created on the server
   (the installer writes `settings.php`), not shipped with the code.

   If a server still carries `settings.php`, `runtime/` or a nested `jaco/`
   inside the application directory, move the settings to
   `jaco-data/settings.php` before the next deployment: replacing the
   application directory would delete them with it.

3. Open the installer in the browser: `https://example.com/jaco/install.php`.
   Enter username (anything you like) and password (at least 8 characters),
   pick a language, and leave two-factor authentication enabled unless you
   deliberately want password-only login. Submitting writes `jaco-data/settings.php`
   (with a generated secret key) and the installer deletes itself afterwards.
   If it cannot delete itself, remove `jaco/install.php` by hand right away.

   The media directory defaults to `media` and may be changed, e.g. to
   `bilder/2026` for a site that groups images by year. The path is relative
   to the web root, may contain slashes, and the installer creates it (with
   its `.htaccess`) before writing the settings.
4. Log in at `/jaco/` with the credentials you just created. Jaco automatically
   creates a backup under `jaco-data/backups/` before every save.

The installer refuses to run once `jaco-data/settings.php` exists, so nobody else
can claim the installation afterwards. A lost TOTP secret is replaced through
`php jaco/cli.php auth:totp-setup -u <user>` (needs server access), so keep the secret
from the installer screen in your authenticator app.

## B. Alternative: install from git

1. Copy the `jaco/` directory of this repository into the root of the existing
   site (next to `index.html`, same layout as above).
2. Install dependencies:

    composer install -d jaco/ --no-dev

3. Either run the browser installer as in step A3, or create the login by hand:
   copy `jaco/settings.dist.php` to `jaco-data/settings.php` and fill it in:

```php
return [
    'users' => [
        'admin' => [
            // a password hash, never the plaintext password
            'password' => '<password-hash>',
            'role' => 'admin',
            // optional: 'language' => 'de', 'totpSecret' => '<base32-secret>',
            // for a public demo: 'readOnly' => true
        ],
    ],
    'secretKey' => 'at-least-32-random-characters',
    // optional, default 'media'
    'mediaDir' => 'bilder/2026',
    // optional, default 'jaco-data' (templates and backups)
    'dataDir' => 'jaco-data',
];
```

A public demo account sets `readOnly` to `true` and keeps `role: admin`: the
visitor sees the whole admin - editors, media, backups, templates - and every
write is refused with 403, the password change included. The password stays the
seeded one, so nobody can lock the others out. A plain `editor` would hide
backups, design, site templates and the system pages, which defeats the purpose
of a demo.

Hash a password you choose with:

    php jaco/cli.php auth:password --ask

Or generate a random password and its digest with:

    php jaco/cli.php auth:password

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
    'password' => '<password-hash>',
    'totpSecret' => '<base32-secret>',
    'secretKey' => 'at-least-32-random-characters',
];
```

becomes

```php
return [
    'users' => [
        'admin' => [
            'password' => '<password-hash>',
            'role' => 'admin',
            'totpSecret' => '<base32-secret>',
        ],
    ],
    'secretKey' => 'at-least-32-random-characters',
];
```

Move the old global `totpSecret` into the record of the one user who used it;
a top-level `totpSecret` is no longer read. Every further account gets its own
record and, if wanted, its own secret. If the old `password` is still plaintext,
replace it with a password hash as shown above.

## Grant write permissions

The PHP process must be allowed to write:

- every editable `.html` file
- `jaco-data/` (templates, backups, runtime log)
- the media directory (`media/` by default)
- `jaco-data/runtime/log/` (error and login log)

## Deploy to a remote server

The steps above assume you can run commands on the server. If you deploy over
the network (FTP, SFTP, SCP, rsync), a few points matter:

- **Extract locally, upload the tree.** Unpack the archive on your machine and
  upload the whole site (pages, `jaco/`, and the empty `jaco-data/` and
  `media/` directories) with your client. Uploading the ZIP and extracting on
  the server is faster but needs shell access.
- **Keep hidden files.** `.htaccess` files start with a dot; many FTP/SFTP
  clients hide them by default. Enable "show hidden files" before uploading,
  or the protection rules and the `Authorization` header forwarding are
  silently skipped. Check afterwards with `curl` (see below).
- **Preserve directory structure.** `jaco/`, `jaco-data/` and `media/` must sit
  next to `index.html` in the web root, not inside a subdirectory.
- **Set write permissions.** The web server user (often `www-data`, `apache`
  or the FTP account) needs write access to every editable `.html` file,
  `jaco-data/` (templates, backups, runtime log) and `media/`. With SFTP/SCP
  this is usually `chmod 644` for files and `755` for directories, owned by
  the web server user; with shared hosting, the FTP account often is the
  web server user.
- **Do not upload `jaco-data/settings.php`, backups or runtime logs** from a
  local installation to a production server. The installer creates them on the
  server; overwriting them can lose credentials or backups.

## Protect the internals

Jaco lives entirely in the web root, so the web server must deny direct
access to the internals. On Apache this ships with the project:

- `jaco/.htaccess` allows only `api.php` and `index.html` and denies everything
  else in that directory, so an unlisted file is never served.
- `jaco/frontend/.htaccess` allows only `.js`, `.mjs` and `.css`.
- `jaco/backend/`, `jaco/language/`, `jaco/schema/`,
  `jaco/vendor/` and `jaco/test/` deny every request.
- `jaco-data/` denies every request.
- `media/` serves images and PDFs and never executes PHP (the installer writes
  its `.htaccess` when creating the directory).

The whitelist depends on `jaco/frontend/.htaccess`: deleting it locks the admin
out, so keep that file in place when you deploy.

`jaco/vendor/` only gets its `.htaccess` from `composer install`; a manual
install that skips it shows up as a red **Vendor protection (.htaccess)** check
on the System page. Create it by hand if needed:

    printf '%s\n' '# Third-party code: never serve directly.' 'Require all denied' > jaco/vendor/.htaccess

Verify that hidden files are deployed (some SCP clients skip dotfiles), and
that Apache honours them: `AllowOverride` must at least allow `AuthConfig` and
`FileInfo` (usually `AllowOverride All`). With `AllowOverride None` every
`.htaccess` is ignored and `jaco-data/` becomes readable over the web.

On Nginx `.htaccess` files are ignored: copy the location blocks from
`nginx-jaco.conf.example` into the server context, and add
`fastcgi_param HTTP_AUTHORIZATION $http_authorization;` to the PHP location.

On Apache with a dist archive, one rule must live in the *site root*
`.htaccess` (the shipped `jaco/.htaccess` enables no rewrite engine):

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTP:Authorization} ^(.+)$
    RewriteRule ^ - [E=HTTP_AUTHORIZATION:%1]
</IfModule>
```

### Browser cache after a release

`index.html` points at unversioned files (`frontend/app.css`, `frontend/app.js`).
The shipped root `.htaccess` therefore sends `Cache-Control: no-cache,
must-revalidate` for HTML, CSS and JavaScript, and the Nginx example does the
same. Browsers keep the files but revalidate them, so a release is picked up
without a hard refresh.

Do not switch these files to `immutable`/long-lived caching until their names
are content-hashed and the ES-module imports are rewritten. Uploaded images and
other media can use longer lifetimes because their names are unique.

Check after a deployment:

```bash
curl -sI https://example.com/jaco/frontend/app.js | grep -i cache-control
```

Expected:

```text
Cache-Control: no-cache, must-revalidate
```

The admin panel reports the state under System environment: the four
`.htaccess` checks (app, backup, log, vendor) verify that the protection
files exist. On Nginx they stay green once the files are deployed; the
location blocks themselves cannot be verified from PHP and must be
checked once by hand:

    curl -I https://example.com/jaco-data/settings.php  # expect 403
    curl -I https://example.com/jaco-data/backups/      # expect 403

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
Editable regions are marked with `data-jaco-*` attributes. Every attribute,
property and value is defined in [jaco-data/templates/content/](jaco-data/templates/content/)
and documented in [docs/attribute-contract.md](attribute-contract.md). The most
important ones:

- `data-jaco-block` — multi-line text block
- `data-jaco-line` — single line (e.g. headings)
- `data-jaco-link` — link (URL and target via dialog)
- `data-jaco-image` — image (replacement via dialog)
- `data-jaco-template="<file>.html"` — instance of a site template
- `data-jaco-repeater` — repeatable block list
- `data-jaco-slot` — flexible insertion point with chooser

Example:

```html
<h1 data-jaco-line>My headline</h1>
<div data-jaco-block><p>My paragraph.</p></div>
<a href="page.html" data-jaco-link>My link</a>
<img data-jaco-image src="media/image.jpg" alt="Image" width="800">
```

A live example of every marker is on `elements.html` (English and German).

## Recurring content blocks

Put a fragment file in `jaco-data/templates/content/` and mark where blocks may
appear with `data-jaco-repeater="<file>.html"` (see
[Repeater](attribute-contract.md#repeater)). The fragment must have exactly one
root element; every direct child of the container becomes one block.

## Flexible insertion points (slots)

A slot lets editors choose which block to insert from a set of fragments.
Mark the container with `data-jaco-slot="<file>.html"` (see
[Slot](attribute-contract.md#slot)). Every direct child of the slot is one block.

## Embedded videos

In a text block (`data-jaco-block`), the toolbar video button embeds a
video from its URL. Only YouTube embeds survive saving
(`https://www.youtube.com/embed/…` and `https://www.youtube-nocookie.com/embed/…`);
any other iframe source is removed, along with event handler attributes.

## Image gallery

**Being redesigned.** The gallery is gone from this version: an image is an image,
the editor replaces the file and nothing else. A save does not write a second file.

The model that replaces it gives the author both files and a container that marks
the group - a `div` with an attribute, holding one link per image:

```text
<container> -> <a href=".../cake.jpg"> -> <img src=".../cake-thumb.jpg">
```

The editor reads the sizes from `width`/`height`, scales the uploaded file to that
size for the thumbnail and caps the full version at 1920 px. Until that exists, the
container attribute is not in the whitelist either, and a save removes it.

The editor still sets `data-jaco-editable-image-file`,
`data-jaco-editable-image-temp` and `data-jaco-is-editing` itself at runtime; they do
not belong in the source HTML.

## Keyboard shortcuts

In the edit views, `Cmd/Ctrl+S` saves and `Cmd/Ctrl+Shift+S` saves and
closes (where a Save & Close button exists: page, CSS and JS editor). The
browser's own save dialog is suppressed while an edit view is open.

## Updating

Jaco can update itself from a published release. Only the `jaco/` application
directory is replaced; `jaco-data/settings.php`, `backups/`, `runtime/`,
`templates/`, `media/` and the public pages are left untouched. Breaking
changes (new `settings.php` keys, changed template markers, PHP version
requirements) are **not** migrated automatically — check the release notes.

### Check for updates

From the command line:

    php jaco/cli.php sys:check-update

Or from the admin: System → "Check for updates".

### Install an update

From the command line (latest release):

    php jaco/cli.php sys:update

Or a specific release or direct URL:

    php jaco/cli.php sys:update -t v0.2.0
    php jaco/cli.php sys:update -u file:///srv/downloads/jaco-dist-v0.2.0.zip

`-t` takes a release tag (`v0.2.0`), not a branch such as `main`: a branch has
no published release or checksum and is refused before any request.

Or from the admin: System → "Install update". The update runs with an extended
time limit; keep the browser tab open until it finishes. A backup of the
previous `jaco/` is written as a directory to
`jaco-data/app-backups/jaco-<version>-<timestamp>/` (only the 5 newest
backups are kept) and can be restored by copying it back over `jaco/`.

Installing needs the `zip` PHP extension. On a host without it, checking for
updates still works, `sys:update` answers with a hint to enable `php-zip`, and
nothing is downloaded or changed.

### Release integrity

A release archive replaces the whole application directory, so it is executed
as code. Jaco installs an archive only when both hold:

- the URL is the `jaco-dist-*.zip` asset of a release in
  `github.com/tbreuss/jaco-cms` (GitHub's redirect to its own download CDN is
  followed),
- the archive matches the `jaco-dist-*.zip.sha256` file published next to it in
  the same release. Without that file, with a different digest, or on a
  redirect anywhere else, the update is refused and `jaco/` is left alone.

`file://` archives are accepted from the CLI only: there the operator vouches
for the file on the server. A web request cannot install a local file, and no
setting can override these rules.

### What happens during an update

1. Fetch the latest release from GitHub and compare versions.
2. Download and verify the dist archive (host + SHA-256).
3. Extract the archive (needs `zip` extension).
4. Back up the current `jaco/` to `jaco-data/app-backups/`.
5. Replace `jaco/` contents with the new version.
6. Verify `jaco/api.php` exists; restore backup on failure.
7. Clean up temporary files.

## Log in

Open `/jaco/` in the browser and log in with the credentials you created in
the installer (or in `jaco-data/settings.php` for a manual install). Jaco automatically
creates a backup under `jaco-data/backups/` before every save.
