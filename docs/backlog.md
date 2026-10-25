# Backlog

Small, concrete gaps in existing features. Big strategic items live in
[missing-features.md](missing-features.md) and are not repeated here.

Status values: `open`, `doing`, `done`. Effort is a rough t-shirt size.

| Status | Item | Notes | Effort |
| --- | --- | --- | --- |
| open | Run the app under Apache in Docker | The image starts `php -S`, which ignores `.htaccess`: the whitelist in `jaco/.htaccess`, the `settings.php` block, the `jaco/frontend/` whitelist, `jaco-data/` and `ErrorDocument 404` stay untested until a deploy reveals them - the `settings.php` incident showed exactly that gap. Add a second stage `FROM php:${PHP_VERSION}-apache` that reuses the `builder` extensions, runs `a2enmod rewrite headers` (the Authorization forwarding in the root `.htaccess` needs mod_rewrite) and sets `AllowOverride All` for the document root. Effort: one target, the existing `server` stage stays. | M |
| open | Point the E2E suite at Apache | `playwright.config.mjs` hardcodes `php -S … router.php`, and `router.php` only imitates the 404 handling. Make the server switchable (`JACO_E2E_APACHE=1` skips the internal webServer, `JACO_E2E_BASEURL` sets the origin) so the suite can run against the Apache container. Open questions before implementing: the `JACO_SETTINGS_FILE` fixture has to reach the container, and `global-setup.mjs` writes into `media/`, `jaco-data/backups/` and `jaco-data/templates/site/` - those paths must be writable by `www-data` (volume permissions or a matching UID), otherwise exactly the writing tests fail. Depends on the Apache image. | S |
| open | Rename pages in the page manager | Promised in `features.html` ("copy, rename and delete") but missing: no `rename_page`, no route, no view. Workaround is copy plus delete, which breaks internal links. A real rename should rewrite links. | S |
| open | Generate the social cards from a source file | `assets/social-card-{en,de}.png` exist only as finished bitmaps; there is no template and no script, so every text change is painted over the PNG. The host name already had to be patched that way when the site moved to `jaco.tebe.ch`. Add a generator (a script next to `build-dist.sh` that writes both cards, or an SVG template plus a renderer) that holds the colours, sizes, positions and both languages, so a domain change or a new language is one line instead of an image edit. Open question: the original build used a font that is not recorded - the current cards are reproduced with Arial Bold, so a regeneration pins the look as it is now, not necessarily as it was designed. | S |
| open | Generate `sitemap.xml` from the pages | The sitemap is hand maintained today: every new page must be added by hand, and `lastmod` goes stale. Jaco already enumerates pages for the page manager, so it could emit `sitemap.xml` with `lastmod` from the file timestamp and `hreflang` alternates derived from the language trees. Decide whether it is generated at save time, on request (a route like `sitemap`) or by a CLI command, and whether `robots.txt` gets the matching `Sitemap:` line automatically. Pages with a `noindex` robots value must be skipped. | M |
| open | Create and delete site templates in the admin | Templates are currently created and removed on the file system under `jaco-data/templates/site/`. Add an input for new names, validation and a delete action with the same no-backup warning as apply-to-all. | S |
| open | Demo playground: a reset installation with an admin account | A second installation (e.g. `demo.jaco.tebe.ch`) that shows every admin feature and cannot be broken: one publicly known **admin** account, so pages, uploads, CSS, backups and site templates all work, and a cron job that restores the whole tree from a tarball once per hour. A `viewer` account is not a demo - it hides most of the admin and disables saving, so the point of the playground is lost. Keep the reset tarball next to the deployment, not inside the installation. Accept that visitors share the instance and can disturb each other between resets; per-session isolation is a different feature. | M |
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
| open | Tables in the rich-text editor | The sanitizer allows `table`/`tr`/`td`, but the Quill toolbar has no table tool. Menus and price lists cannot be built without hand-written HTML. | M |
| open | Duplicate content blocks | Blocks can only be added empty (from fragment) or deleted. Duplicating a filled block as a template for the next entry is the most common click for recurring content. | S |
| open | Reorder blocks without draggable | Only `draggable` lists reorder; `top`/`bottom`/`any` have no grip and no up/down buttons. Changing order means delete plus recreate. | S |
| open | Alt text on plain images | Only `image-extended` asks for alt text (`editor.js`); plain `editable-image` saves without. Accessibility depends on the marker the developer chose once. | S |
| open | Unlink in the link dialog | The `editable-link` dialog changes URL and target but cannot turn a link back into text. | S |
| open | Restore recently deleted blocks | A deleted block is gone (page-level restore only via backup). A per-list trash buffer would defuse misclicks. | S |
| open | Semantic heading inside the line editor | The line editor wraps text in a `<p>` (Quill default block); a CSS override in `editor.css` keeps the heading look while editing. Alternative: a clipboard matcher (`P` → `header` level from the host tag) for real `<h1>`–`<h6>` during edit. Only worth it if DevTools-semantics while editing ever matter. | S |
| open | Share one Ace editor helper across views | `PageEditView`, `CssEditView` and `BackupEditView` copy the same `ace.edit` setup with slightly different lifecycles (single instance twice, recreate-per-update once). Every lifecycle fix has to be thought through three times. Bundle creation, theme watching and destroy in one `lib/` helper; views keep only their save and content logic. | S |
| open | Light theme flash on hard refresh | Briefly shows the light theme on hard refresh in dark mode. Not reproducible headless (admin boot, editors and preview are dark from the first frame); needs the exact spot (admin chrome, preview iframe, editor toolbar, window) plus theme setting and browser to narrow down. Deferred: explicitly not a release blocker. | S |
| done | Triage the unmerged feature branches | All feature branches were deleted: `feat/content-templates`, `feat/js-editor`, `feat/draggable-content-blocks` and `feat/configurable-upload-dir` were never merged, `feat/password-hashing` and `feat/role-aware-ui` had already been merged as pull requests, `json-schema` is three and a half years old, and `feat/process-runner` was closed without merging (the process runner was not needed). `git branch` is a signal again. | S |
| open | Shortcuts in the command palette | The palette lists the commands but binds no keys: every command is reached by typing or by clicking. The registry has no `shortcut` field and the palette shows no shortcut column. Alt+letter is out (browsers reserve several combinations), so the candidates are Ctrl/Cmd+letter or a modifier-free key. | S |
| open | Recently used commands on top of the palette | The palette always shows the registry order. Commands that are used often should float to the top, so the two or three that matter are one keypress away. Needs a place to keep the history (session or local storage) and a cap. | S |
| open | Multi-step processes for commands | A command may need more than one step ("New page" is a template, a filename and a title). The current dialog asks for all three at once, which is fine for one form but does not scale to a real process with a review step. A runner would carry the context, validate per step and commit at the end. Deferred: no command needs it today, and the one form is faster than a wizard. | M |
| open | Remove the remaining error suppression in `install.php` | Five `@` calls survive there: `make_dir_rec`, `mkdir`, `file_put_contents` and `unlink(__FILE__)` at the end. The backend is free of them; the installer is the last place where a failed self-deletion (a security-relevant outcome) is silent. Check explicitly and let real failures surface. | S |
| open | Restructure QUnit test suite | Single monolithic `tests.js` (~2700 lines): split into per-topic files, add `expect()` declarations, introduce a `mountView` setup helper, and tighten global-state cleanup (sessionStorage, ace, confirm, route) to avoid order-dependent leaks. | M |
| open | Style form selects instead of leaving the bezel to the browser | A `<select>` keeps its native appearance, so Chromium paints its own bezel inside a box that is taller than the one `app.css` styles (`padding: 12px`, `border`, `border-radius`, `background-color`). The field and the focus ring therefore do not line up, and an open select shows a rim around its popup. Two attempts fixed one symptom each and were reverted: moving the focus ring inside (`outline-offset: -3px`) and resetting the appearance with a themed SVG chevron, the way Bootstrap 5 does. Neither was verifiably right on the reported machine, so the next attempt starts with a screenshot of the states in both themes instead of a patch. Decide up front whether the popup should stay native (then only the closed field may be styled, and the ring must not be drawn around an open list) or be replaced by a custom listbox (then keyboard handling, `aria-expanded` and the mobile behaviour come with it). Affected: every `Select` in the admin - CSS file, template, robots, theme switch. | S |
| open | Content-list buttons carry their language in the page | The label of an add button comes from `data-jaco-title` in the page itself and is looked up with the literal as the translation key, so the page decides which language it speaks: `index.html` says `"Add feature"`, while three German pages say `"Funktion hinzufügen"` - an English or French editor sees a German label there. Decide whether pages carry a neutral key (`data-jaco-title="addFeature"`) that all four language files translate, which needs a migration of every page and content template, or whether the literal stays the documented behaviour and each page is expected to be written in its own language. Count as of the cleanup: 13x `Add news`, 4x `Add feature`, 3x `Funktion hinzufügen`, plus one each of `Add top/bottom/draggable block` and `Add block here`. A statically built frontend cannot find these labels - they only exist in the pages at runtime. | M |

