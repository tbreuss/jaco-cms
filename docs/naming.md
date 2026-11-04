# Naming conventions

Naming conventions for backend (PHP) and frontend (JavaScript) code.

## PHP backend

Function names start with a verb. The return type picks the exact shape.
File names follow the namespace, see the directory contract in each `_index_.php` file.

### Commands: `verb_object`

Functions with side effects return a `Payload`:

- `delete_page`, `create_upload`, `record_login_failure`, `restore_backup`

Endpoint files use a fixed vocabulary instead: `action` and `responder` in `api/`, `command` and `responder` in `cli/`.

### Queries: `get_`, `find_`, `read_`, `load_`, or a bare verb

Functions without side effects return plain values:

- `get_` is a cheap direct accessor: `get_title`, `get_version`
- `find_` is a search that may come up empty, list versus single item: `find_pages` versus `find_page`
- `read_` reads bytes from disk: `read_file`, `read_login_history`
- `load_` loads with a fallback chain: `load_translation` (language, then the default language)
- pure computation without one of these cases takes a bare verb: `combine`, `encode_json`

### Predicates: always `is_`, `has_`, or `can_`

A boolean answer carries its prefix, a bare adjective never stands alone:

- `is_authenticated`, `is_allowed_attribute`

The reverse holds as well: no `is_` prefix without a boolean. A function returning seconds is `get_login_block_remaining`, not `is_login_blocked`.

### Factories: `create_` constructs, `Payload` signals effects

`create_` builds domain things: objects (`create_list`, `create_one`), tokens (`create_token`), stored records (`create_page`, `create_upload`). Whether anything was persisted is not in the prefix but in the return type: a `Payload` means the function did something, a plain value means it only built something. There is no `build_` prefix; splitting the family would duplicate what the return type already says.

### Errors and handlers

Error list builders end in `_errors`: `transform_client_errors`. Fixed error data is not a function at all but a namespaced constant: `INVALID_SERVICE_STATUS_ERRORS`. Callbacks take a `handle_` prefix: `handle_embedded_data_url_images`.

### Affixes

- `_rec` marks the recursive variant: `remove_dir_rec`, `find_files_rec`
- `_value` marks the single value variant: `sanitize_value`, `validate_value`

A leading underscore marks module private helpers that are only called inside their own file, for example `_rrmdir`.

### Rule vocabulary: validators ask, sanitizers convert

Rule names travel as strings in rule arrays, so they form their own closed vocabulary – but it follows the same system. Validators return a result object and read as predicates: `is_required`, `is_numeric`, `is_string`, `has_file_extension`, `is_file_writable`. Sanitizers return the converted value and read as transformers: `to_integer`, `to_float`, `to_string`, `to_filename`, `transliterate_filename`, `escape_html`. The dispatch maps in `core/data/_index_.php` are the full list of supported names on both sides; an unknown name is rejected at runtime and covered by tests.

Pipeline order is sanitize first, then validate, and `is_required` belongs in front: the dispatcher reports only the first failure per field, so a blank value answers `cannot be blank` instead of a length complaint. Traversal rejection lives on three levels on purpose (`to_filename`, the `_traversal_error` guard in the validators, the filesystem functions): defense in depth, do not "simplify" one of them away.

Kept out of the scheme on purpose: the filesystem `file_exists` mirrors the native function exactly.

## Explicitly out of scope

- **PHP mirrors** (`file_exists`, `dir_exists`, `random_string`) keep the native names for least surprise, even where this guide would pick another form. `random_string` mirrors the native `random_bytes` and `random_int` family.
- **Adapter vocabulary** (`api_errors`, `cli_errors`, `maintenance_response`) is a fixed convention of the api and cli entry points.
- **Handler callbacks** referenced as strings (`error_handler` in `api.php`) keep their role names, static analysis cannot verify string callables.
- **No abbreviations** in new names. The `_rec` affix above is grandfathered, not a template.
- **No namespace stutter**: inside `jaco\domain\page` it is `find_pages`, never `page_find_pages`.
- **Qualify natives on collision**: a function named after a native (`is_string`, `is_numeric`) shadows it inside its namespace, so the bodies call `\is_string` and `\is_numeric` explicitly. Unqualified, the names resolve to the function itself – infinite recursion.

