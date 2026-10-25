# Constraints that are easy to break

Feature summaries live in [features.md](features.md); shared terms in [glossary.md](glossary.md).
These are the invariants and edge cases that tests cannot express on their own.

- **Focus needs a visible document.** `jaco/index.html` keeps the document at
  `visibility:hidden` until the window `load` event, and hidden elements cannot take the
  focus. `jaco/frontend/component/Input.js` focuses immediately *and* again on `load` if
  the first attempt did not take effect.
- **Nothing is touched without a backup.** `page/update`, `page/update-body`,
  `page/delete`, `css/update` and `upload/delete` write a copy into `jaco-data/backups` before
  they modify a file (`jaco/backend/domain/page.php`, `css.php`, `upload.php`).
- **The app directory is out of reach.** Page create, update, update body, delete, copy
  (source *and* target) and view, `css/update` and `upload/create` all reject paths whose
  first segment is `jaco/` (the `file_in_dir` guard), and the page, stylesheet, upload and
  backup lists never show files from `jaco/`.
- **Operator data lives outside the app directory.** Templates and backups are stored in
  `Config::$dataDir` (default `<webDir>/jaco-data`, overridable through the `dataDir`
  setting), split into `templates/{page,content,site}` and `backups`.
  `jaco-data/.htaccess` denies every request and disables directory listings, so replacing
  the `jaco/` directory never touches site data. Uploads live in `Config::$mediaDir`
  (default `<webDir>/media`).
- **The JSON schemas are the API contract.** `jaco/schema/**` defines what a request must
  contain and what a response may contain; `jaco/backend/core/router.php` validates both.
- **Embedded images are extracted, not stored.** `page/update` and `page/update-body`
  write an image file only
  when the `<img>` carries a marker attribute, a `src` and `data-jaco-editable-image-file`
  (`jaco/backend/core/html.php`). Afterwards that attribute is gone and `src` points to
  `media/<file>`. The parser clears the global libxml error buffer first, because the
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
- **A read-only account is a real admin that may not store anything.** The user
  record carries `readOnly => true`; `read_token()` reads it from the current
  record, so flipping the flag takes effect without waiting for the token to
  expire. `router\dispatch()` answers every POST of such an account with 403
  except the routes in `router\READ_ONLY_EXCEPTIONS` (`template/site/preview`,
  which only counts matches). The two reading routes in `router\READ_ONLY_DENIED`
  (`system/log`, `system/php-info`) are refused as well, whatever the method:
  they would print paths, versions and possibly environment variables to every
  visitor of a public demo. The rest of the admin therefore stays available - the
  point of the account is to show the interface - while nothing can change -
  the own password change included. The form still opens: the save
  answers with the read-only message instead of the menu hiding the entry.
  The banner calls it a demo, because that is what the account is used for.
