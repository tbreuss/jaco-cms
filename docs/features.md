# Jaco CMS – features and constraints

Jaco CMS is a flat-file CMS: the pages *are* the database. There is no central feature
list to maintain – the behaviour is pinned by the tests in `jaco/test`, and a test that
goes red is more precise than a paragraph.

This file keeps the two things tests cannot express: **what the words mean** and **which
constraints are easy to break**. All paths are relative to the repository root.

## Glossary

Ambiguity usually starts with words, not sentences. These terms have exactly one meaning.

| Term | Meaning |
| --- | --- |
| Page | An `.htm`/`.html` file below the web root (`Config::$webDir`), excluding `jaco/`. The file is the data store – there is no database. |
| Stylesheet | A `.css` file below the web root, excluding `jaco/`, `jaco/_backup` and the `inspiration` directory. Listed, viewed and written through `css/list`, `css/view` and `css/update`. |
| Editable marker | A `data-jaco-*` attribute in page HTML. Six variants: `data-jaco-editable` (block, rich text), `data-jaco-editable-line` (single line, block tags stripped), `data-jaco-editable-image` (image swap, size from `width`/`height`), `data-jaco-editable-image-extended` (swap plus alt text and fit/fill), `data-jaco-editable-image-gallery` (thumbnail links to the full version, stored as `name-full.ext`), `data-jaco-editable-link` (`href` and `target`). |
| Content list | A `<div data-jaco-content-list>` with `data-jaco-fragment` (a file from `jaco/_template/content/`), `data-jaco-title` (add-button label) and `data-jaco-pos` (`top`, `bottom`, `any` or `draggable`). Blocks live in `<div data-jaco-content-item>` and are added/removed in the inline editor; `draggable` adds pointer-based drag reorder via a grip handle (with auto-scroll, no library). Served through `template/content` and `template/content/view`. |
| Inline edit | Editing a page in the iframe that shows the live site (route `/page-edit`, `jaco/frontend/editor.js` injected into the iframe, saved through `page/update-body`). |
| Upload | A file in `upload/`. Allowed extensions: `png`, `gif`, `jpg`, `jpeg`, `svg`, `pdf` (`Config::$uploadExtensions`). |
| Backup | A copy in `jaco/_backup`, taken before a page, stylesheet or upload is overwritten or deleted. Name: `<YYYYMMDD>_<HHMMSS>_<4 chars>_<file>`, where `/` in the file path becomes `|`. |
| Page template | A file in `jaco/_template/page`, used when a page is created. Listed through `template/pages`. |
| Block template | A file in `jaco/_template/content`, a single reusable block (e.g. a news item). Listed through `template/content`, fetched through `template/content/view`. |
| Block list | No file: `<div data-jaco-content-list>` markup on a page that references a block template and lets editors add/remove blocks inline. Blocks live in `<div data-jaco-content-item>`. |
| Config | The injected `jaco\core\config\Config` value object: directories (including the configurable `uploadDir`, always inside the web root), extensions, credentials, `defaultLanguage`, `maintenanceMode` and the php error settings. It replaced the former `jaco\*` constants; `jaco/settings.php` overrides the defaults. Empty credentials and short secret keys fall back to random values, so logging in stays impossible until real values are set (fail closed, see below). |
| Domain payload | Return value of a `jaco\domain\*` function: `success`, `invalid` (HTTP 422 with an error list) or `error` (HTTP 500). |
| API action | A route registered in `jaco/routes.php` (`action=page/update`). |
| Session token | JWT signed with HS256, issued by `POST action=login`, sent as `Authorization: Bearer <token>`. |
| Two-factor authentication | Optional TOTP second login step (RFC 6238, `jaco/backend/core/totp.php`). Offline by design: no SMS, mail or network. The secret lives in `jaco/settings.php` next to the password, enrollment runs through `auth:totp-setup` or the installer checkbox. |
| API vs. domain names | The page manager speaks `fileName` + `html`, the inline editor `fileName` + `body`; the domain functions speak `file` + `withContent` and `file` + `withBody`. |

## Constraints that are easy to break

- **Focus needs a visible document.** `jaco/index.html` keeps the document at
  `visibility:hidden` until the window `load` event, and hidden elements cannot take the
  focus. `jaco/frontend/component/Input.js` focuses immediately *and* again on `load` if
  the first attempt did not take effect.
- **Nothing is touched without a backup.** `page/update`, `page/update-body`,
  `page/delete`, `css/update` and `upload/delete` write a copy into `jaco/_backup` before
  they modify a file (`jaco/backend/domain/page.php`, `css.php`, `upload.php`).
