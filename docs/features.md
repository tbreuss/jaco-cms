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
| Editable marker | A `data-jaco-*` attribute in page HTML. Five variants: `data-jaco-editable` (block, rich text), `data-jaco-editable-line` (single line, block tags stripped), `data-jaco-editable-image` (image swap, size from `width`/`height`), `data-jaco-editable-image-extended` (swap plus alt text and fit/fill), `data-jaco-editable-link` (`href` and `target`). |
| Inline edit | Editing a page in the iframe that shows the live site (route `/page-edit`, `jaco/frontend/editor.js` injected into the iframe, saved through `page/update-body`). |
| Upload | A file in `upload/`. Allowed extensions: `png`, `gif`, `jpg`, `jpeg`, `svg`, `pdf` (`Config::$uploadExtensions`). |
| Backup | A copy in `jaco/_backup`, taken before a page, stylesheet or upload is overwritten or deleted. Name: `<YYYYMMDD>_<HHMMSS>_<4 chars>_<file>`, where `/` in the file path becomes `|`. |
| Template | A file in `jaco/_template/page`, used when a page is created. `jaco/_template/content` (fragments) is a stub in `jaco/backend/domain/template.php`. |
| Config | The injected `jaco\core\config\Config` value object: directories, extensions, credentials, `defaultLanguage`, `maintenanceMode` and the php error settings. It replaced the former `jaco\*` constants; `jaco/settings.php` overrides the defaults. |
| Domain payload | Return value of a `jaco\domain\*` function: `success`, `invalid` (HTTP 422 with an error list) or `error` (HTTP 500). |
| API action | A route registered in `jaco/routes.php` (`action=page/update`). |
| Session token | JWT signed with HS256, issued by `POST action=login`, sent as `Authorization: Bearer <token>`. |
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
- **Stylesheets are content, but not HTML.** `css/update` stores the file as sent:
  `html\sanitize_html()` would destroy a stylesheet, and a `.css` file served as
  `text/css` carries no script vector. Only `.css` files below the web root are accepted,
  `jaco/` is out of reach, and the previous version is backed up first
  (`jaco/backend/domain/css.php`).
- **Maintenance mode blocks writes, not reads.** With `MAINTENANCE_MODE` every mutating
  route (page create, update, update body, delete, copy, `css/update`, `upload/create`,
  `upload/delete`, `backup/restore`, `backup/delete`) answers with the maintenance
  response, while lists and views keep working.
- **Login attempts are throttled.** Three failures within ten minutes block the login for
  ten minutes; fifteen failures in total block it until
  `jaco/runtime/log/loginhistory.json` is deleted. Every failure counts into one global
  bucket, so rotating usernames does not help, and the response never reveals whether the
  username exists (`jaco/backend/core/auth.php`).
- **Uploads are never overwritten.** A name collision gets `-1` appended
  (`filesystem\file_name_to_write()`), and `upload/create` validates the extension before
  the uploaded file is moved into place.
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
- **Server errors stay English on the wire and are translated in the frontend.**
  The backend has no per-request locale, so `detail` strings arrive in English and
  `tError()` in `jaco/frontend/model/i18n.js` translates them for display
  (`FormErrors.js`). A new static message needs a key in all four
  `jaco/language/*.json` files (guarded by `jaco/test/LanguageTest.php`); a new
  dynamic message additionally needs its English template in `errorTemplates`,
  otherwise it falls back to English.

## Where the rules live

| Area | Test |
| --- | --- |
| Auth tokens: issue and validation | `jaco/test/AuthTest.php` |
| Login throttling: block, permanent block, reset | `jaco/test/LoginThrottleTest.php` |
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
| Config defaults and masked diagnostics | `jaco/test/DefinedConstantsTest.php` |
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

Not tested on purpose: the diagnostics endpoints (`system`, `system/log`,
`system/php-info`, all environment dependent; `system\defined_constants()` itself is
covered by `DefinedConstantsTest`) and the global `error_handler`, `exception_handler`
and `fatal_handler`. Still without a test: restoring a backup whose original page no
longer exists.
