# Backlog

Small, concrete gaps in existing features, plus larger strategic gaps
ranked by value for Jaco's target audiences. Status values: `open`, `doing`, `done`.
Effort is a rough t-shirt size.

| Status | Item | Notes | Effort |
| --- | --- | --- | --- |
| open | Settings.php lookup uses hardcoded `jaco-data` instead of configurable `dataDir` | `api.php`/`cli.php` build `settings.php` path before `create_default_config()` runs. A custom `dataDir` cannot be found. | S |
| open | Point the E2E suite at Apache | `playwright.config.mjs` hardcodes `php -S … router.php`; make server switchable via `JACO_E2E_APACHE=1`. Depends on Apache image. | S |
| open | The gallery model is open | `data-jaco-image--lightbox` was removed from the contract. Author supplies both files, a container marks the group. Open: container attribute name, what a click replaces, whether a save builds structure. | L |
| open | Rename pages in the page manager | Promised in `features.html` but missing: no `rename_page`, route or view. Copy+delete breaks internal links. | S |
| open | Generate the social cards from a source file | `media/social-card-{en,de}.png` are finished bitmaps with no template/script. Add a generator (script or SVG+renderer) so a domain change or new language is one line instead of an image edit. | S |
| open | Generate `sitemap.xml` from the pages | Sitemap is hand maintained. Jaco could emit `sitemap.xml` with `lastmod` from file mtime and `hreflang` alternates from language trees. | M |
| open | Create and delete site templates in the admin | Templates are created/removed on the file system only. Add input for new names, validation and delete action. | S |
| open | Demo playground: a reset installation with an admin account | A second installation showing every admin feature with a public admin account, restored from a tarball hourly. | M |
| open | Maintenance toggle in the admin panel | `maintenanceMode` works but is `settings.php`-only; add UI toggle on the system page. | M |
| open | Rename "maintenance mode" | Blocks writes in the admin, not visitors. Rename to what it does ("editing locked"). | S |
| open | Prune old backups on write | `prune_old_backups()` runs only on backup listing; also prune when a backup is written. | S |
| open | Warn on deleting referenced files | `upload/delete` and `page/delete` do not check references from other pages. | M |
| open | Upload progress and size feedback | `UploadListView` posts all files via one `Promise.all` with a single catch. | M |
| open | Login throttle only covers one address at a time | Add a capped global bucket plus recovery key in `settings.php`. | M |
| open | Login history read-modify-write can lose a failure | Two simultaneous failed logins both read, append and write; last write wins. Hold a lock across read/write. | S |
| open | Loading and not-found states in detail views | Detail views render empty forms on slow networks; cannot be told apart from deleted entries. | S |
| open | Make `iframe.getHTML()` non-destructive for observers | `getHTML()` tears down editor chrome on every read. Serialize from a cloned `body` so observers leave the live preview alone. | M |
| open | Consolidate component CSS under a `.j-` prefix | Component classes (`.modal`, `.field-error`, …) have no namespace; rename to `.j-*`. | M |
| open | Paginate lists, collapse admin navigation on mobile | No limit/offset; full navbar without a mobile menu. | M |
| open | Skeleton tables for list loading states | Lists pop in instead of rendering in a stable frame. | M |
| open | Tables in the rich-text editor | Sanitizer allows `table`/`tr`/`td`, but Quill toolbar has no table tool. | M |
| open | Duplicate content blocks | Blocks can only be added empty (from fragment) or deleted. | S |
| open | Reorder blocks without draggable | Only `draggable` lists reorder; `top`/`bottom`/`any` have no grip/buttons. | S |
| open | Alt text is only asked for in one image mode | Only `choice` edits `alt`; plain image offers no field. Offer in every mode. | S |
| open | Text editors with a smaller set of tools | Need `--tools="basic"`/`"full"` per block in `editor.js` and tag whitelist per value in the sanitizer. | M |
| open | Most `--tools` on the pages change nothing | 246 markers carry `--tools`; 30 need it, 216 do not. Remove the unnecessary ones. | M |
| open | Fragments in subdirectories | Fragment value must follow file syntax; wrap `text-en.html`/`text-de.html` in one root element. | M |
| open | Links get no `rel` and no `title` from the dialog | Link dialog edits `href`/`target`; add `rel="noopener noreferrer"` for `_blank` and a `title` field. | S |
| open | Video iframes have no title | Video dialog edits `src` only; add a `title` field. | S |
| open | The sanitizer drops attributes a page needs | `loading`, `decoding` on `img`; `allow`, `loading`, `referrerpolicy` on `iframe`; `lang` everywhere are not whitelisted. Add with closed value sets. | S |
| open | Unlink in the link dialog | Dialog changes URL/target but cannot turn a link back into text. | S |
| open | Restore recently deleted blocks | A deleted block is gone (page-level restore only via backup). | S |
| open | Semantic heading inside the line editor | Line editor wraps text in `<p>`; CSS keeps heading look, but real `<h1>`–`<h6>` would need a clipboard matcher. | S |
| open | Share one Ace editor helper across views | `PageEditView`, `CssEditView`, `BackupEditView` copy the same `ace.edit` setup. Bundle into one `lib/` helper. | S |
| open | Light theme flash on hard refresh | Briefly shows light theme on hard refresh in dark mode. | S |
| open | Shortcuts in the command palette | Palette lists commands but binds no keys. | S |
| open | Recently used commands on top of the palette | Palette always shows registry order; used commands should float to the top. | S |
| open | Multi-step processes for commands | A command may need more than one step (template, filename, title). Current dialog asks all at once. | M |
| open | Remove the remaining error suppression in `install.php` | Five `@` calls survive; replace with explicit checks. | S |
| open | Restructure QUnit test suite | Monolithic `tests.js` (~2700 lines): split into per-topic files, add `expect()` declarations. | M |
| open | Style form selects instead of leaving the bezel to the browser | `<select>` keeps native appearance; Chromium paints its own bezel. | S |
| open | Content lists keep their state on DOM nodes | Flags live on DOM nodes; move state into a `Map` keyed by the list element. | M |
| open | The editor's chrome lives in the content DOM | Editor chrome (buttons, bars, chooser) is part of the saved content DOM. Move chrome into a parallel layer. | L |
| open | Drag reorder does not restack mid-drag | Button order only corrects on drop, not during drag. | S |
| open | Position attributes changing under a mounted list | Only `--position` is handled when it changes; `--sortable`/`--label` are not. | S |
| open | Content-list buttons carry their language in the page | Button label comes from `data-jaco-title` literal, not a translated key. | M |
| open | Unsaved inline edits are lost when a save keeps failing | `api.js` shows the error (request failed, session expired, server error), but nothing keeps the typed content: after a lost session or a server error only the discard prompt stands between the user and the loss. Open: whether a draft (for example in `sessionStorage`) is wanted and when it is offered back. | M |
| open | The inline editor is called through `window.*` globals | `iframe.js` calls `serializeBody`, `initRichTextEditor`, `initImages` and more on the page's `window`; a missing one fails with a `TypeError` far from the cause. One small object (init, serialize, destroy) would be one contract and easier to fake in tests. | M |
| open | Saving a page does not detect a concurrent change | `page/update-body` overwrites: with two tabs or two devices the last save wins. A backup is written first. Open: whether this is needed at all, Jaco being a single-user system. | M |
| open | Roundtrip tests for the inline editor with a real Quill | The view tests fake `iframe`; `serializeBody` has two DOM tests. Missing: open a block, edit, serialize, compare, for unknown inline tags, lists, videos and nested content lists. Also the safety net for a Quill upgrade. | M |
| open | Editing a block drops markup Quill does not know | Quill rebuilds the block: unknown attributes and tags are lost and a nested list does not survive (see the note in the content-list tests). `removeBoundaryEmptyParagraphs` and `normalizeVideoFrames` patch single artifacts. Open: which markup has to survive. | L |
| open | Upgrade Quill 1.3.7 to 2.x | The saved HTML would need `getSemanticHTML()` instead of `.ql-editor.innerHTML` (the instance is not kept in `editor.js`), `quill.snow-customized.css` (212 `ql-` rules) and `editor.css` have to be rebased, `matchVisual` is gone and the keyboard `key` is case-sensitive. Loading assumes an UMD build that sets `window.Quill`. Open: whether and when. | L |
| open | Editor handlers replace instead of add | `PageInlineEditView` assigns `iframe.getElement().onload` and `editor.js` assigns `window.onresize`: a second assignment, or a handler of the page, overwrites the first. Use `addEventListener`. | S |
| open | `iframe.fileName()` takes the preview path as it is | `/de/` becomes `de/`, not `de/index.html`, and a URL that is not a file name (a rewrite) is not mapped to one. The server rejects unknown names, so the save fails instead of writing. Check which URLs the preview can have. | S |
| open | The load timeout of the editor is not tested | `EDITOR_LOAD_TIMEOUT_MS` is a constant in `iframe.js`; the timeout path has no QUnit test because 10 s cannot be shortened from a test. | S |
| open | The sanitizer removes `aria-label` from links and navigation | The allowlist in `html.php` has `aria-label` on `button` only, so `aria-label` on `a` and `nav` is dropped on every save (seen on `website/elements.html`). Related: the row on `allow`, `loading` and `referrerpolicy` above. Open: which `aria-*` attributes on which tags, and whether `role` is dropped too. | S |
| open | Page scripts change the DOM the inline editor saves | Page scripts run in the editor iframe and `serializeBody()` reads the result: `nav.js` rewrites the language link (`de/elements.html` becomes `/de/index.html`), `theme.js` writes a theme-dependent `aria-label` and `title` on the theme button. Open: do not run scripts in edit mode, mark attributes set by scripts, or make the scripts leave the editor alone. | M |
| open | The dirty check does not see line breaks inside `<pre>` | `normalizeForCompare` treats whitespace with a line break as formatting, also inside `<pre>`: a change that only adds or removes line breaks there raises no discard prompt. | S |
| open | Stray `</div>` in `website/elements.html` | One `</div>` too many around line 256 (the repeater section). The browser ignores it and a save through the editor drops it. | S |
| open | `assemble-docroot.sh` fails on a second run | `rm -rf .docroot` ended with `Permission denied` on `.docroot/jaco` and an empty directory stayed behind. Cause unknown (flags, ACL or owner were not checked). | S |
| open | Small leftovers in `PageInlineEditView.js` | A comment is duplicated in `savePageSettings`, and `bindShortcut` removes the old listeners twice. | S |

