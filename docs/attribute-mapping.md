# Data attribute naming

> **Status: agreed.** The naming below is settled; the implementation is deferred
> on purpose. The code, the pages and the tests still use the names in
> [What exists today](#what-exists-today), with one exception: the template
> marker already carries its file name (`data-jaco-template="footer.html"`), in the
> pages, the site templates, `template_with_marker()` and the tests. Only its
> whitelist entry is still missing.

The `data-jaco-*` attributes are the contract between a page and the editor. They
are written by hand into HTML that a customer already owns, so every name here is
public API, not an internal detail.

## What exists today

Sixteen attributes, three naming schemes, one namespace.

| Attribute | Kind | Read by | Survives saving |
| --- | --- | --- | --- |
| `data-jaco-editable` | boolean | editor | yes |
| `data-jaco-editable-line` | boolean | editor | yes |
| `data-jaco-editable-link` | boolean | editor | yes |
| `data-jaco-editable-image` | boolean | editor | yes |
| `data-jaco-editable-image-extended` | boolean | editor | yes |
| `data-jaco-editable-image-gallery` | boolean | editor | yes |
| `data-jaco-content-list` | boolean | `frontend/contentlists.js` | yes |
| `data-jaco-content-item` | boolean | content list | yes |
| `data-jaco-fragment` | value | content list | yes |
| `data-jaco-title` | value | content list | yes |
| `data-jaco-pos` | value | content list | yes |
| `data-jaco-template` | value (file name, with `.html`) | `backend/core/html.php` | **no** |
| `data-jaco-editable-image-file` | value, runtime | editor | no |
| `data-jaco-gallery-full` | value, runtime | editor | no |
| `data-jaco-is-editing` | boolean, runtime | editor | no |
| `data-jaco-editable-image-temp` | boolean, runtime | editor | no |

The whitelist in `backend/core/html.php` (`ALLOWED_ATTRIBUTES`) decides what
survives a save; everything else is stripped silently.

### Problems

- **Three prefixes for one idea.** `editable`, `content-list`, and bare names
  (`fragment`, `title`, `pos`) sit next to each other.
- **A key does not say what it configures.** `data-jaco-fragment` belongs to a
  content list, but the name does not show it.
- **Runtime state shares the namespace with content.** A forgotten whitelist
  entry writes editor state into the page.
- **`data-jaco-template` is missing from the whitelist**, so a page loses its
  template binding when it is saved (see [Removed bug class](#removed-bug-class)).

## The design

One rule, and it is a rule about **roles**, not about values:

```
data-jaco-<role>[="value"]               a role: the editor acts on the element
data-jaco-<role>--<property>[="value"]   a property: it configures that role

A role name may contain single hyphens (repeater-item).
The double hyphen separates the property from its role.
Whether the value is absent (boolean), optional, required
or forbidden is a property of the name, not of the grammar.
```

A name that is present is on. A name that carries a value is configured. The
two are independent axes: a role can carry a value (`data-jaco-template="footer.html"`
— the role is "template instance", the value is its name), and a property can be
boolean (`data-jaco-image--resizable`).

> **Why this replaced "no value grammar".** An earlier revision split the
> namespace into "markers carry no value, keys carry a value". That split was a
> rule about syntax, not about meaning, and it forced every role that is
> parameterised by identity (`template`) into a two-attribute form whose marker
> was meaningless on its own. The current rule puts the boundary where it belongs:
> between roles and properties.

### Consequences

Roles and properties are told apart **by the syntax**: a name with `--` is a
property, and everything before the `--` is its role. A single hyphen can
therefore stay inside a role name without making the grammar ambiguous.
`repeater--item-label` is a property of `repeater`, `repeater-item--label` a
property of `repeater-item`; with a single hyphen as separator the two could not
be told apart without the list.

The pairing is mechanical: a property is valid only when the element carries the
role in front of the `--`. The **closed list of names** is still what decides
which roles and properties exist and which values they take. The server validates
against it; an unknown name is an error (see
[What the server validates](#what-the-server-validates)).

The separator is a double hyphen because the editor never reads these attributes
through `dataset` (it uses `getAttribute`, `hasAttribute` and selectors), so the
awkward camel-case key `jacoImage-Resizable` does not matter.

### Roles

A role is what the editor does to the element. Roles may be parameterised by
identity; the table says whether a value is optional, required or forbidden.

| Role | Value | Meaning |
| --- | --- | --- |
| `data-jaco-block` | forbidden | rich text block |
| `data-jaco-line` | forbidden | single line of text |
| `data-jaco-link` | forbidden | link, edited in a dialog |
| `data-jaco-image` | forbidden | image, replace |
| `data-jaco-repeater` | forbidden | container of repeatable blocks |
| `data-jaco-repeater-item` | forbidden | one block inside such a container |
| `data-jaco-template` | **required** | site template instance; the value is the template file name |

At most **one role per element**. Two roles on one element - `data-jaco-block`
with `data-jaco-line`, `data-jaco-image` with `data-jaco-link` - are an error, not
a combination. Properties belong to the role on the same element; an element
without a role can carry none.

### Properties

A property configures a role. Its name is `<role>--<property>`, and it is only
valid on an element that carries that role (see
[What the server validates](#what-the-server-validates)).

| Property | On | Value | Meaning |
| --- | --- | --- | --- |
| `data-jaco-image--resizable` | `data-jaco-image` | forbidden | width and height editable |
| `data-jaco-image--lightbox` | `data-jaco-image` | forbidden | write `name-full.ext` and link the thumbnail to it |
| `data-jaco-repeater--fragment` | `data-jaco-repeater` | required | file in `jaco-data/templates/content/` that provides a new block |
| `data-jaco-repeater--label` | `data-jaco-repeater` | optional | label of the add button |
| `data-jaco-repeater--add` | `data-jaco-repeater` | optional, closed set | where the add button goes: `top` (default), `bottom`, `any` |
| `data-jaco-repeater--sortable` | `data-jaco-repeater` | forbidden | blocks can be reordered by the grip |

### Editor state

The editor keeps its own state **out of the DOM**. It sets no `data-jaco-*`
attribute while it runs, so the page the editor saves is the page the author wrote,
and no name exists that an author could copy by mistake.

| State | Today | Held in |
| --- | --- | --- |
| a block or line is being edited | `data-jaco-is-editing` | a CSS class set by the editor, or a `WeakSet` |
| file name an upload is stored under | `data-jaco-editable-image-file` | `WeakMap` from the `<img>` to the name |
| full-size data URL for the lightbox | `data-jaco-gallery-full` | `WeakMap` from the `<img>` to the data URL |
| the size badge | `data-jaco-editable-image-temp` | a reference to the inserted element |

The base64 image data therefore no longer sits in a DOM attribute. Because no
runtime name is in the contract, an old one that turns up in a page is an
**unknown name** like any other and is rejected on save.

## Elements

```html
<div data-jaco-block>…</div>
<h2 data-jaco-line>…</h2>
<a href="contact.html" data-jaco-link>Contact</a>

<img data-jaco-image src="media/team.jpg" width="800">
<img data-jaco-image data-jaco-image--resizable src="media/blog.jpg" width="800">
<img data-jaco-image data-jaco-image--lightbox src="media/cake.jpg" width="400">
<img data-jaco-image data-jaco-image--resizable data-jaco-image--lightbox src="media/cake.jpg" width="400">

<section data-jaco-repeater
         data-jaco-repeater--fragment="feature_en.html"
         data-jaco-repeater--label="Add feature"
         data-jaco-repeater--add="top" data-jaco-repeater--sortable>
  <h2 data-jaco-line>Why Jaco CMS?</h2>
  <div data-jaco-repeater-item>
    <h3 data-jaco-line>Edit in place</h3>
    <div data-jaco-block><p>…</p></div>
  </div>
</section>

<footer data-jaco-template="footer.html">…</footer>
```

## Image

| Form | Meaning |
| --- | --- |
| `data-jaco-image` | replace the file, keep `width` and `height` |
| `+ data-jaco-image--resizable` | width and height editable |
| `+ data-jaco-image--lightbox` | full version written, thumbnail linked |
| both properties | all of it |

A lightbox image after saving:

```html
<a href="media/cake-full.jpg">
  <img data-jaco-image data-jaco-image--lightbox src="media/cake.jpg" width="400">
</a>
```

An existing `<a>` around the image wins: no `-full.ext` is written and nothing is
wrapped.

## Repeater

Where the add button goes and whether blocks can be sorted are **independent**
properties. Neither implies the other.

| `data-jaco-repeater--add` | Behaviour |
| --- | --- |
| `top` (default) | add button before the first block |
| `bottom` | add button after the last block |
| `any` | add button before every block and at the end |

`data-jaco-repeater--sortable` adds the grip and lets blocks be moved; without it
they stay where they are. It does **not** change the add position — a sortable
repeater with no `--add` places its button like `top`, because `top` is the
default, not because sorting implies it. To place the button elsewhere, write
`--add` explicitly. The rule holds for every add position, `any` included: button
before each block, grip on each block.

Today one value mixes both: `data-jaco-pos="draggable"` means "button on top and
blocks sortable". The merge also makes the fallback for an unknown value `any`;
with the split the default is `top`, and a repeater that wants a button before
every block says `any` explicitly. The split removes the hidden coupling: the
old value had two meanings that happened to coincide, the new attributes have
one meaning each.

Both name a file, both carry the extension: `data-jaco-repeater--fragment="feature_en.html"`
and `data-jaco-template="footer.html"`. The directories hold `.html` files, both
APIs take a file name (`template/content/view?file=…`,
`template/site/preview` with `fileName`), and the template list in the admin shows
file names. Only the marker used to drop the extension; with the rename it follows.

Both are checked on save, with different consequences. A fragment that does not
exist is an **error** - without it the repeater cannot add anything. A template
name that does not exist is a **warning** - the page still renders and only
"apply to all" finds nothing.

`data-jaco-repeater-item` marks a **direct child** as one block. An item nested
deeper is an error, not silently ignored. A heading inside the container is
ignored and gets no delete button.

## Template

```html
<footer data-jaco-template="footer.html">…</footer>
<footer data-jaco-template="footer-en.html">…</footer>
<footer data-jaco-template="footers/footer-en.html">…</footer>
```

Apply-to-all replaces every instance with that value across the project. The value
is the path of the template file **relative to `jaco-data/templates/site/`**
(`footer.html`, `footers/footer-en.html`), the same string the admin lists and the
preview endpoint takes. Templates may sit in subdirectories, so the value is not a
single token. On save the value is checked against `jaco-data/templates/site/`: an
unknown value produces a **warning**, not an error, because a page may reference a
template before its file is uploaded. "Apply to all" then simply finds nothing.

### Syntax of the value

```text
value   = segment *( "/" segment ) ".html"
segment = 1*( ALPHA / DIGIT / "-" / "_" )
```

As a regular expression: `^[A-Za-z0-9_-]+(/[A-Za-z0-9_-]+)*\.html$`.

- The separator is `/`, on every platform. A backslash is not a separator and is
  not allowed.
- A segment has letters, digits, `-` and `_`. No dots, so `..` and hidden files
  cannot occur; the one dot is the one in `.html`.
- No leading or trailing `/`, no empty segment (`a//b.html`), no spaces, no
  umlauts, no other characters. Nothing is escaped: a name that needs escaping is
  not a valid name.
- The value is compared as written, case-sensitively. `Footer.html` and
  `footer.html` are two templates, even where the file system would treat them as
  one.
- There is no depth limit beyond the file system's.

The value is **validated, not sanitized**. Today `validate_site_template_file()`
normalizes through `to_filename` (which silently drops `.` and `..` segments) and
then rejects every value that contains a directory (`basename($file) !== $file`).
Both change: apply-to-all matches strings, so a value that is normalized into
another one would bind a page to a template nobody wrote. A value that does not
match the pattern is an error on save, and the API refuses it the same way. The
rule is one function that the marker check, the template API and apply-to-all all
use. `find_site_templates()` already walks the directory recursively; the list
shows the relative path, and `site_template_name()` strips the directory together
with the extension where a label is needed.

The fragment (`data-jaco-repeater--fragment`) names a file in
`jaco-data/templates/content/`, which stays flat. If it ever gets subdirectories,
it takes the same value syntax.

`site_template_name()` keeps stripping directory and extension where a label is
needed - the template list, the preview title - but not in the marker. The marker carries what
the file is called.

The value is **required**: a template without a name is not a template, because
"apply-to-all" has no group to apply to. This is why `data-jaco-template` is the
only role that carries a value — the name is the identity of the element, not a
configuration of it. The earlier two-attribute form
(`data-jaco-template data-jaco-template-name="…"`) was a workaround for the
old "roles carry no value" rule; with that rule gone, it has no reason to exist.

## Length

| Element | Today | Proposal | Characters |
| --- | --- | --- | --- |
| Block | `data-jaco-editable` | `data-jaco-block` | 18 → 15 |
| Single line | `data-jaco-editable-line` | `data-jaco-line` | 23 → 14 |
| Link | `data-jaco-editable-link` | `data-jaco-link` | 23 → 14 |
| Image | `data-jaco-editable-image` | `data-jaco-image` | 24 → 15 |
| Image, resizable | `data-jaco-editable-image-extended` | `data-jaco-image data-jaco-image--resizable` | 33 → 42 |
| Image, lightbox | `data-jaco-editable-image-gallery` | `data-jaco-image data-jaco-image--lightbox` | 32 → 41 |
| Image, both | two attributes | three attributes | 44 → 68 |
| Repeater | `data-jaco-content-list` | `data-jaco-repeater` | 22 → 18 |
| Repeater item | `data-jaco-content-item` | `data-jaco-repeater-item` | 22 → 23 |
| Fragment | `data-jaco-fragment="…"` | `data-jaco-repeater--fragment="…"` | 36 → 46 |
| Button label | `data-jaco-title="…"` | `data-jaco-repeater--label="…"` | 29 → 39 |
| Add position | `data-jaco-pos="…"` | `data-jaco-repeater--add="…"` | 25 → 32 |
| Sortable | – | `data-jaco-repeater--sortable` | – → 28 |
| Template | `data-jaco-template="footer"` | `data-jaco-template="footer.html"` | 27 → 32, already done |

Counted in the pages: 75 plain images, 31 resizable, 2 lightbox, 0 with both. The
common case gets nine characters shorter; the expensive cases are rare. The
"image, both" row is the only one that grows by more than a few characters
(44 → 68); it occurs on zero pages today, so the cost is theoretical.

A repeater with fragment and label, using the default add position:

```text
today    112 characters (four attributes, the position is always written)
proposal 103 characters (two attributes)
```

With a non-default position it grows to 135, with sorting to 163. The common case
gets shorter; `repeater--` costs eleven characters in every property it prefixes.

The migration writes the add position out, so a migrated repeater is 135
characters, not 103: the short form is what a page looks like once the explicit
`top` is dropped. Both numbers describe the same element.

The template row grows by five characters because the extension moves in:
`data-jaco-template="footer"` (27) becomes `data-jaco-template="footer.html"` (32).
On 100 occurrences in the pages that is 500 characters - the price of one rule
instead of an exception.

## What the server validates

| Rule | Message |
| --- | --- |
| `data-jaco-<role>--<property>` only on an element that carries `data-jaco-<role>` | property without its role (one rule for every property) |
| `data-jaco-repeater-item` only as a direct child of a repeater | item outside a repeater, or nested deeper |
| at most one role per element | two roles on one element |
| `data-jaco-repeater` and `data-jaco-repeater-item` never on one element | conflicting roles |
| `data-jaco-repeater--fragment` required on a repeater | repeater without a fragment |
| `data-jaco-repeater--fragment` names a file in `jaco-data/templates/content/` | error: unknown fragment |
| `data-jaco-repeater--add` one of three values | unknown add position |
| `data-jaco-repeater--sortable` carries no value | unexpected value |
| `data-jaco-template` without a value | template without a name |
| `data-jaco-template` value matches `^[A-Za-z0-9_-]+(/[A-Za-z0-9_-]+)*\.html$` | invalid template value: no extension, a `..` or backslash, an empty or leading segment, or another character |
| `data-jaco-template` value matches a file in `jaco-data/templates/site/` | warning: unknown template, the page is still saved |
| `data-jaco-block`, `-line`, `-link`, `-image`, `-repeater`, `-repeater-item` carry no value | unexpected value on a role |
| unknown `data-jaco-*` name | unknown name |

Errors are reported before saving, not silently dropped.

> **Value rules are per name, not per grammar.** Each row above is a property of
> the name in the closed list. The grammar itself does not know which names take
> values; the validator does.

## Where the contract lives

1. `jaco/data-attributes.json` – the one list of names (new), read by the whitelist in `backend/core/html.php`, by the frontend and by the tests.
2. `frontend/` – selectors in `editor.js` and `contentlists.js`, and the attribute list `dataAttrs` in `lib/iframe.js`.
3. Pages and templates – `*.html` in the root, `de/`, `jaco-data/templates/`.
4. `docs/` – this file.

A test that reads the names from the contract file and checks them against the
frontend selectors and the pages would catch a rename that is only half done. The
repository already has this pattern: `IgnoreRulesTest`, `MenuRoleTest`,
`PaletteRoleTest`. The page check parses HTML; that is more work than the
constant-to-constant checks in `IgnoreRulesTest`, and is the reason the contract
test is its own step.

### One source for the names

The names stood in four places that had to agree: the whitelist in `html.php`, the
selectors in `editor.js` and `contentlists.js`, the copy in `lib/iframe.js`, and the
pages. The contract file is the one place that lists them; everything else reads
it or is checked against it. A rename is one edit in the file plus the pages.

```json
{
  "roles": {
    "block": {"value": "forbidden"},
    "template": {"value": "required"},
    "repeater-item": {"value": "forbidden"}
  },
  "properties": {
    "image--resizable": {"value": "forbidden"},
    "repeater--add": {"value": "optional", "values": ["top", "bottom", "any"]}
  }
}
```

The sketch shows the shape, not every entry. The role of a property is the part
before the `--`, so the file does not repeat it. How the frontend receives the file
(a generated constant or a request with the other editor data) is decided when it is
built; the contract test checks whichever it is.

The whitelist is derived from it and holds thirteen names:

```php
'data-jaco-block', 'data-jaco-line', 'data-jaco-link', 'data-jaco-image',
'data-jaco-repeater', 'data-jaco-repeater-item', 'data-jaco-template',
'data-jaco-image--resizable', 'data-jaco-image--lightbox',
'data-jaco-repeater--fragment', 'data-jaco-repeater--label',
'data-jaco-repeater--add', 'data-jaco-repeater--sortable',
```

> `data-jaco-template-name` is gone: the name is the value of
> `data-jaco-template`. Thirteen names, one rule. The file holds nothing the editor
> sets at runtime.

## Migration

Renaming is a **breaking change**, and that costs nothing yet: the product is not
released, so no customer page carries the old names. The only pages that do are
the demo site and the templates in this repository, and they are in git.

**Decided: hard break.** No alias period, no migration path for customer pages.
The old names disappear with the rename and every page in this repository is
rewritten. An alias period would double every selector and every test for a
compatibility nobody needs. The names are cheapest to change now; once the
product is released, this decision has to be made again.

The default add position is `top`. All six repeaters in the pages carry an explicit
`pos` today (one `any`, one `bottom`, one `top`, three `draggable`), so no page
relies on the old fallback and the migration does not change any behaviour. It
writes the position as it stands, and splits `pos="draggable"` into `add="top"`
plus `sortable`.

### Mapping

The rename is mechanical per attribute, but three cases carry a semantic change
and need a human decision:

| Old | New | Notes |
| --- | --- | --- |
| `data-jaco-editable` | `data-jaco-block` | mechanical |
| `data-jaco-editable-line` | `data-jaco-line` | mechanical |
| `data-jaco-editable-link` | `data-jaco-link` | mechanical |
| `data-jaco-editable-image` | `data-jaco-image` | mechanical |
| `data-jaco-editable-image-extended` | `data-jaco-image data-jaco-image--resizable` | one attribute becomes two |
| `data-jaco-editable-image-gallery` | `data-jaco-image data-jaco-image--lightbox` | one attribute becomes two |
| `data-jaco-content-list` | `data-jaco-repeater` | mechanical |
| `data-jaco-content-item` | `data-jaco-repeater-item` | mechanical |
| `data-jaco-fragment="…"` | `data-jaco-repeater--fragment="…"` | mechanical |
| `data-jaco-title="…"` | `data-jaco-repeater--label="…"` | mechanical |
| `data-jaco-pos="draggable"` | `data-jaco-repeater--add="top" data-jaco-repeater--sortable` | **split**: one value carried two meanings |
| `data-jaco-pos="top"` | `data-jaco-repeater--add="top"` | default is now `top`, but write it out |
| `data-jaco-pos="bottom"` | `data-jaco-repeater--add="bottom"` | mechanical |
| `data-jaco-pos="any"` | `data-jaco-repeater--add="any"` | mechanical |
| *(no `pos`)* | *(no attribute)* | none exists in the repository; the new default `top` applies, which is the one deliberate behaviour change |
| `data-jaco-template="footer-en"` | `data-jaco-template="footer-en.html"` | **done**: extension added in pages, templates and code; the whitelist entry is still open |
| `data-jaco-editable-image-file` | – | runtime; leaves the DOM, see [Editor state](#editor-state) |
| `data-jaco-gallery-full` | – | runtime; leaves the DOM, see [Editor state](#editor-state) |
| `data-jaco-is-editing` | – | runtime; leaves the DOM, see [Editor state](#editor-state) |
| `data-jaco-editable-image-temp` | – | runtime; leaves the DOM, see [Editor state](#editor-state) |

The three **semantic** cases, plus one mechanical change:

1. **`pos="draggable"` → `add="top"` + `sortable`.** The old value meant both
   "button on top" and "blocks sortable". Both must be written out.
2. **No `pos` → `add="any"`.** The old fallback for a missing or unknown value was
   `any`; the new default is `top`. Writing `any` keeps the behaviour, and a
   repeater that wants the new default can drop the attribute afterwards.
3. **Images with both capabilities** (`-extended` + `-gallery`) become three
   attributes. Today zero pages have both, so the case is theoretical — but the
   rewrite script must handle it.

A fourth change touched every template marker: the value gained its `.html`
extension. It is **already done** - the pages and site templates carry
`footer-en.html`, and `template_with_marker()` in `backend/core/html.php` takes the
file name instead of `site_template_name($file)`. The rewrite script has nothing
left to do for the template marker.

### Order

Pages and templates are rewritten by a script, not by hand, because 22 pages and
12 templates are too many for a manual pass that must be consistent. The script
is a one-off; it is not checked in. Its output is verified by the contract test
(step 1) and by the Playwright suite.

### Decisions taken

| Question | Decision |
| --- | --- |
| Alias period or hard break | hard break |
| Default add position | `top` (today an unknown value falls back to `any`); no page relies on the old fallback |
| Inline `style` for editable values | not supported, see [Block configuration](#block-configuration) |
| Template marker | `data-jaco-template="footer.html"` — the role carries its file name as value |
| List of repeatable blocks | called `repeater`, because `list` reads like `<ul>` |
| Roles vs. properties | roles and properties are the same kind of name; value rules are per name |
| `sortable` and `add` | independent; `sortable` does not imply an add position |
| Unknown template name | warning, not an error: the page is saved and apply-to-all finds nothing |
| Unknown fragment file | error: a repeater without its fragment can add nothing |
| Roles per element | at most one; properties need their role on the same element |
| Template value | with `.html`, like the fragment, not the label; a path relative to `jaco-data/templates/site/`, `/` as separator, segments of letters, digits, `-` and `_`, ending in `.html`; validated, not sanitized |
| Block properties | `block--align`, `block--tone`, `block--size` with closed value sets; added to the contract with the editor feature |
| `sortable` with `add="any"` | allowed: button before every block, grip on every block |

## Removed bug class

`data-jaco-template` is missing from `ALLOWED_ATTRIBUTES`, so it is stripped on
every save:

```php
sanitize_html('<footer data-jaco-template="footer.html">x</footer>')
// '<footer>x</footer>'
```

A page that is edited once loses its template binding. The rename touches the
template attribute anyway, so the whitelist entry comes with it. The attribute
is one attribute with a required value, and the value gains the extension. The
bug is in the whitelist, not in the name, so the rename fixes the whitelist and
leaves the marker's mechanism alone.

## Appendix: every form

### Content

```html
<div data-jaco-block>
  <p>A paragraph with <strong>formatting</strong> and a <a href="…">link</a>.</p>
</div>

<h1 data-jaco-line>Welcome</h1>
<h2 data-jaco-line>Why Jaco CMS?</h2>

<a href="contact.html" data-jaco-link>Contact</a>
<a href="https://example.com" target="_blank" data-jaco-link>External</a>
<a href="mailto:info@example.com" data-jaco-link>Mail</a>
```

### Images

```html
<img data-jaco-image src="media/team.jpg" alt="Team" width="800" height="600">
<img data-jaco-image data-jaco-image--resizable src="media/blog.jpg" alt="Post" width="800">
<img data-jaco-image data-jaco-image--lightbox src="media/cake.jpg" alt="Cake" width="400">
<img data-jaco-image data-jaco-image--resizable data-jaco-image--lightbox
     src="media/cake.jpg" alt="Cake" width="400">
```

After saving a lightbox image:

```html
<a href="media/cake-full.jpg">
  <img data-jaco-image data-jaco-image--lightbox src="media/cake.jpg" alt="Cake" width="400">
</a>
```

An image with its own link wins: no `-full` file.

```html
<a href="shop.html">
  <img data-jaco-image data-jaco-image--lightbox src="media/cake.jpg" width="400">
</a>
```

Link and image side by side, two edits:

```html
<a href="shop.html" data-jaco-link>
  <img data-jaco-image src="media/cake.jpg" width="400">
</a>
```

### Repeater

Every add position, including the default:

```html
<section data-jaco-repeater data-jaco-repeater--fragment="feature_en.html">…</section>
<section data-jaco-repeater data-jaco-repeater--fragment="feature_en.html" data-jaco-repeater--add="top">…</section>
<section data-jaco-repeater data-jaco-repeater--fragment="feature_en.html" data-jaco-repeater--add="bottom">…</section>
<section data-jaco-repeater data-jaco-repeater--fragment="feature_en.html" data-jaco-repeater--add="any">…</section>
<section data-jaco-repeater data-jaco-repeater--fragment="feature_en.html"
         data-jaco-repeater--add="top" data-jaco-repeater--sortable>…</section>
```

With and without a label:

```html
<section data-jaco-repeater
         data-jaco-repeater--fragment="news_item.html"
         data-jaco-repeater--add="top" data-jaco-repeater--sortable
         data-jaco-repeater--label="Add news">…</section>

<section data-jaco-repeater
         data-jaco-repeater--fragment="news_item.html">…</section>
```

Without `data-jaco-repeater--label` the add button carries no text. Deriving one
from the fragment name is listed under [Out of scope](#out-of-scope).

A complete repeater. The heading is not an item: no delete button, not moved when
blocks are sorted.

```html
<section data-jaco-repeater
         data-jaco-repeater--fragment="feature_en.html"
         data-jaco-repeater--label="Add feature"
         data-jaco-repeater--add="top" data-jaco-repeater--sortable>
  <h2 data-jaco-line>Why Jaco CMS?</h2>

  <div data-jaco-repeater-item>
    <h3 data-jaco-line>Edit in place</h3>
    <div data-jaco-block><p>Change text directly on the page.</p></div>
  </div>

  <div data-jaco-repeater-item>
    <h3 data-jaco-line>No database</h3>
    <div data-jaco-block><p>Plain HTML files, nothing else.</p></div>
  </div>
</section>
```

Two repeaters on one page, the second inside a wrapper:

```html
<section data-jaco-repeater data-jaco-repeater--fragment="news_item.html"
         data-jaco-repeater--label="Add news">…</section>

<div class="team">
  <section data-jaco-repeater data-jaco-repeater--fragment="member.html"
           data-jaco-repeater--label="Add member"
           data-jaco-repeater--add="top" data-jaco-repeater--sortable>…</section>
</div>
```

### Template

```html
<footer data-jaco-template="footer.html">…</footer>

<footer data-jaco-template="footer-en.html">…</footer>
<footer data-jaco-template="footer-de.html">…</footer>

<!-- several instances of the same name on one page -->
<footer data-jaco-template="footer.html">…</footer>
<footer data-jaco-template="footer.html">…</footer>
```

### One image through the whole cycle

The editor adds no attribute while it works; only the saved result differs.

```html
<!-- in the file -->
<img data-jaco-image data-jaco-image--lightbox src="media/cake.jpg" width="400">

<!-- while uploading: the same element, the file name and the full-size data live in the editor -->
<img data-jaco-image data-jaco-image--lightbox src="media/cake.jpg" width="400">

<!-- saved -->
<a href="media/cake-full.jpg">
  <img data-jaco-image data-jaco-image--lightbox src="media/cake.jpg" width="400">
</a>
```

### Rejected forms

Each of these has to produce a message on save instead of being ignored.

| Form | Why it is rejected |
| --- | --- |
| `<img data-jaco-image--resizable>` | property without its role |
| `<section data-jaco-repeater data-jaco-repeater--add="top">` | repeater without a fragment |
| `<section data-jaco-repeater data-jaco-repeater--add="middle" …>` | unknown add position |
| `<div data-jaco-repeater-item>` outside a repeater | item outside its container |
| `<section data-jaco-repeater data-jaco-repeater-item>` | both roles on one element |
| `<div data-jaco-repeater-item data-jaco-repeater--fragment="x.html">` | property on an item |
| `<footer data-jaco-template>` | role without its required value |
| `<footer data-jaco-template="footer">` | value without the extension |
| `<div data-jaco-block="x">` | value on a role that takes none |
| `<div data-jaco-block data-jaco-line>` | two roles on one element |
| `<section data-jaco-repeater data-jaco-repeater--fragment="nosuch.html">` | fragment file does not exist |
| `<div><div data-jaco-repeater-item>` inside a repeater | item is not a direct child |
| `<div data-jaco-transient-active>` | a runtime name from the old code is unknown |

## Block configuration

The rule carries capabilities and closed vocabularies. It does **not** carry free
values, and the four obvious candidates for a block show why.

```text
public pages load only theme.js and nav.js; neither reads data-jaco attributes.
The editor runs inside the admin iframe. A value in a data attribute therefore
has to work through CSS, and CSS cannot read a number out of an attribute.
```

| Candidate | Works as | Why |
| --- | --- | --- |
| Alignment | `data-jaco-block--align="left\|center\|right"` | closed vocabulary, CSS can select it |
| Background | `data-jaco-block--tone="light\|dark\|brand"` | tones from the design, not arbitrary colours |
| Height and width | `data-jaco-block--size="small\|medium\|large"` | steps, CSS-selectable |
| Exact pixels | – | needs inline `style`, which the sanitizer strips |

```css
[data-jaco-block--align="center"] { text-align: center }
[data-jaco-block--tone="dark"]    { background: #1a1a1a; color: #f5f5f5 }
[data-jaco-block--size="large"]   { padding: 4rem 2rem }
```

`style` is stripped today, on purpose:

```php
sanitize_html('<div data-jaco-block style="width:800px">x</div>')
// '<div data-jaco-block>x</div>'
```

Allowing it would mean a CSS sanitizer of its own - `expression()`, `url()`,
`position: fixed`, `z-index` - and that is a separate project, not part of this
rename. **Decided: no `style` support.** Free values are not offered for blocks;
images keep using the `width` and `height` attributes they already have.

**Decided: the three block properties are agreed** as shown in the table:
`data-jaco-block--align`, `--tone` and `--size`, each with the closed set of values
listed there. They are not among the thirteen names yet; they join the contract
file when the editor offers them, and no earlier. Further block settings follow
the same shape: a property on the role with a closed set of values. The fact that a role can carry
a value (`data-jaco-template="footer.html"`) does not open the door to free values: the
value rule is per name, and a new name is added to the closed list before it is
used.

## Implementation order

Nothing below is done. The naming is agreed, the code still uses the old names.
When the work starts, this is the order; step 1 is written first and is supposed
to fail until the rest agrees.

| # | Step | Where |
| --- | --- | --- |
| 1 | Contract file and contract test: read the thirteen names from the file, compare them with the whitelist, the frontend selectors and every page | `jaco/data-attributes.json`, new test next to `IgnoreRulesTest` |
| 2 | Whitelist derived from the file, validation rules, template marker fixed | `backend/core/html.php` |
| 3 | Selectors read the names; editor state leaves the DOM | `frontend/editor.js`, `frontend/contentlists.js`, `frontend/lib/iframe.js` |
| 4 | Pages (the template marker is already done) | 14 files in the root and `de/` |
| 5 | Templates | 12 files under `jaco-data/templates/` |
| 6 | Suites | PHPUnit, QUnit, Playwright |

**The contract test runs after every step and must be green before the next step
starts.** With a hard break, the intermediate states are inconsistent by design
(old names in code, new names in the whitelist, or vice versa); the test is what
keeps the inconsistency bounded to one step. A step that leaves the test red is a
step that is not finished.

**Step 1** writes the contract file and checks four places against each other (file, whitelist, frontend, pages), the same way `IgnoreRulesTest`
guards the ignore rules and `MenuRoleTest` the menu. Without it a rename that
missed one selector looks green. The page check parses HTML, which is more work
than the constant-to-constant checks; this is why it is a step of its own.

**Step 2** derives the whitelist from the contract file, so it holds exactly its
thirteen names, and fixes the template marker from `docs/backlog.md`: `data-jaco-template`
is missing from the whitelist today and is stripped on every save, which the file
makes impossible. The pairing rules
from [What the server validates](#what-the-server-validates) go in the same place:
a property without its role, an item outside a repeater, a repeater without a
fragment, an unknown add position, a value on a role that takes none, a missing
value on a role that requires one, an unknown name.

**Step 3** replaces every selector, and the list of attribute names in
`frontend/lib/iframe.js` (`dataAttrs`), which is a copy of the contract outside the
whitelist. The editor also stops writing `data-jaco-is-editing`, `-image-file`,
`-gallery-full` and `-image-temp` and keeps that state in JavaScript, see
[Editor state](#editor-state). With a hard break there is no old name to fall
back to, so a missed selector silently stops working - the contract test from step
1 is what catches it. One behaviour changes with the split: `makeSortable` in
`frontend/contentlists.js` runs today only when the position is `draggable`. It has
to run whenever `data-jaco-repeater--sortable` is present, for every add position,
otherwise `add="bottom"` with sorting silently stays fixed.

**Step 4** rewrites 14 pages. The add position becomes explicit (`top` was
implicit `any` before), `pos="draggable"` splits into `add="top"` plus `sortable`,
image capabilities become two properties, the template marker needs no change: it already
carries `.html` and has no second attribute.

**Step 5** touches 12 templates: six content fragments, two page templates, four
site templates. They carry the same attributes as the pages.

**Step 6** runs everything: PHPUnit (400 tests), QUnit (180), Playwright (35), and
`./build-dist.sh` plus `./verify-dist-archive.sh`.

### Why the contract test comes first

With a hard break, a page that still carries `data-jaco-editable` does not fail
loudly - the sanitizer strips the attribute and the page quietly stops being
editable. A test that compares the contract file, the whitelist, the frontend and the pages is the only thing that makes the
rename verifiable instead of hopeful. Running it after every step is the
difference between "the rename is done" and "the rename looks done".

## Out of scope

- The `-full.ext` convention behind the lightbox.
- Deriving the add-button label from the fragment name, which would make
  `data-jaco-repeater--label` optional in practice. It fixes the "labels carry the page
  language" item in `docs/backlog.md` and belongs in its own change.
- Deriving a template name when it is absent. The value is required because an
  anonymous template has no apply-to-all group; if that changes, it is a new
  decision, not a rename.

## Changelog

| Version | Date | Change |
| --- | --- | --- |
| 0.15.0 | 2026-10-26 | Default add position stays `top`; all six repeaters in the pages carry a `pos`, so the migration changes no behaviour. The template value is a path relative to `jaco-data/templates/site/` with a fixed syntax and is validated, not sanitized, so templates can live in subdirectories. The three block properties are agreed. The way the frontend reads the contract file stays open. |
| 0.14.0 | 2026-10-26 | Editor state leaves the DOM: no transient attributes, so the exception in the grammar, the `transient-*` rules and the base64 data attribute are gone. One contract file (`jaco/data-attributes.json`) lists the names; whitelist, frontend and tests read it. Steps 2 and 6 merge. |
| 0.13.0 | 2026-10-26 | Properties are separated from their role by a double hyphen: `data-jaco-image--resizable`, `data-jaco-repeater--fragment`. The grammar is no longer ambiguous, the pairing rule is one rule instead of one per role, and the "Consequences" section changes from a trade-off to a statement. Transient attributes keep their names and are the one documented exception; a surviving one is an error. `lib/iframe.js` joins the places that carry the contract. |
| 0.12.1 | 2026-10-26 | Template extension is implemented (commit `3072786`); the document marks it done and leaves only the whitelist entry open. |
| 0.12.0 | 2026-10-26 | Both file references carry the extension: `data-jaco-template="footer.html"`. The template label without extension stays a display concern in `site_template_name()`. |
| 0.11.0 | 2026-10-25 | Five details settled: at most one role per element, fragment takes the extension while the template name does not, `sortable` works with every add position, an item must be a direct child, a missing fragment is an error while a missing template is a warning. |
| 0.10.0 | 2026-10-25 | One rule about roles, not about values: roles may carry a required value (`data-jaco-template="…"`), so `-template-name` is gone. `sortable` no longer implies an add position. Transient attributes follow the same grammar; the `transient-` prefix is a recognisability aid, not a second scheme. Role/property ambiguity documented. Migration mapping and per-step contract gate added. Whitelist is thirteen names. Corrections after review: the template value rule was inverted, a missing `pos` maps to `any` to keep the old behaviour, an unknown template name is a warning, and `makeSortable` has to run for every add position. |
| 0.9.0 | 2026-10-25 | Four names fixed: `repeater-label` (was `title`), `repeater-add` plus `repeater-sortable` (was `repeater-position` with `draggable` inside), `transient-filename` (was `transient-target`), `transient-badge` (was `transient-temp`). |
| 0.8.0 | 2026-10-25 | Implementation order written down; step 1 is the contract test. |
| 0.7.0 | 2026-10-25 | Decisions recorded: hard break, default `top`, no `style` support, block settings only as closed vocabularies. |
| 0.6.0 | 2026-10-25 | `list` becomes `repeater`: the name no longer reads like `<ul>`/`<ol>`. |
| 0.5.0 | 2026-10-25 | One rule: marker plus keys, no value grammar. Image capabilities become boolean keys. Every marker gets a plain name; the template name becomes a key as well. |
| 0.4.0 | 2026-10-25 | Markers instead of a role value: `data-jaco-content-block`, `data-jaco-repeater`, `data-jaco-repeater-item`. Keys stay domain-prefixed. |
| 0.3.1 | 2026-10-25 | Appendix with every valid form and the rejected ones. |
| 0.3.0 | 2026-10-25 | List position becomes a flag in the value; three attributes instead of four. `key=value` reserved but disabled. |
| 0.2.0 | 2026-10-25 | One marker attribute with a type token, type-prefixed keys, `list-item` instead of `item`. |
| 0.1.0 | 2026-10-25 | Draft. Corrected against the code: the first revision described a migration that had not happened, mixed `transient` and `runtime`, and used two separators in one grammar. |
