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
| Page | An `.htm`/`.html` file below `WEB_DIR` (the web root), excluding `jaco/`. The file is the data store – there is no database. |
| Editable marker | A `data-jaco-*` attribute in page HTML. Five variants: `data-jaco-editable` (block, rich text), `data-jaco-editable-line` (single line, block tags stripped), `data-jaco-editable-image` (image swap, size from `width`/`height`), `data-jaco-editable-image-extended` (swap plus alt text and fit/fill), `data-jaco-editable-link` (`href` and `target`). |
| Inline edit | Editing a page in the iframe that shows the live site (route `/page-edit`, `jaco/frontend/editor.js` injected into the iframe, saved through `page/update-body`). |
| Upload | A file in `upload/`. Allowed extensions: `png`, `gif`, `jpg`, `jpeg`, `svg`, `pdf`. |
| Backup | A copy in `jaco/_backup`, taken before a page or upload is overwritten or deleted. Name: `<YYYYMMDD>_<HHMMSS>_<4 chars>_<file>`, where `/` in the file path becomes `|`. |
| Template | A file in `jaco/_template/page`, used when a page is created. `jaco/_template/content` (fragments) is a stub in `jaco/backend/domain/template.php`. |
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
  `page/delete` and `upload/delete` write a copy into `jaco/_backup` before they modify a
  file (`jaco/backend/domain/page.php`, `jaco/backend/domain/upload.php`).
- **The app directory is out of reach.** Page create, update, update body, delete, copy
  (source *and* target) and view all reject paths whose first segment is `jaco/` (the
  `file_in_dir` guard), and the page, upload and backup lists never show files from `jaco/`.
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
- **The editable markers are allowlisted.** `data-jaco-editable`, `-line`, `-link`, `-image`
  and `-image-extended` must stay in `ALLOWED_ATTRIBUTES`: dropping one silently makes the
  page uneditable in place, because the editor finds its blocks through these attributes.
  `<script>` is removed from the body, so a script include belongs in the `<head>`, which
  the page manager owns.
- **Lists do not ship content.** `page/list` and `upload/list` return `content: null`,
  `backup/list` returns an empty string. Only `page/view` and `backup/view` return the
  body – for an upload inside a backup as base64.
- **Sort parameters are validated per resource.** A field can appear in the response and
  still not be sortable: `sort=file` is rejected for backups, `sort=name` for uploads.
- **Titles are read as UTF-8.** `html\get_title()` prepends a charset hint before parsing.
  A plain `DOMDocument::loadHTML()` assumes ISO-8859-1 and turns `Rückgabe` into
  `RÃ¼ckgabe` for pages without a `<meta charset>`.
- **Paths are relative to the web root.** Page and upload names always contain their
  directory (`upload/picture.jpg`), because the file search starts at `WEB_DIR`.
- **`backup/restore` moves instead of copying, and trusts the name.** The destination comes
  from the backup name (`|` becomes `/`), the backup is renamed onto it and the file
  timestamp is restored from the name. Pass only names that came from `backup/list`: a
  backup of an app file would be written back into `jaco/`.
- **The login form submits natively.** `jaco/frontend/view/LoginView.js` wraps the fields
  in a `<form>` and cancels the submit event, so RETURN and the submit button each send
  exactly one request and the page never reloads.

## Where the rules live

| Area | Test |
| --- | --- |
| Auth tokens: issue and validation | `jaco/test/AuthTest.php` |
| Route table: public/private, methods | `jaco/test/ApiRoutesTest.php` |
| Pages: list, view, copy, create, update (document and body), delete | `jaco/test/ApiPage*Test.php` |
| Backups: list, view, restore, delete | `jaco/test/ApiBackup*Test.php` |
| Uploads: list, delete | `jaco/test/ApiUpload*Test.php` |
| Page templates, translations | `jaco/test/ApiTemplatePagesTest.php`, `jaco/test/ApiTranslationsTest.php` |
| Shared base: real route table, token, fixture cleanup | `jaco/test/ApiTestCase.php` |
| Input sanitizing and validation rules | `jaco/test/SanitizeTest.php`, `jaco/test/ValidateTest.php` |
| HTML: sanitizing (XSS), titles, embedded images | `jaco/test/HtmlFunctionsTest.php`, `jaco/test/HtmlTitleTest.php`, `jaco/test/EmbeddedImagesTest.php` |
| Filesystem: naming, extensions, paths, timestamps | `jaco/test/FileFunctionsTest.php`, `jaco/test/FileSystemNamingTest.php` |
| Schema validation, error responses and field mapping | `jaco/test/SchemaValidationTest.php`, `jaco/test/ResponderMappingTest.php` |
| Frontend components, page editor model, login form | `jaco/test/js/tests.js` |

Run them from the repository root:

    docker run --rm -v $(pwd)/jaco:/app jitesoft/phpunit

Options for the PHPUnit binary have to be passed explicitly, because the image entrypoint
is PHP: `docker run --rm -v $(pwd)/jaco:/app jitesoft/phpunit phpunit --filter AuthTest`.

The frontend tests are QUnit tests that run in a real browser; they need node ≥ 22 and a
local Chrome and drive the page through the devtools protocol, so no npm packages are
involved. Open `jaco/test/js/index.html` in a browser while developing, or run them
headless:

    php -S 127.0.0.1:8899 -t . &
    node jaco/test/js/runner.mjs

Not tested on purpose: the diagnostics endpoints (`system`, `system/log`,
`system/php-info`, all environment dependent) and the global `error_handler`,
`exception_handler` and `fatal_handler`. Still without a test: restoring a backup whose
original page no longer exists.
