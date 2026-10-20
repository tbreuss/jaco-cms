# Contributing to Jaco CMS

Thank you for improving Jaco. This document is for human contributors and for
automated assistants (any AI or bot): follow it together with
[docs/development.md](docs/development.md).

## Documentation

- **Repository root** — uppercase names for standard entry points: `README.md`,
  `CONTRIBUTING.md`, `INSTALL.md`.
- **`docs/`** — lowercase names; multi-word files use hyphens (`menu-structure.md`),
  single words are allowed (`features.md`). Index: [docs/readme.md](docs/readme.md).

## Before you open a pull request

1. Read [docs/development.md](docs/development.md) for layout, conventions, and test commands.
2. Keep changes **focused** — every line should trace to the task. No drive-by refactors,
   formatting sweeps, or speculative features.
3. Match the **existing style** in the files you touch (PHP, JavaScript, CSS, docs).
4. Add or update **tests** when behaviour changes (PHPUnit and/or QUnit as appropriate).
5. Run the same checks CI runs when you can (see [development.md](docs/development.md)). At minimum for frontend
   work: `node jaco/test/js/runner.mjs` with a local `php -S` server.
6. Use **commit messages** in the style of recent history: `fix:`, `feat:`, `style:`,
   short subject, English.

## Translations

User-visible strings in the admin UI belong in **all** language files under
`jaco/language/` (`en.json`, `de.json`, `fr.json`, `it.json`). Use the existing
`t('Key')` pattern; do not hard-code UI text in views.

## What not to commit

- `jaco/settings.php`, tokens, passwords, or other secrets
- Local test sites or scratch HTML under the web root unless the change requires it
- Vendored asset updates without a deliberate upgrade (Ace, Mithril, etc.)

## Product and UI behaviour

For navbar, roles, modals, and command palette behaviour, see
[docs/menu-structure.md](docs/menu-structure.md). For installation and server
setup, see [INSTALL.md](INSTALL.md).

## Questions

If requirements are ambiguous, ask before implementing. Prefer a smaller, verified
change over a large guess.