- **The app directory is out of reach.** Page create, update, update body, delete, copy
  (source *and* target) and view, `css/update` and `upload/create` all reject paths whose
  first segment is `jaco/` (the `file_in_dir` guard), and the page, stylesheet, upload and
  backup lists never show files from `jaco/`.
- **The JSON schemas are the API contract.** `jaco/schema/**` defines what a request must
  contain and what a response may contain; `jaco/backend/core/router.php` validates both.
- **Embedded images are extracted, not stored.** `page/update` and `page/update-body`
  write an image file only
  when the `<img>` carries a marker attribute, a `src` and `data-jaco-editable-image-file`
  (`jaco/backend/core/html.php`). Afterwards that attribute is gone and `src` points to
  `upload/<file>`. The parser clears the global libxml error buffer first, because the
  check at the end reads all of it – a leftover error from earlier parsing would turn a
  valid save into a failure.
- **Two save paths with different owners.** `page/update` is the page manager: the whole
  document is stored as sent – doctype, `<head>`, `<meta>`, `<base>`, `<link>` and
  `<script>` included – and only the body is sanitized. `page/update-body` is the inline
  editor: it sends content, never a document, so the stored document keeps its doctype and
  head. A page that is stored as a fragment stays a fragment. Trust boundary: whoever holds
  a valid token has the page manager, which is file access.
- **Only YouTube embeds survive sanitizing.** `iframe` elements stay only with a `src` below `https://www.youtube.com/embed/` or `https://www.youtube-nocookie.com/embed/`; anything else (including a stripped `src`) is removed with the node. Event handlers never survive, on any tag.
- **Stylesheets are content, but not HTML.** `css/update` stores the file as sent:
  `html\sanitize_html()` would destroy a stylesheet, and a `.css` file served as
  `text/css` carries no script vector. Only `.css` files below the web root are accepted,
  `jaco/` is out of reach, and the previous version is backed up first
  (`jaco/backend/domain/css.php`).
- **Maintenance mode blocks writes, not reads.** With `maintenanceMode` in
  `settings.php` every mutating route (page create, update, update body, delete, copy,
  `css/update`, `upload/create`, `upload/delete`, `backup/restore`, `backup/delete`)
  answers with the maintenance response, while lists and views keep working. Every api
  response also carries the flag as an `X-Maintenance-Mode` header (`jaco/api.php`);
  the admin reads it off the response instead of polling, so the banner follows the
  setting without a reload (`jaco/frontend/lib/maintenance.js`).
- **Login attempts are throttled per address, never globally.** Three failures from one
  address within ten minutes block that address for ten minutes; a blocked attempt counts
  again, so hammering keeps its own block alive. Rotating usernames does not help (the
  bucket is the address, not the name) and the response never reveals whether a name
  exists. There is no permanent block, so nobody can lock the owner out, and a successful
  login clears that address; `cli.php auth:unblock` clears all of them. Command line
  callers have no address and are not throttled. The history keeps at most
  `LOGIN_MAX_ADDRESSES` addresses and drops everything outside the window
  (`jaco/backend/core/auth.php`).
- **The password changes through the api, not by editing the file.**
  `system/change-password` takes the current password as proof – a wrong one counts
  against the login throttle – and writes a new `password_hash()` digest through
  `write_setting()`, which keeps the comments and the other keys of `settings.php` and
  parses the result before it replaces the file. The route is deliberately exempt from
  the maintenance block: it is the way back in when something went wrong
  (`jaco/backend/domain/system.php`, `jaco/backend/core/config.php`).
- **Two-factor codes count as attempts.** A wrong TOTP code records a failure like a
  wrong password, and a correct password without a code only reveals that a second
  step is required (`totpRequired`), never a token. There are no backup codes:
  a lost secret is replaced through `auth:totp-setup`, which needs server access.
- **Uploads are never overwritten.** A name collision gets `-1` appended
  (`filesystem\file_name_to_write()`), and `upload/create` validates the extension before
  the uploaded file is moved into place.
- **Credentials fail closed, never open.** `create_default_config()` treats empty
  `username`/`password`/`secretKey` like missing ones (random fallbacks) and
  replaces a `secretKey` below 32 characters; `auth\authenticate()` additionally
  refuses empty stored values. A verbatim copy of `settings.dist.php` can
  therefore never log in and never signs tokens with the example key
  (`jaco/backend/core/config.php`, `jaco/backend/core/auth.php`,
  `jaco/test/ConfigDefaultsTest.php`).
- **The admin password is stored hashed, never in clear.** `settings.php` holds a
  `password_hash()` digest, verified with `password_verify()` (itself constant time).
  A plaintext value no longer matches, because it is not a valid hash. There is no
  migration path: an installation with a plaintext password has to have a hash
  written into `settings.php` (`php -r "echo password_hash('...', PASSWORD_DEFAULT);"`)
  or reinstalled. The installer and `auth:token` are unaffected — both take the
  plaintext and verify it against the stored digest
  (`jaco/install.php`, `jaco/backend/core/auth.php`).
