# Data attribute naming

> **Status: agreed.** The naming below is settled; the implementation is deferred
> on purpose. The code, the pages and the tests still use the names in
> [What exists today](#what-exists-today).

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
| `data-jaco-template` | value | `backend/core/html.php` | **no** |
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
data-jaco-<name>[="value"]

Every name is a role (the editor acts on the element)
or a property of a role (it configures it).
Whether the value is absent (boolean), optional, required
or forbidden is a property of the name, not of the grammar.
```

A name that is present is on. A name that carries a value is configured. The
two are independent axes: a role can carry a value (`data-jaco-template="footer"`
— the role is "template instance", the value is its name), and a property can be
boolean (`data-jaco-image-resizable`).

> **Why this replaced "no value grammar".** An earlier revision split the
> namespace into "markers carry no value, keys carry a value". That split was a
> rule about syntax, not about meaning, and it forced every role that is
> parameterised by identity (`template`) into a two-attribute form whose marker
> was meaningless on its own. The current rule puts the boundary where it belongs:
> between roles and properties.

### Consequences

The grammar is **syntactically ambiguous**: `data-jaco-<word>-<word>` can be a
role with a compound name (`repeater-item`) or a property of a role
(`image-resizable`). The disambiguation is the **closed list of names** below,
not the syntax. This is a deliberate trade: an extensible namespace cannot be
parsed by shape alone. The server validates against the list; an unknown name is
an error (see [What the server validates](#what-the-server-validates)).

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
| `data-jaco-template` | **required** | site template instance; the value is the template name |

At most **one role per element**. Two roles on one element - `data-jaco-block`
with `data-jaco-line`, `data-jaco-image` with `data-jaco-link` - are an error, not
a combination. Properties belong to the role on the same element; an element
without a role can carry none.

### Properties

A property configures a role. It is only valid on its role (see
[What the server validates](#what-the-server-validates)).

| Property | On | Value | Meaning |
| --- | --- | --- | --- |
| `data-jaco-image-resizable` | `data-jaco-image` | forbidden | width and height editable |
| `data-jaco-image-lightbox` | `data-jaco-image` | forbidden | write `name-full.ext` and link the thumbnail to it |
| `data-jaco-repeater-fragment` | `data-jaco-repeater` | required | file in `jaco-data/templates/content/` that provides a new block |
| `data-jaco-repeater-label` | `data-jaco-repeater` | optional | label of the add button |
| `data-jaco-repeater-add` | `data-jaco-repeater` | optional, closed set | where the add button goes: `top` (default), `bottom`, `any` |
| `data-jaco-repeater-sortable` | `data-jaco-repeater` | forbidden | blocks can be reordered by the grip |

### Transient

Transient attributes are roles and properties like any other. They follow the same
grammar; the only difference is that their names are not in the whitelist, so a
forgotten entry cannot persist editor state. The `transient-` prefix makes them
recognisable in a saved page, which is the point: a transient attribute that
survives is a bug that announces itself.

| Role / property | On | Value | Meaning |
| --- | --- | --- | --- |
| `data-jaco-transient-active` | block, line | forbidden | editing is active |
| `data-jaco-transient-badge` | the size badge | forbidden | editor-inserted element, removed before saving |
| `data-jaco-transient-filename` | image | required | file name the upload is stored under |
| `data-jaco-transient-full` | image | required | full-size data URL for the lightbox |

## Elements

```html
<div data-jaco-block>…</div>
<h2 data-jaco-line>…</h2>
<a href="contact.html" data-jaco-link>Contact</a>

<img data-jaco-image src="media/team.jpg" width="800">
<img data-jaco-image data-jaco-image-resizable src="media/blog.jpg" width="800">
<img data-jaco-image data-jaco-image-lightbox src="media/cake.jpg" width="400">
<img data-jaco-image data-jaco-image-resizable data-jaco-image-lightbox src="media/cake.jpg" width="400">

<section data-jaco-repeater
         data-jaco-repeater-fragment="feature_en.html"
         data-jaco-repeater-label="Add feature"
         data-jaco-repeater-add="top" data-jaco-repeater-sortable>
  <h2 data-jaco-line>Why Jaco CMS?</h2>
  <div data-jaco-repeater-item>
    <h3 data-jaco-line>Edit in place</h3>
    <div data-jaco-block><p>…</p></div>
  </div>
</section>

<footer data-jaco-template="footer">…</footer>
```

## Image

| Form | Meaning |
| --- | --- |
| `data-jaco-image` | replace the file, keep `width` and `height` |
| `+ data-jaco-image-resizable` | width and height editable |
| `+ data-jaco-image-lightbox` | full version written, thumbnail linked |
| both properties | all of it |

A lightbox image after saving:

```html
<a href="media/cake-full.jpg">
  <img data-jaco-image data-jaco-image-lightbox src="media/cake.jpg" width="400">
</a>
```

An existing `<a>` around the image wins: no `-full.ext` is written and nothing is
wrapped.

## Repeater

Where the add button goes and whether blocks can be sorted are **independent**
properties. Neither implies the other.

| `data-jaco-repeater-add` | Behaviour |
| --- | --- |
| `top` (default) | add button before the first block |
| `bottom` | add button after the last block |
| `any` | add button before every block and at the end |

`data-jaco-repeater-sortable` adds the grip and lets blocks be moved; without it
they stay where they are. It does **not** change the add position — a sortable
repeater with no `-add` places its button like `top`, because `top` is the
default, not because sorting implies it. To place the button elsewhere, write
`-add` explicitly. The rule holds for every add position, `any` included: button
before each block, grip on each block.

Today one value mixes both: `data-jaco-pos="draggable"` means "button on top and
blocks sortable". The merge also makes the fallback for an unknown value `any`;
with the split the default is `top`, and a repeater that wants a button before
every block says `any` explicitly. The split removes the hidden coupling: the
old value had two meanings that happened to coincide, the new attributes have
one meaning each.

`data-jaco-repeater-fragment` takes the file **with** its extension
(`feature_en.html`); the template name in `data-jaco-template` is the identifier
**without** it (`footer`). The difference follows from what they name: a file to
fetch, and a group to apply to.

Both are checked on save, with different consequences. A fragment that does not
exist is an **error** - without it the repeater cannot add anything. A template
name that does not exist is a **warning** - the page still renders and only
"apply to all" finds nothing.

`data-jaco-repeater-item` marks a **direct child** as one block. An item nested
deeper is an error, not silently ignored. A heading inside the container is
ignored and gets no delete button.

## Template

```html
<footer data-jaco-template="footer">…</footer>
<footer data-jaco-template="footer-en">…</footer>
```

Apply-to-all replaces every instance with that name across the project. The name
is the file name without its extension; file names are sanitized, so the value is
a single token. On save the name is checked against `jaco-data/templates/site/`:
an unknown name produces a **warning**, not an error, because a page may reference a
template before its file is uploaded. "Apply to all" then simply finds nothing.

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
| Image, resizable | `data-jaco-editable-image-extended` | `data-jaco-image data-jaco-image-resizable` | 33 → 41 |
| Image, lightbox | `data-jaco-editable-image-gallery` | `data-jaco-image data-jaco-image-lightbox` | 32 → 40 |
| Image, both | two attributes | three attributes | 44 → 66 |
| Repeater | `data-jaco-content-list` | `data-jaco-repeater` | 22 → 18 |
| Repeater item | `data-jaco-content-item` | `data-jaco-repeater-item` | 22 → 23 |
| Fragment | `data-jaco-fragment="…"` | `data-jaco-repeater-fragment="…"` | 36 → 45 |
| Button label | `data-jaco-title="…"` | `data-jaco-repeater-label="…"` | 29 → 38 |
| Add position | `data-jaco-pos="…"` | `data-jaco-repeater-add="…"` | 25 → 31 |
| Sortable | – | `data-jaco-repeater-sortable` | – → 27 |
| Template | `data-jaco-template="footer"` | `data-jaco-template="footer"` | 27 → 27 |

Counted in the pages: 75 plain images, 31 resizable, 2 lightbox, 0 with both. The
common case gets nine characters shorter; the expensive cases are rare. The
"image, both" row is the only one that grows by more than a few characters
(44 → 66); it occurs on zero pages today, so the cost is theoretical.

A repeater with fragment and label, using the default add position:

```text
today    112 characters (four attributes, the position is always written)
proposal 101 characters (two attributes)
```

With a non-default position it grows to 132, with sorting to 159. The common case
gets shorter; `repeater` costs six characters in every name it prefixes.

The migration writes the add position out, so a migrated repeater is 132
characters, not 101: the short form is what a page looks like once the explicit
`top` is dropped. Both numbers describe the same element.

The template row is now neutral: the value moves from a second attribute onto
the role, so the two-attribute form (`data-jaco-template data-jaco-template-name="footer"`,
51 characters) is gone. The 27-character row is the whole cost.

## What the server validates

| Rule | Message |
| --- | --- |
| `data-jaco-image-*` only with `data-jaco-image` | property without its role |
| `data-jaco-repeater-*` only with `data-jaco-repeater` | property on an element that is no repeater |
| `data-jaco-repeater-item` only as a direct child of a repeater | item outside a repeater, or nested deeper |
| at most one role per element | two roles on one element |
| `data-jaco-repeater` and `data-jaco-repeater-item` never on one element | conflicting roles |
| `data-jaco-repeater-fragment` required on a repeater | repeater without a fragment |
| `data-jaco-repeater-fragment` names a file in `jaco-data/templates/content/` | error: unknown fragment |
| `data-jaco-repeater-add` one of three values | unknown add position |
| `data-jaco-repeater-sortable` carries no value | unexpected value |
| `data-jaco-template` without a value | template without a name |
| `data-jaco-template` value matches a file in `jaco-data/templates/site/` | warning: unknown template, the page is still saved |
| `data-jaco-block`, `-line`, `-link`, `-image`, `-repeater`, `-repeater-item` carry no value | unexpected value on a role |
| `data-jaco-transient-full` only with `data-jaco-image-lightbox` | full image without a lightbox |
| `data-jaco-transient-*` never saved | transient attribute survived |
| unknown `data-jaco-*` name | unknown name |

Errors are reported before saving, not silently dropped.

> **Value rules are per name, not per grammar.** Each row above is a property of
> the name in the closed list. The grammar itself does not know which names take
> values; the validator does.

## Where the contract lives

1. `backend/core/html.php` – the whitelist, decides what survives a save.
2. `frontend/` – selectors in `editor.js` and `contentlists.js`.
3. Pages and templates – `*.html` in the root, `de/`, `jaco-data/templates/`.
4. `docs/` – this file.

A test that reads the names from the whitelist and checks them against the
frontend selectors and the pages would catch a rename that is only half done. The
repository already has this pattern: `IgnoreRulesTest`, `MenuRoleTest`,
`PaletteRoleTest`. The page check parses HTML; that is more work than the
constant-to-constant checks in `IgnoreRulesTest`, and is the reason the contract
test is its own step.

The whitelist holds thirteen names and no transient attribute:

```php
'data-jaco-block', 'data-jaco-line', 'data-jaco-link', 'data-jaco-image',
'data-jaco-repeater', 'data-jaco-repeater-item', 'data-jaco-template',
'data-jaco-image-resizable', 'data-jaco-image-lightbox',
'data-jaco-repeater-fragment', 'data-jaco-repeater-label',
'data-jaco-repeater-add', 'data-jaco-repeater-sortable',
```

> `data-jaco-template-name` is gone: the name is the value of
> `data-jaco-template`. Thirteen names, one rule.

## Migration

Renaming is a **breaking change**:

- The demo site is in git; its 22 pages are mechanical.
- A customer's page is not in git. After a rename its `data-jaco-editable` is
  unknown, is stripped on the next save, and the page silently stops being
  editable.

**Decided: hard break.** No alias period. The old names disappear with the
rename, every page in this repository is rewritten, and a customer's page needs a
manual edit. An alias period would double every selector and every test for a
compatibility nobody here needs.

The migration also writes the add position explicitly, because the default
changes from `any` to `top`, and splits `pos="draggable"` into `add="top"` plus
`sortable`.

### Mapping

The rename is mechanical per attribute, but three cases carry a semantic change
and need a human decision:

| Old | New | Notes |
| --- | --- | --- |
| `data-jaco-editable` | `data-jaco-block` | mechanical |
| `data-jaco-editable-line` | `data-jaco-line` | mechanical |
| `data-jaco-editable-link` | `data-jaco-link` | mechanical |
| `data-jaco-editable-image` | `data-jaco-image` | mechanical |
| `data-jaco-editable-image-extended` | `data-jaco-image data-jaco-image-resizable` | one attribute becomes two |
| `data-jaco-editable-image-gallery` | `data-jaco-image data-jaco-image-lightbox` | one attribute becomes two |
| `data-jaco-content-list` | `data-jaco-repeater` | mechanical |
| `data-jaco-content-item` | `data-jaco-repeater-item` | mechanical |
| `data-jaco-fragment="…"` | `data-jaco-repeater-fragment="…"` | mechanical |
| `data-jaco-title="…"` | `data-jaco-repeater-label="…"` | mechanical |
| `data-jaco-pos="draggable"` | `data-jaco-repeater-add="top" data-jaco-repeater-sortable` | **split**: one value carried two meanings |
| `data-jaco-pos="top"` | `data-jaco-repeater-add="top"` | default is now `top`, but write it out |
| `data-jaco-pos="bottom"` | `data-jaco-repeater-add="bottom"` | mechanical |
| `data-jaco-pos="any"` | `data-jaco-repeater-add="any"` | mechanical |
| *(no `pos`)* | `data-jaco-repeater-add="any"` | **preserve** the old fallback; `top` would change behaviour |
| `data-jaco-template="x"` | `data-jaco-template="x"` | unchanged — the rename fixes the whitelist entry instead |
| `data-jaco-editable-image-file` | `data-jaco-transient-filename` | runtime; not in pages |
| `data-jaco-gallery-full` | `data-jaco-transient-full` | runtime; not in pages |
| `data-jaco-is-editing` | `data-jaco-transient-active` | runtime; not in pages |
| `data-jaco-editable-image-temp` | `data-jaco-transient-badge` | runtime; not in pages |

The three **semantic** cases:

1. **`pos="draggable"` → `add="top"` + `sortable`.** The old value meant both
   "button on top" and "blocks sortable". Both must be written out.
2. **No `pos` → `add="any"`.** The old fallback for a missing or unknown value was
   `any`; the new default is `top`. Writing `any` keeps the behaviour, and a
   repeater that wants the new default can drop the attribute afterwards.
3. **Images with both capabilities** (`-extended` + `-gallery`) become three
   attributes. Today zero pages have both, so the case is theoretical — but the
   rewrite script must handle it.

### Order

Pages and templates are rewritten by a script, not by hand, because 22 pages and
12 templates are too many for a manual pass that must be consistent. The script
is a one-off; it is not checked in. Its output is verified by the contract test
(step 1) and by the Playwright suite.

### Decisions taken

| Question | Decision |
| --- | --- |
| Alias period or hard break | hard break |
| Default add position | `top` (today an unknown value falls back to `any`) |
| Inline `style` for editable values | not supported, see [Block configuration](#block-configuration) |
| Template marker | `data-jaco-template="…"` — the role carries its identity as value |
| List of repeatable blocks | called `repeater`, because `list` reads like `<ul>` |
| Roles vs. properties | roles and properties are the same kind of name; value rules are per name |
| `sortable` and `add` | independent; `sortable` does not imply an add position |
| Unknown template name | warning, not an error: the page is saved and apply-to-all finds nothing |
| Unknown fragment file | error: a repeater without its fragment can add nothing |
| Roles per element | at most one; properties need their role on the same element |
| `sortable` with `add="any"` | allowed: button before every block, grip on every block |

## Removed bug class

`data-jaco-template` is missing from `ALLOWED_ATTRIBUTES`, so it is stripped on
every save:

```php
sanitize_html('<footer data-jaco-template="footer">x</footer>')
// '<footer>x</footer>'
```

A page that is edited once loses its template binding. The rename touches the
template attribute anyway, so the whitelist entry comes with it. Note that the
old form (`data-jaco-template="footer"`) and the new form are the same string —
the bug is in the whitelist, not in the name. The rename fixes the whitelist; it
does not rename the attribute.

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
<img data-jaco-image data-jaco-image-resizable src="media/blog.jpg" alt="Post" width="800">
<img data-jaco-image data-jaco-image-lightbox src="media/cake.jpg" alt="Cake" width="400">
<img data-jaco-image data-jaco-image-resizable data-jaco-image-lightbox
     src="media/cake.jpg" alt="Cake" width="400">
```

After saving a lightbox image:

```html
<a href="media/cake-full.jpg">
  <img data-jaco-image data-jaco-image-lightbox src="media/cake.jpg" alt="Cake" width="400">
</a>
```

An image with its own link wins: no `-full` file.

```html
<a href="shop.html">
  <img data-jaco-image data-jaco-image-lightbox src="media/cake.jpg" width="400">
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
<section data-jaco-repeater data-jaco-repeater-fragment="feature_en.html">…</section>
<section data-jaco-repeater data-jaco-repeater-fragment="feature_en.html" data-jaco-repeater-add="top">…</section>
<section data-jaco-repeater data-jaco-repeater-fragment="feature_en.html" data-jaco-repeater-add="bottom">…</section>
<section data-jaco-repeater data-jaco-repeater-fragment="feature_en.html" data-jaco-repeater-add="any">…</section>
<section data-jaco-repeater data-jaco-repeater-fragment="feature_en.html"
         data-jaco-repeater-add="top" data-jaco-repeater-sortable>…</section>
```

With and without a label:

```html
<section data-jaco-repeater
         data-jaco-repeater-fragment="news_item.html"
         data-jaco-repeater-add="top" data-jaco-repeater-sortable
         data-jaco-repeater-label="Add news">…</section>

<section data-jaco-repeater
         data-jaco-repeater-fragment="news_item.html">…</section>
```

Without `data-jaco-repeater-label` the add button carries no text. Deriving one
from the fragment name is listed under [Out of scope](#out-of-scope).

A complete repeater. The heading is not an item: no delete button, not moved when
blocks are sorted.

```html
<section data-jaco-repeater
         data-jaco-repeater-fragment="feature_en.html"
         data-jaco-repeater-label="Add feature"
         data-jaco-repeater-add="top" data-jaco-repeater-sortable>
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
<section data-jaco-repeater data-jaco-repeater-fragment="news_item.html"
         data-jaco-repeater-label="Add news">…</section>

<div class="team">
  <section data-jaco-repeater data-jaco-repeater-fragment="member.html"
           data-jaco-repeater-label="Add member"
           data-jaco-repeater-add="top" data-jaco-repeater-sortable>…</section>
</div>
```

### Template

```html
<footer data-jaco-template="footer">…</footer>

<footer data-jaco-template="footer-en">…</footer>
<footer data-jaco-template="footer-de">…</footer>

<!-- several instances of the same name on one page -->
<footer data-jaco-template="footer">…</footer>
<footer data-jaco-template="footer">…</footer>
```

### Transient, while it runs

```html
<!-- upload without lightbox -->
<img data-jaco-image
     data-jaco-transient-filename="IMG_0042.jpg"
     src="media/team.jpg" width="800">

<!-- upload with lightbox -->
<img data-jaco-image data-jaco-image-lightbox
     data-jaco-transient-filename="IMG_0042.jpg"
     data-jaco-transient-full="data:image/jpeg;base64,/9j/4AAQSkZJRg…"
     src="media/cake.jpg" width="400">

<!-- picked from the media library: nothing is written, no transient attributes -->
<img data-jaco-image src="media/existing.jpg" width="800">

<!-- while a block or a line is being edited -->
<div data-jaco-block data-jaco-transient-active>…</div>
<h2 data-jaco-line data-jaco-transient-active>…</h2>

<!-- the size badge, inline or block depending on the parent -->
<p>Text <img data-jaco-image src="media/icon.svg" width="24">
   <span data-jaco-transient-badge>Image size: HTML 24x24, …</span>
</p>

<div class="figure">
  <img data-jaco-image src="media/team.jpg" width="800">
  <div data-jaco-transient-badge>Image size: HTML 800x600, …</div>
</div>
```

One image through the whole cycle:

```html
<!-- in the file -->
<img data-jaco-image data-jaco-image-lightbox src="media/cake.jpg" width="400">

<!-- while uploading -->
<img data-jaco-image data-jaco-image-lightbox
     data-jaco-transient-filename="IMG_0042.jpg"
     data-jaco-transient-full="data:image/jpeg;base64,…"
     src="media/cake.jpg" width="400">

<!-- saved -->
<a href="media/cake-full.jpg">
  <img data-jaco-image data-jaco-image-lightbox src="media/cake.jpg" width="400">
</a>
```

### Rejected forms

Each of these has to produce a message on save instead of being ignored.

| Form | Why it is rejected |
| --- | --- |
| `<img data-jaco-image-resizable>` | property without its role |
| `<section data-jaco-repeater data-jaco-repeater-add="top">` | repeater without a fragment |
| `<section data-jaco-repeater data-jaco-repeater-add="middle" …>` | unknown add position |
| `<div data-jaco-repeater-item>` outside a repeater | item outside its container |
| `<section data-jaco-repeater data-jaco-repeater-item>` | both roles on one element |
| `<div data-jaco-repeater-item data-jaco-repeater-fragment="x.html">` | property on an item |
| `<footer data-jaco-template>` | role without its required value |
| `<div data-jaco-block="x">` | value on a role that takes none |
| `<div data-jaco-block data-jaco-line>` | two roles on one element |
| `<section data-jaco-repeater data-jaco-repeater-fragment="nosuch.html">` | fragment file does not exist |
| `<div><div data-jaco-repeater-item>` inside a repeater | item is not a direct child |
| `<img data-jaco-image data-jaco-transient-full="…">` | full image without a lightbox |

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
| Alignment | `data-jaco-block-align="left\|center\|right"` | closed vocabulary, CSS can select it |
| Background | `data-jaco-block-tone="light\|dark\|brand"` | tones from the design, not arbitrary colours |
| Height and width | `data-jaco-block-size="small\|medium\|large"` | steps, CSS-selectable |
| Exact pixels | – | needs inline `style`, which the sanitizer strips |

```css
[data-jaco-block-align="center"] { text-align: center }
[data-jaco-block-tone="dark"]    { background: #1a1a1a; color: #f5f5f5 }
[data-jaco-block-size="large"]   { padding: 4rem 2rem }
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

If block settings are added later, they follow the same shape as the rest:
a property on the role with a closed set of values. The fact that a role can carry
a value (`data-jaco-template="…"`) does not open the door to free values: the
value rule is per name, and a new name is added to the closed list before it is
used.

## Implementation order

Nothing below is done. The naming is agreed, the code still uses the old names.
When the work starts, this is the order; step 1 is written first and is supposed
to fail until the rest agrees.

| # | Step | Where |
| --- | --- | --- |
| 1 | Contract test: read the thirteen names from the whitelist, compare them with the frontend selectors and with every page | new test next to `IgnoreRulesTest` |
| 2 | Whitelist and validation rules | `backend/core/html.php` |
| 3 | Selectors | `frontend/editor.js`, `frontend/contentlists.js` |
| 4 | Pages, add position written out | 14 files in the root and `de/` |
| 5 | Templates | 12 files under `jaco-data/templates/` |
| 6 | Fix the template marker (see `docs/backlog.md`) | `backend/core/html.php` |
| 7 | Suites | PHPUnit, QUnit, Playwright |

**The contract test runs after every step and must be green before the next step
starts.** With a hard break, the intermediate states are inconsistent by design
(old names in code, new names in the whitelist, or vice versa); the test is what
keeps the inconsistency bounded to one step. A step that leaves the test red is a
step that is not finished.

**Step 1** checks three places against each other, the same way `IgnoreRulesTest`
guards the ignore rules and `MenuRoleTest` the menu. Without it a rename that
missed one selector looks green. The page check parses HTML, which is more work
than the constant-to-constant checks; this is why it is a step of its own.

**Step 2** holds the thirteen persistent names and deliberately none of the
transient ones, so a transient attribute cannot survive a save. The pairing rules
from [What the server validates](#what-the-server-validates) go in the same place:
a property without its role, an item outside a repeater, a repeater without a
fragment, an unknown add position, a value on a role that takes none, a missing
value on a role that requires one, an unknown name.

**Step 3** replaces every selector. With a hard break there is no old name to fall
back to, so a missed selector silently stops working - the contract test from step
1 is what catches it. One behaviour changes with the split: `makeSortable` in
`frontend/contentlists.js` runs today only when the position is `draggable`. It has
to run whenever `data-jaco-repeater-sortable` is present, for every add position,
otherwise `add="bottom"` with sorting silently stays fixed.

**Step 4** rewrites 14 pages. The add position becomes explicit (`top` was
implicit `any` before), `pos="draggable"` splits into `add="top"` plus `sortable`,
image capabilities become two properties, the template marker keeps its string
but loses its second attribute.

**Step 5** touches 12 templates: six content fragments, two page templates, four
site templates. They carry the same attributes as the pages.

**Step 6** is the bug from `docs/backlog.md`: `data-jaco-template` is missing from
the whitelist and is stripped on every save. The rename touches the whitelist
anyway (thirteen names, no `-name`), so the entry comes with it.

**Step 7** runs everything: PHPUnit (400 tests), QUnit (180), Playwright (35), and
`./build-dist.sh` plus `./verify-dist-archive.sh`.

### Why the contract test comes first

With a hard break, a page that still carries `data-jaco-editable` does not fail
loudly - the sanitizer strips the attribute and the page quietly stops being
editable. A test that compares the three sources is the only thing that makes the
rename verifiable instead of hopeful. Running it after every step is the
difference between "the rename is done" and "the rename looks done".

## Out of scope

- The `-full.ext` convention behind the lightbox.
- `data-jaco-transient-full` carries base64 image data in a DOM attribute; the
  rename does not make that cheaper.
- Deriving the add-button label from the fragment name, which would make
  `data-jaco-repeater-label` optional in practice. It fixes the "labels carry the page
  language" item in `docs/backlog.md` and belongs in its own change.
- Deriving a template name when it is absent. The value is required because an
  anonymous template has no apply-to-all group; if that changes, it is a new
  decision, not a rename.

## Changelog

| Version | Date | Change |
| --- | --- | --- |
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
