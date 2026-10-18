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
| open | Start page after login is empty | `StartView` renders nothing. Show what can be done here (edit pages, uploads, backups) instead of a dead page. | S |
| open | Upload progress and size feedback | `UploadListView` posts all files via one `Promise.all` with a single catch. Show per-file progress and pre-check sizes. | M |
| open | Change password in the admin panel | No route and no menu entry; every change needs server access. Self-service for onboarding and rotation. | M |
| open | Loading and not-found states in detail views | Page, upload and backup detail views render empty forms on slow networks and cannot be told apart from deleted entries. | S |
| open | Remove dead ErrorModal component | `ErrorModal.js` is imported nowhere (no view uses `openErrorModal`/`ErrorModal`). The active modals are `ModalSmall` and `ModalMedium`. Either delete it or wire it up for real error dialogs. | S |
| open | Consolidate component CSS under a `.j-` prefix | Component classes (`.modal`, `.field-error`, `.form-errors`, `.list-filter`, `.m-notification`) have no namespace while Quill (`.ql-*`), Ace (`.ace_*`) and QUnit (`.qunit-*`) all do. Rename component-owned classes to `.j-*` to avoid collisions if a third-party library ever ships the same class. Note: `.m-*` was likely meant as "message", not a namespace — do not extend it. | M |
| open | Paginate lists, collapse admin navigation on mobile | Filter/sort only, no limit/offset; full navbar without a mobile menu. Lists slow down and the admin is barely usable on phones. | M |
| open | Skeleton tables for list loading states | Lists pop in after loading instead of rendering in a stable frame. Build one `SkeletonTable` component (rows × columns matching each view) shown during `isLoading`, used by every list. Deliberately no route-level blocking on data (would trade shift for silence on slow connections). | M |
| open | Choose uploaded images in the editor | Every image click opens the local file picker; the upload manager and the editor do not know each other. Reusing an image duplicates it with a `-1` suffix. Add a "choose from library" option. | M |
| open | Tables in the rich-text editor | The sanitizer allows `table`/`tr`/`td`, but the Quill toolbar has no table tool. Menus and price lists cannot be built without hand-written HTML. | M |
| open | Duplicate content blocks | Blocks can only be added empty (from fragment) or deleted. Duplicating a filled block as a template for the next entry is the most common click for recurring content. | S |
| open | Reorder blocks without draggable | Only `draggable` lists reorder; `top`/`bottom`/`any` have no grip and no up/down buttons. Changing order means delete plus recreate. | S |
| open | Alt text on plain images | Only `image-extended` asks for alt text (`editor.js`); plain `editable-image` saves without. Accessibility depends on the marker the developer chose once. | S |
| open | Unlink in the link dialog | The `editable-link` dialog changes URL and target but cannot turn a link back into text. | S |
| open | Restore recently deleted blocks | A deleted block is gone (page-level restore only via backup). A per-list trash buffer would defuse misclicks. | S |
| open | Semantic heading inside the line editor | The line editor wraps text in a `<p>` (Quill default block); a CSS override in `editor.css` keeps the heading look while editing. Alternative: a clipboard matcher (`P` → `header` level from the host tag) for real `<h1>`–`<h6>` during edit. Only worth it if DevTools-semantics while editing ever matter. | S |
| open | Share one Ace editor helper across views | `PageEditView`, `CssEditView` and `BackupEditView` copy the same `ace.edit` setup with slightly different lifecycles (single instance twice, recreate-per-update once). Every lifecycle fix has to be thought through three times. Bundle creation, theme watching and destroy in one `lib/` helper; views keep only their save and content logic. | S |
| open | Light theme flash on hard refresh | Briefly shows the light theme on hard refresh in dark mode. Not reproducible headless (admin boot, editors and preview are dark from the first frame); needs the exact spot (admin chrome, preview iframe, editor toolbar, window) plus theme setting and browser to narrow down. | S |
| open | Restructure QUnit test suite | Single monolithic `tests.js` (~2300 lines): split into per-topic files, add `expect()` declarations, introduce a `mountView` setup helper, and tighten global-state cleanup (sessionStorage, ace, confirm, route) to avoid order-dependent leaks. | M |

## Done

| Item | Commit |
| --- | --- |
| Save & Close must not close on validation error | `fix: do not close editor on validation error` |
| Redirect to login on expired token | `fix: redirect to login on expired session` |
| Warn on unsaved changes | `fix: warn on unsaved changes` |
| Readonly / disabled fields are not distinguishable from normal fields | `style: distinguish disabled/read-only inputs` |
| Toasts have a weird mouseover/click effect | `feat: accessible, dismissible toasts with modern palette` |
| Sticky, accessible error toasts | `feat: accessible, dismissible toasts with modern palette` |
| About page missing | `feat: add About page with version and project link` |
| Generic OK button labels on add/copy page | `fix: use specific labels instead of generic OK on add/copy page buttons` |
| Hardcoded colors scattered through app.css | `style: convert all hardcoded colors to CSS variables` |
| Dark mode broken (unclosed brace swallowed theme block) | `style: convert all hardcoded colors to CSS variables` |
| No CSS syntax check in tests | `test: add CSS syntax check via styleSheet rules` |
| Modals lack Escape handling | `feat: close modals with Escape key` |