- **Uploads are scrubbed when they are SVG.** `svg` stays an allowed extension,
  but `html\sanitize_svg()` strips script elements, foreign objects, event
  handler attributes and script urls on every write path (direct upload,
  editor data urls, gallery full versions) before the file reaches `upload/`.
  Malformed markup is rejected instead of guessed
  (`jaco/backend/core/html.php`).
- **The app directory guard ignores case.** `filesystem\is_file_in_dir()`
  compares the first path segment case-insensitively, because `Jaco/...`
  resolves to the app directory on macOS and Windows
  (`jaco/backend/core/filesystem.php`, `jaco/test/FileSystemNamingTest.php`).
- **The editable markers are allowlisted.** `data-jaco-editable`, `-line`, `-link`, `-image`
  and `-image-extended` must stay in `ALLOWED_ATTRIBUTES`: dropping one silently makes the
  page uneditable in place, because the editor finds its blocks through these attributes.
  `<script>` is removed from the body, so a script include belongs in the `<head>`, which
  the page manager owns.
- **Lists do not ship content.** `page/list` and `upload/list` return `content: null`,
  `css/list` does not even carry a content field (`additionalProperties: false` in its
  schema), and `backup/list` returns an empty string. Only `page/view`, `css/view` and
  `backup/view` return the body – for an upload inside a backup as base64.
- **Sort parameters are validated per resource.** A field can appear in the response and
  still not be sortable: `sort=file` is rejected for backups, `sort=name` for uploads.
- **Titles are read as UTF-8.** `html\get_title()` prepends a charset hint before parsing.
  A plain `DOMDocument::loadHTML()` assumes ISO-8859-1 and turns `Rückgabe` into
  `RÃ¼ckgabe` for pages without a `<meta charset>`.
- **Paths are relative to the web root.** Page, stylesheet and upload names always contain
  their directory (`upload/picture.jpg`), because the file search starts at
  `Config::$webDir`.
- **`backup/restore` moves instead of copying, and trusts the name.** The destination comes
  from the backup name (`|` becomes `/`), the backup is renamed onto it and the file
  timestamp is restored from the name. Pass only names that came from `backup/list`: a
  backup of an app file would be written back into `jaco/`.
- **The login form submits natively.** `jaco/frontend/view/LoginView.js` wraps the fields
  in a `<form>` and cancels the submit event, so RETURN and the submit button each send
  exactly one request and the page never reloads.
- **Save shortcuts listen in two places.** `bindSaveShortcut` in `jaco/frontend/lib/shortcuts.js`
  covers Cmd/Ctrl+S (save) and Cmd/Ctrl+Shift+S (save and close). Ace swallows keydown
  inside its textarea, so the Ace editors need native commands too
  (`editor.commands.addCommand` in `PageEditView.js`, `CssEditView.js`); the inline
  editor needs listeners in both the parent and the iframe document, unbound in
  `onremove`, or shortcuts leak across routes.
- **The theme is an explicit choice, not just the OS setting.** `jaco/frontend/lib/theme.js`
  persists light/dark/auto in localStorage and applies it as `data-theme` on `<html>`
  (`:root[data-theme="dark"]` carries the dark tokens, so no media query is needed).
  An inline script in `jaco/index.html` sets the attribute before CSS paints to avoid
  a flash; new theme-dependent colors belong in the `:root` tokens, never in rules.
  The project site shares that key: `assets/theme.js` in the head of every site page
  writes the same `jaco-theme`, and `assets/site.css` keeps its palette in `:root` tokens
  with `light-dark()`, so the automatic case follows the OS without a media query. The
  explicit `data-theme="dark"` block repeats the dark tokens on purpose: browsers without
  `light-dark()` (iOS < 17.5, Chrome < 123) would otherwise ignore a chosen dark theme.
  The switch button sits in the page markup; the script only wires it up, while the
  theme itself is still applied synchronously before the first paint. The mobile menu in
  `assets/nav.js` works the same way: its button sits in the markup and the open state
  is a class on `<html>`, which no save touches. It takes over below 40rem;
  the plain row needs 500px of content plus the page padding in german, so a smaller
  breakpoint makes the row wrap on tablets.
- **Server errors stay English on the wire and are translated in the frontend.**
  The backend has no per-request locale, so `detail` strings arrive in English and
  `tError()` in `jaco/frontend/model/i18n.js` translates them for display
  (`FormErrors.js`). A new static message needs a key in all four
  `jaco/language/*.json` files (guarded by `jaco/test/LanguageTest.php`); a new
  dynamic message additionally needs its English template in `errorTemplates`,
  otherwise it falls back to English.

