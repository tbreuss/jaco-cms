# Admin menu structure

Status: implemented.

## Navigation

```text
JACO | Content ▾ | This page ▾ | Admin ▾ | [⌘K Commands] | Account ▾ | [Theme ▾]
```

Four menu groups plus two direct controls. The Commands badge shows `⌘K` on
macOS and `Ctrl+K` elsewhere.

## Groups

```text
Content ▾
├─ Pages
├─ Media
├─ Backups
└─ Design

This page ▾
├─ Edit page
├─ Copy page
└─ Delete page

Admin ▾
├─ Environment
├─ PHP error log
└─ PHP info

Account ▾
├─ About
├─ Change password
└─ Logout
```

- **Theme** stays a direct switch in the navbar.
- **Jump to page** is available from the command palette.
- **Commands** opens the command palette (Ctrl/Cmd+K).
- **Quick Action** is gone.
- About is no longer part of the admin group.
- The account menu names the signed-in user and its role on top (`user (role)`).

## Interaction

- On a fine pointer with hover: entering the trigger opens the menu, leaving closes it.
- Touch and keyboard: click, `Enter` or `Space` toggles the menu.
- Only one menu is open at a time.
- `Escape` and a click outside close the menu.
- `ArrowDown`/`ArrowUp` on the trigger open it and focus the first/last entry.
- Triggers expose `aria-haspopup="menu"`, `aria-expanded` and `aria-controls`.
- Entries use `role="menuitem"`; disabled entries stay focusable with `aria-disabled`.
- Hover still highlights the trigger; the open state is driven by pointer type and media query.

## Pages view

```text
Pages                                      [+ Add page]
Filter: …
────────────────────────────────────────────────────────────
a.html    Startseite      12.10.2026      [Edit] [Copy] [Delete]
```

- `Add page` is the toolbar action.
- `Edit`, `Copy` and `Delete` are row actions.
- `Copy` receives the row's file and title; it no longer assumes the page in
  the preview.
- `Jump to page` is available from the command palette, not the Pages view.

## Inline editor

The inline editor toolbar offers **Page settings**, **Cancel** and **Save**.

- **Page settings** opens a modal for the page title, meta description and robots
  value. It is available to editors and admins; viewers see the editor but not
  the settings button.
- Saving the settings updates only the document head. Unsaved body changes stay
  in the editor.
- The raw page editor exposes the same title, description and robots fields.

## Roles

| Element | Admin | Editor | Viewer |
| --- | --- | --- | --- |
| Content: Pages, Media, Backups | active | active | active |
| Design | writable | read-only | read-only |
| This page: Edit page | active | active | active, no save |
| This page: Copy/Delete page | active | active | visible-disabled |
| This page: Page settings | active | active | visible-disabled |
| Admin entries | active | visible-disabled | visible-disabled |
| Account: About, Logout | active | active | active |
| Account: Change password | active | active | active unless `passwordChange => false` |
| Theme | active | active | active |
| Jump to page (Commands) | active | active | active |
| Commands | active | active | active |
| Pages: Add/View/Edit content/Edit/Copy/Delete | active | active | visible-disabled |

Disabled controls stay visible and use `aria-disabled`; the API remains the
security boundary.

## Jump to page

- Opens from the command palette; there is no direct navbar control.
- Searches filename, path and title, case-insensitive.
- `↑`/`↓` selects, `Enter` opens, `Esc` closes.
- Loads the page in the preview iframe and returns to the start route.
- Uses `page/list` with an empty filter, so it is independent of the filter in
  the Pages view.
- Every row carries three icon actions: view the page, edit the content inline
  and edit the document.

## Commands

- Opens the command palette, centred over the site, with an autofocused
  search field.
- `Ctrl`/`Cmd`+`K` opens it, including over an open modal. The navbar carries
  a visible trigger with the shortcut hint.
- Typing filters, `↑`/`↓` selects, `Enter` runs, `Esc` closes and returns the
  focus to where it came from.
- The commands are defined once in `jaco/frontend/lib/commands.js`; the
  palette only renders and runs them.
- Commands the current role or the current context does not allow are not
  listed — the palette is for running things, not for showing what is
  refused. The navigation itself keeps those entries visible but disabled.
