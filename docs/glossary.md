# Glossary

Ambiguity usually starts with words, not sentences. These terms have exactly one meaning.

| Term | Meaning |
| --- | --- |
| Page | An `.htm`/`.html` file below the web root (`Config::$webDir`), excluding `jaco/`. The file is the data store – there is no database. |
| Stylesheet | A `.css` file below the web root, excluding `jaco/`, `jaco-data/backups` and the `inspiration` directory. Listed, viewed and written through `css/list`, `css/view` and `css/update`. |
| Editable marker | A `data-jaco-*` attribute in page HTML. Six variants: `data-jaco-editable` (block, rich text), `data-jaco-editable-line` (single line, block tags stripped), `data-jaco-editable-image` (image swap, size from `width`/`height`), `data-jaco-editable-image-extended` (swap plus alt text and fit/fill), `data-jaco-editable-image-gallery` (thumbnail links to the full version, stored as `name-full.ext`), `data-jaco-editable-link` (`href` and `target`). |
| Content list (block list) | A `<div data-jaco-content-list>` with `data-jaco-fragment` (a file from `jaco-data/templates/content/`), `data-jaco-title` (add-button label) and `data-jaco-pos` (`top`, `bottom`, `any` or `draggable`). Blocks live in `<div data-jaco-content-item>` and are added/removed in the inline editor; `draggable` adds pointer-based drag reorder via a grip handle (with auto-scroll, no library). Served through `template/content` and `template/content/view`. |
| Site template | A reusable HTML fragment in `jaco-data/templates/site/<name>.html`. Pages opt in with `data-jaco-template="<name>"`; applying replaces the element with the template root, preserves the marker and backs up every page it rewrites. |
| Page settings | The page title, meta description and robots value stored in the document head. Edited through the inline editor dialog or the raw page editor; saved through `page/settings` or the optional metadata fields of `page/update`. A metadata-only save leaves the body untouched. |
| Inline edit | Editing a page in the iframe that shows the live site (route `/page-edit`, `jaco/frontend/editor.js` injected into the iframe, saved through `page/update-body`). |
| Upload | A file in `media/`. Allowed extensions: `png`, `gif`, `jpg`, `jpeg`, `svg`, `pdf` (`Config::$uploadExtensions`). |
| Backup | A copy in `jaco-data/backups`, taken before a page, stylesheet or upload is overwritten or deleted. Name: `<YYYYMMDD>_<HHMMSS>_<4 chars>_<file>`, where `/` in the file path becomes `|`. |
| Page template | A file in `jaco-data/templates/page`, used when a page is created. Listed through `template/pages`. |
| Block template | A file in `jaco-data/templates/content`, a single reusable block (e.g. a news item). Listed through `template/content`, fetched through `template/content/view`. |
| Application directory | `jaco/`: admin UI, `api.php`, PHP code, `vendor/`, `runtime/` and `settings.php`. Reachable only through its entry points; the rest answers 403. |
| Operator data directory | `jaco-data/` (`Config::$dataDir`): page, content and site templates under `templates/` and the backups under `backups/`. Never reachable over the web. Replacing `jaco/` does not touch it. |
| Media directory | `media/` (`Config::$mediaDir`): editor uploads served as static files. Referenced as `media/<file>`. |
| Config | The injected `jaco\core\config\Config` value object: directories (including the configurable `mediaDir` for uploads and `dataDir` for templates and backups, both always inside the web root), extensions, the `users` list, `defaultLanguage`, `maintenanceMode` and the php error settings. It replaced the former `jaco\*` constants; `jaco/settings.php` overrides the defaults. An empty users list and a short secret key fall back to safe values, so logging in stays impossible until real values are set (fail closed, see below). |
| Domain payload | Return value of a `jaco\domain\*` function: `success`, `invalid` (HTTP 422 with an error list) or `error` (HTTP 500). |
| API action | A route registered in `jaco/routes.php` (`action=page/update`). |
| Session token | JWT signed with HS256, issued by `POST action=login`, sent as `Authorization: Bearer <token>`. |
| Two-factor authentication | Optional TOTP second login step (RFC 6238, `jaco/backend/core/totp.php`). Offline by design: no SMS, mail or network. Every user has their own secret in their `settings.php` record (`users` → `totpSecret`); an empty or missing value means password-only login for that account. Enrollment runs through `auth:totp-setup -u <user>` or the installer checkbox. |
| API vs. domain names | The page manager speaks `fileName` + `html`, the inline editor `fileName` + `body`; the domain functions speak `file` + `withContent` and `file` + `withBody`. |