## Done

| Item | Commit |
| --- | --- |
| Site templates with apply-to-all | `feat: add site templates with apply-to-all` |
| Page title, meta description and robots UI | `feat: add page settings for title, description and robots` |
| Translate Quill's internal labels | `feat: translate Quill toolbar labels` |
| Dark mode for the Quill editor chrome | `feat: theme the Quill editor chrome` |
| 404 page for the public website | `feat: finish the public 404 page` |
| Translate the inline editor and media picker | `feat: translate the inline editor and media picker` |
| Choose uploaded images in the editor | `feat: add a media picker for image markers` |
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
| Group the admin navigation into content, admin and account | `feat: regroup the admin navigation and fix page copy` |
| Roles (`admin` / `editor` / `viewer`) for every route | `feat: introduce roles for admin, editor and viewer` |
| Split 401 from 403, so a denied action no longer logs out | `feat: introduce roles for admin, editor and viewer` |
| Error suppression (`@`) around file calls in the backend | `feat: change the admin password in the system view` |
| The upload delete confirmation was not translated | `fix: translate the upload delete confirmation` |
| List views showed only "Validation Error" instead of the cause | `fix: show validation details in list views` |
| Adding a page ended in the pages list, so the content had to be edited in a second step | `feat: continue the add-page flow directly in the inline editor` |
| The pages list had no icon for "view the page", and the icon tooltips were named inconsistently | `feat: streamline page navigation with view/edit actions and consistent icon tooltips` |
| No icon for "edit the content inline" (only "edit the document") | `feat: add edit-content icon and use icon actions in jump-to-page` |
| The signed-in user and role were nowhere visible | `feat: show the signed-in user and role in the account menu` |
| No way to reach the actions by keyboard | `feat: add the keyboard command palette` |

## Won't do

Proposals that look like a gap but are not: closing them would cost more than
they gain. Recorded so they are not brought up again.

| Item | Why not |
| --- | --- |
| Fill the start page (`/`) with a quick-action dashboard | Measured as a regression: the admin panel is `position: fixed` over the site preview, so any content fills `#app` and hides the site (panel 97 px → 829 px, preview 747 px → ~0 px). `/` renders nothing on purpose — the JACO logo links to it from every view and is the way back to browsing the site. See the comment in `frontend/view/StartView.js`. |
