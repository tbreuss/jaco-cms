# Glossary

Ambiguity usually starts with words, not sentences. These terms have exactly one meaning.

| Term | Meaning |
| --- | --- |
| Page | An `.htm`/`.html` file below the web root (`Config::$webDir`), excluding `jaco/`. The file is the data store – there is no database. |
| Stylesheet | A `.css` file below the web root, excluding `jaco/` and `jaco-data/backups`. Listed, viewed and written through `css/list`, `css/view` and `css/update`. |
| Editable marker | A `data-jaco-*` attribute in page HTML. Roles: `data-jaco-block` (multi-line text), `data-jaco-line` (single line), `data-jaco-image` (image), `data-jaco-link` (`href` and `target`), `data-jaco-repeater` (repeatable block), `data-jaco-slot` (flexible insertion), `data-jaco-template` (site template). Properties: `--tools`, `--mode`, `--position`, `--label`, `--sortable`. Details: [attribute-contract.md](attribute-contract.md). |
| Content list (block list) | A `<div data-jaco-repeater="<file>.html">` whose value is the fragment from `jaco-data/templates/content/`. Every direct child is one block; the fragment has exactly one root element. Details: [attribute-contract.md](attribute-contract.md). |
| Slot | A `<div data-jaco-slot="a.html, b.html">` whose value names one or more fragments from `jaco-data/templates/content/`. Clicking the add button shows a chooser; an empty `data-jaco-slot` offers every fragment from the content API. Details: [attribute-contract.md](attribute-contract.md). |
| Shared content (site template) | A reusable HTML fragment in `jaco-data/templates/site/<name>.html`. Unlike a page or content template it is not copied: there is one original that is rolled out to every page that includes it. Pages opt in with `data-jaco-template="<name>.html"`; applying replaces the element with the template root, preserves the marker and backs up every page it rewrites. |
| Page settings | The page title, meta description and robots value stored in the document head. Edited through the inline editor dialog or the raw page editor; saved through `page/settings` or the optional metadata fields of `page/update`. A metadata-only save leaves the body untouched. |
| Maintenance mode | A setting (`Config::$maintenanceMode`) that blocks writes in the admin but does not hide the site from visitors. Toggled via `settings.php`. |
| Read-only account | An account with the `readOnly` flag: it can open the admin and inspect everything, but every write is refused by the router. Used for client handover and public demos. |
| Sanitizer | The HTML cleaning step (`sanitize_html()`) that removes disallowed tags and attributes on save. Only YouTube embeds survive; event handlers never do. The sanitizer is the reason `data-jaco-*` attributes are whitelisted. |
| Upload | A file in `media/`. Allowed extensions: `png`, `gif`, `jpg`, `jpeg`, `svg`, `pdf` (`Config::$uploadExtensions`). |
| Media library | The collection of uploaded files in `media/`, browsable from the image picker and the upload manager. Editors reuse existing files instead of uploading duplicates. |
| Backup | A copy in `jaco-data/backups`, taken before a page, stylesheet or upload is overwritten or deleted. Name: `<YYYYMMDD>_<HHMMSS>_<4 chars>_<file>`, where `/` in the file path becomes `|`. |
| Page template | A file in `jaco-data/templates/page`, used when a page is created. Listed through `template/pages`. |
| Fragment | A file in `jaco-data/templates/content/` that provides one reusable block. Inserted into pages via repeaters and slots. Must have exactly one root element. |
| Block template | A file in `jaco-data/templates/content`, a single reusable block (e.g. a news item). Listed through `template/content`, fetched through `template/content/view`. |
| Application directory | `jaco/`: admin UI, `api.php`, PHP code, `vendor/`, `runtime/`. Reachable only through its entry points; the rest answers 403. |
| Operator data directory | `jaco-data/` (`Config::$dataDir`): page, content and site templates under `templates/` and the backups under `backups/`. Never reachable over the web. Replacing `jaco/` does not touch it. |
| Media directory | `media/` (`Config::$mediaDir`): editor uploads served as static files. Referenced as `media/<file>`. |
| Config | The injected `jaco\core\config\Config` value object: directories (including the configurable `mediaDir` for uploads and `dataDir` for templates and backups, both always inside the web root), extensions, the `users` list, `defaultLanguage`, `maintenanceMode` and the php error settings. It replaced the former `jaco\*` constants; `jaco-data/settings.php` overrides the defaults. An empty users list and a short secret key fall back to safe values, so logging in stays impossible until real values are set (fail closed, see below). |
| Domain payload | Return value of a `jaco\domain\*` function: `success`, `invalid` (HTTP 422 with an error list) or `error` (HTTP 500). |
| API action | A route registered in `jaco/routes.php` (`action=page/update`). |
| Session token | JWT signed with HS256, issued by `POST action=login`, sent as `Authorization: Bearer <token>`. |
| Two-factor authentication | Optional TOTP second login step (RFC 6238, `jaco/backend/core/totp.php`). Offline by design: no SMS, mail or network. Every user has their own secret in their `jaco-data/settings.php` record (`users` → `totpSecret`); an empty or missing value means password-only login for that account. Enrollment runs through `auth:totp-setup -u <user>` or the installer checkbox. |
| API vs. domain names | The page manager speaks `fileName` + `html`, the inline editor `fileName` + `body`; the domain functions speak `file` + `withContent` and `file` + `withBody`. |