## Done

| Item | Commit |
| --- | --- |
| Site templates with apply-to-all | `feat: add site templates with apply-to-all` |
| Saving a page keeps its site template marker | `refactor: the data attributes are one contract of thirteen names` |
| The demo link nests the admin into itself | `fix: the demo link no longer nests the admin into itself` |
| Page title, meta description and robots UI | `feat: add page settings for title, description and robots` |
| Translate Quill's internal labels | `feat: translate Quill toolbar labels` |
| Dark mode for the Quill editor chrome | `feat: theme the Quill editor chrome` |
| 404 page for the public website | `feat: finish the public 404 page` |
| Translate the inline editor and media picker | `feat: translate the inline editor and media picker` |
| Choose uploaded images in the editor | `feat: add a media picker for image markers` |
| Save & Close must not close on validation error | `fix: do not close editor on validation error` |
| Redirect to login on expired token | `fix: redirect to login on expired session` |
| Warn on unsaved changes | `fix: warn on unsaved changes` |
| Readonly / disabled fields distinguishable from normal fields | `style: distinguish disabled/read-only inputs` |
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
| Split 401 from 403 | `feat: introduce roles for admin, editor and viewer` |
| Error suppression (`@`) around file calls in the backend | `feat: change the admin password in the system view` |
| The upload delete confirmation was not translated | `fix: translate the upload delete confirmation` |
| List views showed only "Validation Error" instead of the cause | `fix: show validation details in list views` |
| Adding a page ended in the pages list | `feat: continue the add-page flow directly in the inline editor` |
| The pages list had no "view page" icon | `feat: streamline page navigation with view/edit actions and consistent icon tooltips` |
| No icon for "edit the content inline" | `feat: add edit-content icon and use icon actions in jump-to-page` |
| The signed-in user and role were nowhere visible | `feat: show the signed-in user and role in the account menu` |
| No way to reach the actions by keyboard | `feat: add the keyboard command palette` |
| Flexible content slots | `feat: flexible content slots (#26)` |
| Build the repeater as the contract describes it | `feat: add content elements and slot-based page templates` |
| Move the pages, templates and docs to the new contract | `feat: add content elements and slot-based page templates` |
| The server cannot check the structure of a repeater | `feat: flexible content slots (#26)` |
| Nesting of roles is only what the click handler happens to do | `feat: flexible content slots (#26)` |
| Build the image modes | `feat: a reference page for every marker, in English and German` |

