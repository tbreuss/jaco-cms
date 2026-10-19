# Backlog

Small, concrete gaps in existing features. Big strategic items live in
[missing-features.md](missing-features.md) and are not repeated here.

Status values: `open`, `doing`, `done`. Effort is a rough t-shirt size.

| Status | Item | Notes | Effort |
| --- | --- | --- | --- |
| open | Rename pages in the page manager | Promised in `features.html` ("copy, rename and delete") but missing: no `rename_page`, no route, no view. Workaround is copy plus delete, which breaks internal links. A real rename should rewrite links. | S |
| open | Title field for existing pages | `create_page`/`copy_page` take `withTitle`, but existing pages can only change it via Ace source. Add a title field to the page manager using `set_title()`. | S |
| open | Demo installation with a viewer account | Not a mode: an isolated installation with sample content, one publicly known `viewer` account and a visible hint in the admin. The roles and the role-aware UI exist now, so this is a configuration task: the viewer sees the read-only banner, may try the inline editor with a disabled save button, and `passwordChange => false` keeps the public password from being changed; nothing needs resetting. | S |
| open | Maintenance toggle in the admin panel | `maintenanceMode` works but is `settings.php`-only. The system page shows the state, every api response carries it as `X-Maintenance-Mode` (the banner follows it), and `write_setting()` can persist a value without losing the file's comments. A first version of the switch was built and reverted again: with a single login it is a self-imposed brake, not access control — whoever holds the credentials turns the mode off again. With the roles in place, a read-only handover no longer depends on it; what is left is the switch for the owner's own brake. | M |
| open | The name "maintenance mode" promises more than the flag does | It blocks writes in the admin, not visitors, and it grants nothing: a read-only handover or an "under construction" page for visitors are different features. Rename setting, header and banner to what it does ("editing locked") and say so in the docs. | S |
| open | Prune old backups on write, not only on list | `prune_old_backups()` runs in the backup listing only. Also prune when a backup is written (or via CLI), so the retention rule holds without opening the page. | S |
| open | Warn on deleting referenced files | `upload/delete` and `page/delete` do not check references from other pages. List referencing pages and ask for confirmation. | M |
| open | Upload progress and size feedback | `UploadListView` posts all files via one `Promise.all` with a single catch. Show per-file progress and pre-check sizes. | M |
| open | Login throttle only covers one address at a time | The per-address bucket removed the denial of service (no attacker can lock the owner out any more), but a distributed attack with many addresses can still guess passwords, limited only by the number of hosts it has. A capped global bucket as a second layer plus a recovery key in `settings.php` would close it without reintroducing the lockout. | M |
| open | Login history read-modify-write can lose a failure | Two simultaneous failed logins both read the history file, both append and the last write wins, so the throttle counts one instead of two. Harmless in practice, but counting should hold a lock across read and write. | S |
| open | Loading and not-found states in detail views | Page, upload and backup detail views render empty forms on slow networks and cannot be told apart from deleted entries. | S |
| open | Make `iframe.getHTML()` non-destructive for observers | `getHTML()` reads by rebuilding the preview: it calls `removeRichTextEditorElements()`, `removeExtendedImages()`, `removeLinks()` and `removeTempImageElements()`. So every read tears down live editor chrome — an open block loses its `.ql-editor`, its toolbar and the caret (pre-existing, measured on HEAD). `keepEditorScripts` stops only the worst case (script removal, fixed together with the first-edit detection); the teardown still runs. Serialize from a cloned `body` so observers leave the live preview alone; `save()` keeps the destructive path, which is what it wants. | M |
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
| open | Triage the unmerged feature branches | `feat/content-templates`, `feat/js-editor`, `feat/draggable-content-blocks` and `feat/configurable-upload-dir` are 4 to 9 days old, `json-schema` is three and a half years old. Each needs a decision — finish, merge or delete — otherwise `git branch` stops being a signal about what is actually in flight. | S |
| open | Remove the remaining error suppression in `install.php` | Five `@` calls survive there: `make_dir_rec`, `mkdir`, `file_put_contents` and `unlink(__FILE__)` at the end. The backend is free of them; the installer is the last place where a failed self-deletion (a security-relevant outcome) is silent. Check explicitly and let real failures surface. | S |
| open | Restructure QUnit test suite | Single monolithic `tests.js` (~2700 lines): split into per-topic files, add `expect()` declarations, introduce a `mountView` setup helper, and tighten global-state cleanup (sessionStorage, ace, confirm, route) to avoid order-dependent leaks. | M |

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
| Dead ErrorModal component | `remove: dead ErrorModal component` |
| Inline editor loses the first unsaved change | `fix: detect the first unsaved change in the inline editor` |
| Dirty check in the inline editor disabled editing | `fix: detect the first unsaved change in the inline editor` |
| QUnit suite cannot run twice within 10 minutes | `test: reset the login throttle before a run` |
| Change password in the admin panel | `feat: change the admin password in the system view` |
| Roles (`admin` / `editor` / `viewer`) for every route | `feat: introduce roles for admin, editor and viewer` |
| Split 401 from 403, so a denied action no longer logs out | `feat: introduce roles for admin, editor and viewer` |
| Error suppression (`@`) around file calls in the backend | `feat: change the admin password in the system view` |

## Won't do

Proposals that look like a gap but are not: closing them would cost more than
they gain. Recorded so they are not brought up again.

| Item | Why not |
| --- | --- |
| Fill the start page (`/`) with a quick-action dashboard | Measured as a regression: the admin panel is `position: fixed` over the site preview, so any content fills `#app` and hides the site (panel 97 px → 829 px, preview 747 px → ~0 px). `/` renders nothing on purpose — the JACO logo links to it from every view and is the way back to browsing the site. See the comment in `frontend/view/StartView.js`. |