- **Maintenance mode blocks writes, not reads, and it is enforced centrally.**
  Every route that changes data is a POST, so `router\dispatch()` blocks POST
  requests with 503 while `maintenanceMode` is on; actions carry no check of
  their own. Only the two routes in `router\MAINTENANCE_EXCEPTIONS` stay
  available: `login` and `system/change-password`, the way back in.
  `ApiMaintenanceTest` walks the route table and fails for any non-exempt POST
  route that is not blocked. With
  `maintenanceMode` in `settings.php` every mutating route (page create, update, update body, delete, copy,
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
- **Roles decide what a route may do.** A token carries `role`: `editor` writes pages,
  uploads and page/block templates, `admin` also changes the CSS, the site templates,
  the backups and the system pages. The router compares the ranks (`role_allows`); a
  route without an explicit role needs `admin`, and a missing or unknown role rejects
  the account at login instead of granting the weakest one. There is no legacy single
  login any more: `settings.php` lists users,
  and without one nobody can log in. A token whose user no longer exists is rejected,
  which ends their sessions when a user is removed. Each record carries `password` and
  `role`, plus the optional `language` (UI language, empty falls back to
  `defaultLanguage`), `totpSecret` (that user's second factor) and `readOnly`
  (true refuses every write for that account)
  (`jaco/backend/core/auth.php`, `jaco/backend/core/router.php`, `jaco/routes.php`).
- **The password changes through the api, not by editing the file.**
  `system/change-password` takes the current password as proof – a wrong one counts
  against the login throttle – and writes a new password hash through
  `write_user_field()`, which keeps the comments and the other keys of `settings.php`
  and leaves the other users untouched. The result is parsed before it replaces the
  file. The route is deliberately exempt from
  the maintenance block: it is the way back in when something went wrong
  (`jaco/backend/domain/system.php`, `jaco/backend/core/config.php`).
- **Two-factor codes count as attempts, and belong to one user.** A wrong TOTP code
  records a failure like a wrong password, and a correct password without a code only
  reveals that a second step is required (`totpRequired`), never a token. Each account
  uses its own `users.<name>.totpSecret`: a code generated for one account is rejected
  for another, and an account without a secret logs in with its password alone. There
  are no backup codes: a lost secret is replaced through `auth:totp-setup -u <name>`,
  which needs server access.
- **Uploads are never overwritten.** A name collision gets `-1` appended
  (`filesystem\file_name_to_write()`), and `upload/create` validates the extension before
  the uploaded file is moved into place.
- **Credentials fail closed, never open.** `create_default_config()` treats an empty
  `secretKey` like a missing one (random fallback) and replaces a key below 32
  characters; without a `users` entry nobody can log in. `auth\authenticate()`
  additionally refuses empty stored values. A verbatim copy of `settings.dist.php` can
  therefore never log in and never signs tokens with the example key
  (`jaco/backend/core/config.php`, `jaco/backend/core/auth.php`,
  `jaco/test/ConfigDefaultsTest.php`).
- **The admin password is stored hashed, never in clear.** `settings.php` holds a
  password hash, verified in constant time.
  A plaintext value no longer matches, because it is not a valid hash. There is no
  migration path: an installation with a plaintext password has to have a new hash
  written into `settings.php` (`php jaco/cli.php auth:password`) or be reinstalled.
  The installer and `auth:token` are unaffected — both take the plaintext and verify
  it against the stored digest (`jaco/install.php`, `jaco/backend/core/auth.php`).
- **Uploads are scrubbed when they are SVG.** `svg` stays an allowed extension,
  but `html\sanitize_svg()` strips script elements, foreign objects, event
  handler attributes and script urls on every write path (direct upload,
  editor data urls, gallery full versions) before the file reaches `media/`.
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
  their directory (`media/picture.jpg`), because the file search starts at
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
  to be opened in the browser once, refuses to run as soon as `jaco-data/settings.php` exists and
  deletes itself on success.

## Roles

Every route needs a role: `editor` changes content, `admin` also changes the site's
configuration. Users and roles live in `settings.php` (`users`), the token carries the
role, and the router compares the ranks. A route without an explicit `role:` needs
`admin`, and a missing or unknown role rejects the account at login.

A user record is `['password' => '<digest>', 'role' => 'editor']`. Three optional fields
can accompany it: `language` sets that account's UI language (empty means
`defaultLanguage`), `totpSecret` holds that user's own TOTP secret, and `readOnly`
(default `false`) may be set to `true` to refuse every write. Never share one
TOTP secret between accounts – a shared code would let one user in as another.

To hash a password you choose, run the command with `--ask` and type the password twice.
It prints only the digest, which replaces the user's password value in `settings.php`:

```bash
php jaco/cli.php auth:password --ask
```

Without `--ask`, the command generates a random password and prints both the password and
its digest:

```bash
php jaco/cli.php auth:password
```

Keep the single quotes around the generated digest in PHP, otherwise the `$2y$` prefix
is interpolated. The manual reset also works for a `readOnly` account. A password
change does not revoke existing login tokens; rotate `secretKey` if every session must
end.

| Capability | `editor` | `admin` |
| --- | --- | --- |
| Read pages, uploads, backups, stylesheets and page/blocks templates | ✓ | ✓ |
| Change the own password | ✓ | ✓ |
| Create, update, copy and delete pages | ✓ | ✓ |
| Change page title, meta description and robots | ✓ | ✓ |
| Add and delete uploads | ✓ | ✓ |
| Read stylesheets, backups and site templates | – | ✓ |
| Read and apply site templates | ✓ | ✓ |
| Change the markup of a site template | – | ✓ |
| List, view and restore backups | ✓ | ✓ |
| Delete a backup | – | ✓ |
| List and view stylesheets | ✓ | ✓ |
| Change a stylesheet | – | ✓ |
| Change a stylesheet | – | ✓ |
| Environment, error log and php-info | – | ✓ |

`readOnly` is the one flag next to the role, because it is not a permission tier: the
account keeps its role, usually `admin`, but the router refuses every write - see
above. It replaces the former `passwordChange` flag: a password change is a write, so
a read-only account cannot change it either.

The routes behind it, `jaco/routes.php` being the source of truth:

There are two roles. An unknown role in `settings.php` rejects the account instead of
falling back to something, so a typo cannot hand out access.

- `editor`: `about`, `page/list`, `page/view`, `page/create`, `page/update`,
  `page/update-body`, `page/settings`, `page/copy`, `page/delete`, `upload/list`,
  `upload/create`, `upload/delete`, `template/pages`, `template/content`,
  `template/content/view`, `template/site/list`, `template/site/view`,
  `template/site/preview`, `template/site/apply`, `backup/list`, `backup/view`,
  `backup/restore`, `css/list`, `css/view` and `system/change-password`
- `admin`: everything, notably `backup/delete`, `css/update`, `template/site/update`,
  `system`, `system/log`, `system/log/clear` and `system/php-info`

An invalid or missing token answers **401**: the session is gone and the frontend returns
to the login. A role that is too low answers **403**: the session stays, only that action
is refused – nobody is logged out for clicking a button they may not use.

The admin UI groups its navigation into four areas: Content (Pages, Media, Backups,
Design), This page (Edit, Copy and Delete for the page currently in the preview), Admin
(Environment, PHP error log, PHP info) and Account (About, Change password, Logout).
Theme stays a direct control; Jump to page is available from the command palette.
The old Quick Action is gone. Add page is a
button in the Pages toolbar, Copy and Delete are row actions there. On a fine pointer
with hover, entering a menu opens it and leaving closes it; touch and keyboard toggle it
with click, Enter or Space. Escape and an outside click close it.

Restricted entries stay visible but disabled: the three system entries below
`admin`, and Copy/Delete below `editor`. Backups and Design are open to both roles:
an editor restores his own work and reads the stylesheet, deleting a backup and
changing the CSS stays with the admin. One rule for
every menu: a missing role greys the entry out instead of hiding it. A read-only account
is the exception - it reaches everything and learns on save that nothing is stored. The
inline editor stays open as a playground: it is editable, the save button is visible but
disabled, the save shortcuts are not bound, and the dirty guard is off, so changes are
never persisted and do not trap the user in a discard dialog. A `readOnly` account sees
a **demo banner** saying that nothing is stored. About is available to every signed-in
account; the public index exposes only name and version. The own password has its own
`/password` route for every account, because it used to live inside the admin-only system
view; a `readOnly` account reaches the form and the save answers **403**. This is
guidance, not access control: the router still checks the signed token on every request,
and the write endpoints answer **403** regardless of what the editor shows.

The token also carries the user's language. The frontend asks the translations endpoint
for it and falls back to the configured default language when the claim is empty. That way
each account gets its own UI language without a separate setting endpoint.

## Where the rules live

| Area | Test |
| --- | --- |
| Auth tokens: issue and validation | `jaco/test/AuthTest.php` |
| Login throttling: per address, unblock, bounded history | `jaco/test/LoginThrottleTest.php` |
| Two-factor codes (RFC vectors) and login flow with secret | `jaco/test/TotpTest.php`, `jaco/test/ApiLoginTotpTest.php` |
| Two-factor setup writes one user's secret | `jaco/test/CliTotpTest.php` |
| CLI entry point: web requests are refused | `jaco/test/CliEntryPointTest.php` |
| Roles in the docs match the route table | `jaco/test/DocsRolesTest.php` |
| Roles in the navigation match the route table | `jaco/test/MenuRoleTest.php` |
| Roles in the command palette match the route table | `jaco/test/PaletteRoleTest.php` |
| Role matrix over the api (admin, editor, unknown role) | `jaco/test/ApiRolesTest.php` |
| Own password change and the read-only account | `jaco/test/ApiChangePasswordTest.php`, `jaco/test/ApiReadOnlyAccountTest.php` |
| Roles in the admin UI: menus, buttons and the demo banner | `jaco/test/js/tests.js` (`role claims`, layout tests) |
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
