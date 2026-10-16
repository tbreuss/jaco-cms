# Backlog

Small, concrete gaps in existing features. Big strategic items live in
[missing-features.md](missing-features.md) and are not repeated here.

Status values: `open`, `doing`, `done`. Effort is a rough t-shirt size.

| Status | Item | Notes | Effort |
| --- | --- | --- | --- |
| open | Rename pages in the page manager | Promised in `features.html` ("copy, rename and delete") but missing: no `rename_page`, no route, no view. Workaround is copy plus delete, which breaks internal links. A real rename should rewrite links. | S |
| open | Title field for existing pages | `create_page`/`copy_page` take `withTitle`, but existing pages can only change it via Ace source. Add a title field to the page manager using `set_title()`. | S |
| open | Maintenance toggle in the admin panel | `maintenanceMode` works but is `settings.php`-only. Add an API route plus a switch on the system page. | S |
| open | Prune old backups on write, not only on list | `prune_old_backups()` runs in the backup listing only. Also prune when a backup is written (or via CLI), so the retention rule holds without opening the page. | S |
| open | Warn on deleting referenced files | `upload/delete` and `page/delete` do not check references from other pages. List referencing pages and ask for confirmation. | M |
| open | Redirect to login on expired token | `service/api.js` handles only 422; 401/403 show a generic "Request failed" with no logout and no redirect. Editors keep typing and lose work after 24h instead of logging in once. | S |
| open | Save & Close must not close on validation error | `PageEditView`/`CssEditView` swallow the 422 without rethrow, so the chained navigation always runs. A failed save looks like success. | S |
| open | Warn on unsaved changes | No dirty check and no `beforeunload` in the page, CSS and inline editors; cancel and navigation discard silently. | M |
| open | Start page after login is empty | `StartView` renders nothing. Show what can be done here (edit pages, uploads, backups) instead of a dead page. | S |
| open | Upload progress and size feedback | `UploadListView` posts all files via one `Promise.all` with a single catch. Show per-file progress and pre-check sizes. | M |
| open | Change password in the admin panel | No route and no menu entry; every change needs server access. Self-service for onboarding and rotation. | M |
| open | Loading and not-found states in detail views | Page, upload and backup detail views render empty forms on slow networks and cannot be told apart from deleted entries. | S |
| open | Sticky, accessible error toasts | Danger toasts vanish after 3s with no `role=alert` and no close button; modals lack Escape handling. Save and network errors go unread. | S |
| open | Paginate lists, collapse admin navigation on mobile | Filter/sort only, no limit/offset; full navbar without a mobile menu. Lists slow down and the admin is barely usable on phones. | M |
| open | Choose uploaded images in the editor | Every image click opens the local file picker; the upload manager and the editor do not know each other. Reusing an image duplicates it with a `-1` suffix. Add a "choose from library" option. | M |
| open | Tables in the rich-text editor | The sanitizer allows `table`/`tr`/`td`, but the Quill toolbar has no table tool. Menus and price lists cannot be built without hand-written HTML. | M |
| open | Duplicate content blocks | Blocks can only be added empty (from fragment) or deleted. Duplicating a filled block as a template for the next entry is the most common click for recurring content. | S |
| open | Reorder blocks without draggable | Only `draggable` lists reorder; `top`/`bottom`/`any` have no grip and no up/down buttons. Changing order means delete plus recreate. | S |
| open | Alt text on plain images | Only `image-extended` asks for alt text (`editor.js`); plain `editable-image` saves without. Accessibility depends on the marker the developer chose once. | S |
| open | Unlink in the link dialog | The `editable-link` dialog changes URL and target but cannot turn a link back into text. | S |
| open | Restore recently deleted blocks | A deleted block is gone (page-level restore only via backup). A per-list trash buffer would defuse misclicks. | S |
| open | Light theme flash on hard refresh | Briefly shows the light theme on hard refresh in dark mode. Not reproducible headless (admin boot, editors and preview are dark from the first frame); needs the exact spot (admin chrome, preview iframe, editor toolbar, window) plus theme setting and browser to narrow down. | S |

## Done

| Item | Commit |
| --- | --- |
| _none yet_ | |