- **The cli entry point is command line only.** `jaco/cli.php` answers a web request with
  403 before it loads anything (a `PHP_SAPI` guard): it prints version and log output and
  would hand that to anyone asking. `install.php` is the deliberate opposite – it is meant
  to be opened in the browser once, refuses to run as soon as `settings.php` exists and
  deletes itself on success.

## Where the rules live

| Area | Test |
| --- | --- |
| Auth tokens: issue and validation | `jaco/test/AuthTest.php` |
| Login throttling: per address, unblock, bounded history | `jaco/test/LoginThrottleTest.php` |
| Two-factor codes (RFC vectors) and login flow with secret | `jaco/test/TotpTest.php`, `jaco/test/ApiLoginTotpTest.php` |
| CLI entry point: web requests are refused | `jaco/test/CliEntryPointTest.php` |
| System environment and log clearing | `jaco/test/ApiSystemTest.php`, `jaco/test/ApiSystemLogClearTest.php` |
| Route table: public/private, methods | `jaco/test/ApiRoutesTest.php` |
| HTTP: request headers, query handling | `jaco/test/HttpRequestTest.php` |
| Pages: list, view, copy, create, update (document and body), delete | `jaco/test/ApiPage*Test.php` |
| Stylesheets: list, view, update | `jaco/test/ApiCss*Test.php` |
| Backups: list, view, restore, delete | `jaco/test/ApiBackup*Test.php` |
| Uploads: list, create, delete | `jaco/test/ApiUpload*Test.php` |
| Page templates, translations | `jaco/test/ApiTemplatePagesTest.php`, `jaco/test/ApiTranslationsTest.php`, `jaco/test/LanguageTest.php` |
| Shared base: real route table, token, fixture cleanup | `jaco/test/ApiTestCase.php` |
| Input sanitizing and validation rules | `jaco/test/SanitizeTest.php`, `jaco/test/ValidateTest.php` |
| HTML: sanitizing (XSS), titles, embedded images | `jaco/test/HtmlFunctionsTest.php`, `jaco/test/HtmlTitleTest.php`, `jaco/test/EmbeddedImagesTest.php` |
| Filesystem: naming, extensions, paths, timestamps | `jaco/test/FileFunctionsTest.php`, `jaco/test/FileSystemNamingTest.php` |
| Schema validation, error responses and field mapping | `jaco/test/SchemaValidationTest.php`, `jaco/test/ResponderMappingTest.php` |
| Json files: every `*.json` below `jaco/` parses | `jaco/test/JsonFilesTest.php` |
| Config defaults and masked diagnostics | `jaco/test/DefinedConstantsTest.php` |
| Credential defaults fail closed | `jaco/test/ConfigDefaultsTest.php` |
| Hardening checks (`.htaccess` files exist) | `jaco/test/SecurityChecksTest.php` |
| Authorization header on every SAPI | `jaco/test/ApiAuthHeaderTest.php` |
| Error responses hide internals | `jaco/test/ErrorHandlerTest.php` |
| Frontend components, page editor model, login form | `jaco/test/js/tests.js` |

Run them from the repository root (dev tools come from `composer install`):

    composer test --working-dir jaco

Options for the PHPUnit binary are appended after `--`, because the Composer
script forwards them: `composer test --working-dir jaco -- --filter AuthTest`.

Static analysis, code style and the full check run the same way:

    composer phpstan --working-dir jaco
    composer cs-check --working-dir jaco
    composer check --working-dir jaco

The frontend tests are QUnit tests that run in a real browser; they need node ≥ 22 and a
local Chrome and drive the page through the devtools protocol, so no npm packages are
involved. Open `jaco/test/js/index.html` in a browser while developing, or run them
headless:

    php -S 127.0.0.1:8899 -t . &
    node jaco/test/js/runner.mjs

The runner clears the login history before it starts. The suite logs in with a deliberately
wrong password and the api counts every failure towards the login throttle — three attempts
block the address for ten minutes. Reloading `jaco/test/js/index.html` in a browser bypasses
that reset, so unblock again once the third reload has failed:

    php jaco/cli.php auth:unblock

Not tested on purpose: the diagnostics endpoints (`system`, `system/log`,
`system/php-info`, all environment dependent; `system\defined_constants()` itself is
covered by `DefinedConstantsTest`) and the global `error_handler` and `fatal_handler`
(the `exception_handler` response shape is covered by `ErrorHandlerTest`).
Still without a test: restoring a backup whose original page no
longer exists.
