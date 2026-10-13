# Naming conventions for backend functions

Function names start with a verb. The return type picks the exact shape.
File names follow the namespace, see the directory contract in each
`_index_.php` file.

## Commands: `verb_object`

Functions with side effects return a `Payload`:

- `delete_page`, `create_upload`, `record_login_failure`, `restore_backup`

Endpoint files use a fixed vocabulary instead: `action` and `responder`
in `api/`, `command` and `responder` in `cli/`.

## Queries: `get_`, `find_`, `read_`, `load_`, or a bare verb

Functions without side effects return plain values:

- `get_` is a cheap direct accessor: `get_title`, `get_version`
- `find_` is a search that may come up empty, list versus single item:
  `find_pages` versus `find_page`
- `read_` reads bytes from disk: `read_file`, `read_login_history`
- `load_` loads with a fallback chain: `load_translation` (language,
  then the default language)
- pure computation without one of these cases takes a bare verb:
  `combine`, `encode_json`

## Predicates: always `is_`, `has_`, or `can_`

A boolean answer carries its prefix, a bare adjective never stands alone:

- `is_authenticated`, `is_allowed_attribute`

The reverse holds as well: no `is_` prefix without a boolean. A function
returning seconds is `login_block_remaining`, not `is_login_blocked`.

## Factories: `create_` constructs, `Payload` signals effects

`create_` builds domain things: objects (`create_list`, `create_one`),
tokens (`create_token`), stored records (`create_page`, `create_upload`).
Whether anything was persisted is not in the prefix but in the return
type: a `Payload` means the function did something, a plain value means
it only built something. There is no `build_` prefix; splitting the
family would duplicate what the return type already says.

## Errors and handlers

Error list builders end in `_errors`: `transform_client_errors`.
Fixed error data is not a function at all but a namespaced constant:
`INVALID_SERVICE_STATUS_ERRORS`. Callbacks take a `handle_` prefix:
`handle_embedded_data_url_images`.

## Affixes

- `_rec` marks the recursive variant: `remove_dir_rec`,
  `find_files_rec`
- `_value` marks the single value variant: `sanitize_value`,
  `validate_value`

A leading underscore marks module private helpers that are only called
inside their own file, for example `_rrmdir`.

## Explicitly out of scope

- **Rule names** (`required`, `safe`, `file_exists`) are a closed
  vocabulary used as strings in rule arrays, not calls. Their
  consistency inside the ruleset matters, not this guide.
- **PHP mirrors** (`file_exists`, `dir_exists`, `random_string`) keep
  the native names for least surprise, even where this guide would pick
  another form. `random_string` mirrors the native `random_bytes` and
  `random_int` family.
- **Adapter vocabulary** (`api_errors`, `cli_errors`,
  `maintenance_response`) is a fixed convention of the api and cli
  entry points.
- **Handler callbacks** referenced as strings (`error_handler` in
  `api.php`) keep their role names, static analysis cannot verify
  string callables.
- **No abbreviations** in new names. The `_rec` affix above is
  grandfathered, not a template.
- **No namespace stutter**: inside `jaco\domain\page` it is
  `find_pages`, never `page_find_pages`.