## JavaScript frontend

The frontend is vanilla JavaScript with Mithril.js, no build step. Files are ES modules loaded directly by the browser. Names follow the surrounding code; this document collects the rules so new code matches.

### Files

- **Views** are `PascalCase` with a `View` suffix: `PageListView.js`, `PageEditView.js`, `LoginView.js`. Subdirectories group by domain: `view/page/`, `view/backup/`, `view/upload/`.
- **Components** are `PascalCase`: `ButtonPrimary.js`, `FilterInput.js`, `PageSettingsDialog.js`. One component per file.
- **Models** are `PascalCase` with a `Model` suffix: `PageListModel.js`, `PageEditModel.js`, `BackupViewModel.js`.
- **Libraries** are `camelCase`: `auth.js`, `iframe.js`, `dirty.js`, `format.js`. They export a single object or function of the same name.
- **State** files are `camelCase`: `state.js`, `actions.js`.
- **Service** files are `PascalCase`: `Notifications.js`, `api.js`.
- **Vendored** code lives under `vendor/` and is never edited.

### Exports

- Views, components and models export a named const matching the file name: `export const PageListView = { ... }`, `export const Icon = { ... }`.
- Libraries export a named const of the same camelCase name: `export const iframe = { ... }`, `export const auth = { ... }`.
- A module with a single default export uses `export default`: `dirty.js` exports `dirty` as default.
- Constants are `UPPER_SNAKE_CASE`: `ROLE_EDITOR`, `ROLE_ADMIN`.

### Functions and methods

- `camelCase`, verb-first: `load`, `loadAll`, `addSuccess`, `formatDateTimeFull`.
- Boolean answers carry an `is`/`has`/`can` prefix: `hasElement`, `isLoading`.
- Event handlers are `on` + event: `onclick`, `oninput`, `onchange`, `onSubmit`. Mithril handlers use the same names.
- Lifecycle and hook methods describe the action: `init`, `initPreview`, `mountView`, `clearFixture`.

### Variables and state

- State objects are `camelCase` nouns: `state`, `items`, `filter`, `sort`.
- Boolean flags read as adjectives: `isLoading`, `isSortable`, `isDragging`.
- DOM references are descriptive: `editableImageExtended`, `divElement`.
- Temporary or loop variables stay short: `i`, `j`, `e`, `t`.

### CSS classes

- Component-owned classes use a `j-` prefix is planned but not yet done; current classes are kebab-case: `jaco-media-item`, `editable-link`, `elements-note`.
- Modifier classes are chained: `jaco-content-add-button--slot`, `jaco-content-add-button--repeater`.
- State classes are `is-` prefixed: `is-dragging`, `is-error`, `is-loading`.

### Routes and IDs

- Route paths are kebab-case: `/page-add`, `/card-en.html`.
- Route IDs (in `app.js`) are dot-separated: `page.add`, `card.en.html`.
- DOM element IDs are camelCase: `editableLink`, `editableImageExtended`.
- CSS/translation keys are Title Case with spaces: `'Add block'`, `'drag to reorder'`, `'No images in the media library.'`.

### Translation keys

- All user-visible text goes through `t('Key', 'fallback')`.
- Keys are English Title Case: `'Add block'`, `'drag to reorder'`, `'No images in the media library.'`.
- The fallback is the English text; other languages translate the key.
- German literals used as keys (e.g. `'Funktion hinzufügen'`) are legacy and should not be introduced in new code.

### Explicitly out of scope

- **Quill/Ace/QUnit class names** (`.ql-*`, `.ace_*`, `.qunit-*`) keep their upstream names.
- **Vendored code** under `vendor/` and `asset/` is never edited.
- **Mithril conventions** (`m()`, `m.route`, `m.render`) follow the framework's own API.
