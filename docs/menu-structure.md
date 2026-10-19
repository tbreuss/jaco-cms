# Admin menu structure

Status: proposal, not implemented. Tracked in [backlog.md](backlog.md).

## Why

The navbar currently mixes three concerns: content, page actions and
account/admin tasks. That makes the same action appear twice, puts a public page
under an admin menu and separates the account actions from each other.

## Current structure

```text
JACO  Quick Action ▾  Pages  Uploads  Backups  CSS  System ▾  Change password  Logout  Theme
```

Problems:

- **Quick Action is a grab bag.** It contains navigation (`Go to page`) and page
  writes (`Edit page`, `Add page`, `Copy page`, `Delete page`) that also belong
  to the page list.
- **About sits under System.** It is active for every role while the other
  System entries are admin-only, so the group has no single meaning.
- **Account actions are split.** `Change password` is a top-level link,
  `Logout` is another, and the theme is a third control.
- **CSS is named after the technology**, not the task, and its menu entry is
  disabled even though the stylesheet can be read.
- **Disabled entries are spread over the bar**, so a viewer cannot see which
  capabilities belong together.

## Target structure

```text
JACO  Pages  Media  Backups  Design        [Edit page]  Admin ▾  Account ▾
```

- **Content (left):** Pages, Media/Uploads, Backups, Design/CSS.
- **Context action:** `Edit page` for the page currently shown in the preview.
  A viewer opens the same inline editor without a working save.
- **Admin (right):** System environment, PHP error log, PHP info. Visible for
  everyone, entries disabled below `admin`.
- **Account (far right):** About, Change password, Theme, Logout.

### Where each action lives

| Action | New home | Notes |
| --- | --- | --- |
| Go to page | Navbar search / command palette, or the Pages view | Optional; no longer mixed with page writes |
| Edit page | Context button next to the navbar | Viewer: inline editor without save |
| Add page | Pages list, toolbar button | Editor/admin only |
| Copy page | Pages list, row action | Editor/admin only |
| Delete page | Pages list, row action | Editor/admin only |
| About | Account menu | Active for every role; protected endpoint |
| Change password | Account menu | Disabled when `passwordChange` is false |
| Theme | Account menu, or keep the existing switch | Decision below |
| Logout | Account menu | |
| Design/CSS | Content navigation | See open decision below |

### Roles

| Item | `viewer` | `editor` | `admin` |
| --- | --- | --- | --- |
| Pages, Media, Backups | active | active | active |
| Design/CSS | read-only | read-only | writable |
| Edit page | active, no save | active | active |
| Add/Copy/Delete page | disabled | active | active |
| Admin menu entries | disabled | disabled | active |
| About | active | active | active |
| Change password | active unless `passwordChange => false` | active | active |
| Logout, theme | active | active | active |

Disabled entries stay **visible** with `aria-disabled`; they are not hidden.

## Migration order

1. **Account menu.** Move About, Change password, Theme and Logout into one
   dropdown. Remove About from System.
2. **Quick Action.** Rename it to `Page` or remove it. Move Add/Copy/Delete to
   the Pages list; keep `Edit page` as a context action and `Go to page` as a
   separate jump/search.
3. **System → Admin.** Rename the group and keep only administration entries
   in it.
4. **CSS → Design.** Rename the entry and decide whether editors may open it
   read-only.
5. **Keyboard and touch.** Replace hover-only dropdowns with click/keyboard
   handling. This overlaps the existing mobile-navigation backlog item.

## Open decisions

- **Theme:** keep the native switch in the navbar or move it into the Account
  menu? The switch is a quick, frequently used control; the account menu is
  tidier.
- **Design for editor/viewer:** active read-only (matches the CSS route) or
  visible-disabled below admin (matches the current rule)?
- **Go to page:** a small search field, a command palette, or a link inside the
  Pages view?
- **Account menu trigger:** user name, role badge, or a generic “Account”
  button?

## Acceptance criteria

- No action appears both in Quick Action and in the Pages list.
- About is not part of the admin-only group.
- Account actions are in one menu.
- Restricted entries remain visible and accessible (`aria-disabled`).
- Dropdowns work with keyboard and touch, not only hover.
- QUnit layout tests and `docs/features.md` describe the same structure.
