# Admin menu structure

Status: implemented in the working tree.

## Navigation

```text
JACO | Content ▾ | This page ▾ | [🔍 Jump to page] | [⌨ Commands] | Admin ▾ | Account ▾ | [Theme ▾]
```

Four menu groups plus three direct controls.

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
- **Jump to page** opens the search dialog from every view.
- **Quick Action** is gone.
- About is no longer part of the admin group.

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
- `Jump to page` is global and not part of the Pages view.

## Roles

| Element | Admin | Editor | Viewer |
| --- | --- | --- | --- |
| Content: Pages, Media, Backups | active | active | active |
| Design | writable | read-only | read-only |
| This page: Edit page | active | active | active, no save |
| This page: Copy/Delete page | active | active | visible-disabled |
| Admin entries | active | visible-disabled | visible-disabled |
| Account: About, Logout | active | active | active |
| Account: Change password | active | active | active unless `passwordChange => false` |
| Theme | active | active | active |
| Jump to page | active | active | active |
| Pages: Add/Copy/Delete | active | active | visible-disabled |

Disabled controls stay visible and use `aria-disabled`; the API remains the
security boundary.

## Jump to page

- Opens the existing page dialog with an autofocused filter.
- Searches filename, path and title, case-insensitive.
- `↑`/`↓` selects, `Enter` opens, `Esc` closes.
- Loads the page in the preview iframe and returns to the start route.
- Uses `page/list` with an empty filter, so it is independent of the filter in
  the Pages view.
