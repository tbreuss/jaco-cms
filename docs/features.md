# Jaco CMS – feature catalogue

One sentence per implemented, user-facing feature. The invariants behind them live in
[constraints.md](constraints.md), the shared vocabulary in [glossary.md](glossary.md),
the admin navigation in [menu-structure.md](menu-structure.md), and installation in
[install.md](install.md). Known gaps stay in [backlog.md](backlog.md).

## Content

| Feature | What it does | Details |
| --- | --- | --- |
| Flat-file pages | Pages are `.htm`/`.html` files below the web root; the file itself is the data store. | [Glossary](glossary.md) |
| Content blocks | Editors add, remove and, where the list uses `draggable`, reorder reusable blocks from templates. | [Glossary](glossary.md) |
| Image gallery | Marked gallery images show thumbnails that link to generated full-size versions. | [Glossary](glossary.md) |
| Media library | Editors upload, view and delete images and PDFs with extension checks and size limits, and name collisions never overwrite existing files. | [Constraints](constraints.md) |
| Templates | Page and block templates are files under `jaco/_template` and are selected by the admin when creating pages and blocks. | [Glossary](glossary.md) |

## Editing

| Feature | What it does | Details |
| --- | --- | --- |
| Inline editing | Editors change marked regions directly in the live preview, with rich text, images, links, embedded videos and gallery images; viewers can open the same editor but cannot save. | [Constraints](constraints.md) |
| Page manager | Editors create pages from templates, copy, edit and delete pages, and continue a newly created page in the inline editor. | [Constraints](constraints.md) |
| Stylesheets | Admins list, view and edit the site CSS from the admin; viewers and editors can read it. | [Constraints](constraints.md) |

## Access and safety

| Feature | What it does | Details |
| --- | --- | --- |
| Authentication | Users log in with a password and receive a JWT; logout and a guarded password change are available. | [Constraints](constraints.md) |
| TOTP | Each user can require a per-user TOTP code as a second login step, or leave it off. | [Constraints](constraints.md) |
| Roles | Viewer, editor and admin roles gate every API route and the matching admin controls. | [Constraints](constraints.md) |
| Login throttle | Failed logins are counted per address and block that address for ten minutes after three attempts. | [Constraints](constraints.md) |
| Backups | Every page, stylesheet or upload change first writes a timestamped backup that can be viewed, restored or deleted. | [Constraints](constraints.md) |
| Maintenance mode | Maintenance mode blocks writes in the admin but does not hide the site from visitors. | [Constraints](constraints.md) |

## Find and operate

| Feature | What it does | Details |
| --- | --- | --- |
| Jump to page | Jump to page filters all pages by filename or title and opens the chosen page in the preview. | [Menu](menu-structure.md) |
| Command palette | Ctrl/Cmd+K opens a searchable palette for role- and context-aware commands. | [Menu](menu-structure.md) |
| Languages | The admin UI is available in English, German, French and Italian, with a per-user language. | [Constraints](constraints.md) |
| Theme | Light, dark and auto themes are explicit, persisted choices applied before first paint. | [Constraints](constraints.md) |
| Diagnostics | About is available to every role, while environment, PHP error log and phpinfo are admin-only. | [Constraints](constraints.md) |
| CLI and installer | A browser installer creates `settings.php`, while the CLI issues tokens, sets TOTP, generates password hashes and clears login blocks. | [Install](install.md) |