## Strategic gaps

Larger gaps ranked by value for Jaco's target audiences (restaurants, clubs,
small companies, private sites). Deliberately not a roadmap; each item states
the gap, who hurts, and the rough size. Concrete, implementation-ready items
live in the table above.

### 1. Self-update mechanism

**Implemented.** `sys:check-update` and `sys:update` download a published
release, back up the previous `jaco/` and replace only the application
directory. Settings, backups, runtime, templates, media and public pages stay
untouched. See [install.md](install.md#updating).

### 2. Account management UI

Roles and multiple users now exist. Missing: an interface to create, edit and
remove accounts from the admin instead of editing `settings.php` by hand.

### 3. SEO basics

Page title, meta description and robots are managed through page settings.
Still missing: sitemap export, Open Graph management and a redirect on rename
(a renamed page leaves its old URL as a 404).

### 4. Contact form

Restaurants (reservations), hairdressers (appointments), small companies
(quotes) need a form. A file-based CMS can only offer a `mailto:` hack or an
external service.

### 5. Drafts and preview before publish

Every save goes live immediately. No draft, no preview without saving, no
scheduling.

### 6. Media housekeeping

Upload list without folders, without unused-file detection, without alt text
care.

### Explicitly not recommended

Content multilingualism (language folders are enough), site search
(static sites of this size rarely need it), frontend analytics.
