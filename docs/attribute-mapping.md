# Data attribute contract

The `data-jaco-*` attributes are the contract between a page and the editor. They
are written by hand into HTML that a customer already owns, so every name here is
public API, not an internal detail.

## The grammar

```text
data-jaco-<role>[="value"]               a role: the editor acts on the element
data-jaco-<role>--<property>[="value"]   a property: it configures that role
```

A role name may contain single hyphens; the double hyphen separates the property
from its role. Whether a value is absent, optional or required is a property of the
name, not of the grammar: a role can carry a value (`data-jaco-template="footer.html"`)
and a property can be a switch (`data-jaco-repeater--sortable`).

The pairing is mechanical: a property is valid only on an element that carries the
role in front of the `--`. The **closed list of names** decides which roles and
properties exist and which values they take; an unknown name is an error (see
[What the server validates](#what-the-server-validates)).

### Roles

A role is what the editor does to the element. At most **one role per element**;
an element without a role carries no property.

| Role | Value | Meaning |
| --- | --- | --- |
| `data-jaco-block` | none | multi-line text |
| `data-jaco-line` | none | one line of text |
| `data-jaco-link` | none | link, edited in a dialog |
| `data-jaco-image` | none | image; the editor replaces the file |
| `data-jaco-repeater` | **required** | container of repeatable blocks; the value is the fragment file |
| `data-jaco-template` | **required** | site template instance; the value is the template file |

### Properties

**A property is optional, and a role without it does the least it can.** More
function is asked for explicitly. A forgotten property gives a smaller editor, which
shows at once and does no harm; the opposite default would grant more than the
author meant, unnoticed. A value is required only where without it something is
missing (the fragment, the template file). The kind of property decides its name:

- **mode** (`--mode`): values that exclude each other and make the same element
  work differently. At most one per role.
- **switch** (`--sortable`): present or absent, never with a value. `="true"` and
  `="false"` do not exist: in HTML the presence of an attribute is the answer.
- **named value** (`--add`, `--label`, `--tools`): the value means something of
  its own.

| Property | On | Value | Meaning |
| --- | --- | --- | --- |
| `data-jaco-block--tools` | `data-jaco-block` | `basic`, `full` | what the editor offers; without it `plain` |
| `data-jaco-line--tools` | `data-jaco-line` | `basic` | what the editor offers; without it `plain` |
| `data-jaco-image--mode` | `data-jaco-image` | `fill`, `fit`, `any` | how the file is scaled to `width` x `height`; without it the file is only replaced |
| `data-jaco-repeater--label` | `data-jaco-repeater` | text | label of the add button; without it "Add block" in the editor language |
| `data-jaco-repeater--add` | `data-jaco-repeater` | `top`, `bottom` | where the add button goes; without it `top` |
| `data-jaco-repeater--sortable` | `data-jaco-repeater` | none | blocks can be reordered by the grip |

Properties describe what the editor offers or does. What the page looks like is the
page's own CSS.

## Text

`block` and `line` are two roles: they say what the editor does, several lines or
one. `--tools` says what each offers; without it the element is `plain`.

| Role | `--tools` | Offers |
| --- | --- | --- |
| `data-jaco-block` | none = `plain` | no formatting, paragraphs only |
| | `basic` | bold, italic, underline, strike, link |
| | `full` | headings, bold, italic, underline, strike, quote, code, lists, indent, link, video |
| `data-jaco-line` | none = `plain` | no formatting |
| | `basic` | bold, italic, underline, strike, link |

`basic` is the same in both roles; `full` adds structure and exists for `block`
only. The server binds the allowed tags to the value: formatting a set does not
offer is removed on save, so a hand-written `<strong>` does not survive in a `plain`
block.

## Image

| Form | What happens to the file | The editor chooses |
| --- | --- | --- |
| `data-jaco-image` | replaced as it is; `width` and `height` are layout only | - |
| `+ data-jaco-image--mode="fill"` | scaled and cut to `width` x `height` | nothing |
| `+ data-jaco-image--mode="fit"` | scaled so the whole image fits into `width` x `height` | nothing |
| `+ data-jaco-image--mode="any"` | like `fill` or `fit`, as chosen in the dialog (default `fill`) | `fit` or `fill` |

The attribute decides, not the presence of `width` and `height`. The editor has no
field for them: they come from the markup and stay as written. A mode needs at least
one of them, otherwise there is nothing to scale to; with only one, `fill` and `fit`
scale in proportion. The bare role is the only spelling of "replace".

The dialog is the same in every form: alt text, choose a file, choose from the
media library; `any` adds the choice between `fit` and `fill`. Replacing the file
clears the alt text or asks for it again, because the old one describes another
picture. An unscaled upload has no size limit of its own. A file with another aspect
ratio than the markup needs `height: auto` in the CSS.

## File values

The fragment of `data-jaco-repeater` (relative to `jaco-data/templates/content/`)
and the file of `data-jaco-template` (relative to `jaco-data/templates/site/`)
follow **one syntax**: the path of the file relative to its directory, the same
string the admin lists and the APIs take.

```text
value   = segment *( "/" segment ) ".html"
segment = 1*( ALPHA / DIGIT / "-" / "_" )
```

As a regular expression: `^[A-Za-z0-9_-]+(/[A-Za-z0-9_-]+)*\.html$`.

- The separator is `/` on every platform; a backslash is not allowed.
- A segment has letters, digits, `-` and `_`: no dots, so `..` and hidden files
  cannot occur; no leading, trailing or empty segment, no spaces or umlauts.
- The value is compared as written, case-sensitively.
- Subdirectories are allowed, with no depth limit beyond the file system's.

The value is **validated, not sanitized**: a value normalised into another one would
bind a page to a file nobody wrote. A value that does not match is an error on save,
and the API refuses it the same way; one function does this for both roles, the APIs
and apply-to-all. A file that does not exist is an **error** for a repeater, because
without its fragment it cannot add anything, and a **warning** for a template,
because a page may reference a template before its file is uploaded.

## Repeater

The value is the fragment, a file in `jaco-data/templates/content/` that provides a
new block (see [File values](#file-values)).

Every **direct child** of the container is one block, with a delete bar and, with
`--sortable`, a grip. Nothing else stands in the container: a heading belongs in a
wrapper around it. The editor's own elements (buttons, bars) are never saved.

`--add` puts the button before the first block (`top`, the default) or after the
last (`bottom`); it is independent of `--sortable`. Without `--label` the button
says "Add block", translated by the editor, so the page does not decide the
language of the button.

## Template

The value is the template file in `jaco-data/templates/site/` (see
[File values](#file-values)). Apply-to-all replaces every instance with that value
across the project. The value is **required**: a template without a name has no
group to apply to.

## What the server validates

| Rule | Message |
| --- | --- |
| `data-jaco-<role>--<property>` only on an element that carries `data-jaco-<role>` | property without its role |
| at most one role per element | two roles on one element |
| `data-jaco-block`, `-line`, `-link`, `-image` carry no value | unexpected value on a role |
| `data-jaco-repeater`, `data-jaco-template` without a value | repeater without a fragment, template without a name |
| their value matches the pattern in [File values](#file-values) | invalid fragment or template value |
| `data-jaco-repeater` value names a file in `jaco-data/templates/content/` | error: unknown fragment |
| `data-jaco-template` value names a file in `jaco-data/templates/site/` | warning: unknown template, the page is still saved |
| `data-jaco-repeater--add` is `top` or `bottom` | unknown add position |
| `data-jaco-repeater--sortable` carries no value | unexpected value |
| `data-jaco-block--tools` is `basic` or `full`; `data-jaco-line--tools` is `basic` | unknown tools |
| `data-jaco-image--mode` is `fill`, `fit` or `any` | unknown mode |
| `data-jaco-image--mode` needs `width` or `height` on the element | mode without a dimension |
| unknown `data-jaco-*` name | unknown name |

Errors are reported before saving, not silently dropped.

## Where the contract lives

`backend/core/html.php` (the whitelist `ALLOWED_ATTRIBUTES`), the selectors in
`frontend/editor.js` and `frontend/contentlists.js`, the pages and templates, and
this file. A test compares the whitelist with the document, the selectors and the
pages, in the pattern of `IgnoreRulesTest` and `MenuRoleTest`.

### The code is the source

`ALLOWED_ATTRIBUTES` is the only place that decides which names exist; every other
mention is a copy that can drift. The code defines the names, this document
describes them, and the test proves the description is still right. The whitelist
holds twelve names and none of the editor's own state:

```php
'data-jaco-block', 'data-jaco-line', 'data-jaco-link', 'data-jaco-image',
'data-jaco-repeater', 'data-jaco-template',
'data-jaco-block--tools', 'data-jaco-line--tools', 'data-jaco-image--mode',
'data-jaco-repeater--label', 'data-jaco-repeater--add', 'data-jaco-repeater--sortable',
```

The test compares it with the tables under [Roles](#roles) and
[Properties](#properties) and with this block, in both directions.

### Serialisation

The browser writes every empty attribute as `=""` (`<div data-jaco-block="">`).
`getHTML()` in `frontend/lib/iframe.js` undoes that with **one generic rule**: an
empty `data-jaco-*` attribute loses its `=""`, whatever it is called, so a new role
needs no line there. Other attributes keep their empty value: `<a href="">` is a
link to the current page, not an absent one.

### Editor state

The editor keeps its state **out of the DOM** and sets no `data-jaco-*` attribute
while it runs, so the page it saves is the page the author wrote. What it needs - the
open block, the file name of an upload, the size badge - lives in the editor (a
class, a `WeakSet`, a `WeakMap`). An editor-state name that turns up in a page is an
unknown name and is rejected on save.

## Gallery

A gallery is not in the contract. The model is designed, not built: the author
writes both files and a container marks the group.

```html
<div class="gallery" data-jaco-gallery>
  <a href="media/cake.jpg"><img src="media/cake-thumb.jpg" alt="Cake" width="300" height="100"></a>
</div>
```

Open: the name of the container attribute, what a click on an image inside it
replaces, and whether a save may ever build structure on its own. Until then
`data-jaco-gallery` is not in the whitelist and a save removes it.

## Appendix: every form

Valid forms first, then what the server rejects with a message on save instead of
ignoring it.

### Block

```html
<div data-jaco-block><p>A paragraph.</p></div>
<div data-jaco-block data-jaco-block--tools="basic"><p>With <strong>formatting</strong>.</p></div>
<div data-jaco-block data-jaco-block--tools="full"><h2>A heading</h2><ul><li>A list</li></ul></div>
```

| Rejected | Why |
| --- | --- |
| `<div data-jaco-block data-jaco-block--tools="plain">` | `plain` is the absence of the property |
| `<div data-jaco-block data-jaco-block--align="center">` | unknown name |
| `<div data-jaco-block="x">` | value on a role that takes none |

### Line

```html
<h1 data-jaco-line>Welcome</h1>
<h1 data-jaco-line data-jaco-line--tools="basic">Why <em>Jaco CMS</em>?</h1>
```

| Rejected | Why |
| --- | --- |
| `<h1 data-jaco-line data-jaco-line--tools="full">` | a line has no `full` |

### Link

The dialog edits `href` and `target`.

```html
<a href="contact.html" data-jaco-link>Contact</a>
<a href="https://example.com" target="_blank" data-jaco-link>External</a>

<!-- a link around an image: two roles on two elements -->
<a href="shop.html" data-jaco-link>
  <img data-jaco-image src="media/cake.jpg" width="400" alt="">
</a>
```

| Rejected | Why |
| --- | --- |
| `<a data-jaco-link="x" href="a.html">` | value on a role that takes none |

### Image

```html
<img data-jaco-image src="media/photo.jpg" width="640" height="360" alt="Photo">
<img data-jaco-image data-jaco-image--mode="fill" src="media/team.jpg" alt="Team" width="640" height="360">
<img data-jaco-image data-jaco-image--mode="fit" src="media/logo.jpg" alt="Logo" width="640" height="360">
<img data-jaco-image data-jaco-image--mode="any" src="media/blog.jpg" alt="Post" width="1200" height="600">
```

| Rejected | Why |
| --- | --- |
| `<img data-jaco-image--mode="fill" width="640">` | property without its role |
| `<img data-jaco-image data-jaco-image--mode="fill">` | a mode without `width` or `height` |
| `<img data-jaco-image data-jaco-image--mode="basic" width="640">` | unknown mode: the bare role is the only spelling of "replace" |

### Repeater

```html
<section data-jaco-repeater="feature-en.html">…</section>
<section data-jaco-repeater="feature-en.html" data-jaco-repeater--add="bottom">…</section>
```

A complete repeater. Every direct child is a block; a heading goes outside:

```html
<h2 data-jaco-line>Why Jaco CMS?</h2>
<section data-jaco-repeater="feature-en.html"
         data-jaco-repeater--label="Add feature"
         data-jaco-repeater--sortable>
  <div>
    <h3 data-jaco-line>Edit in place</h3>
    <div data-jaco-block><p>Change text directly on the page.</p></div>
  </div>
  <div>
    <h3 data-jaco-line>No database</h3>
    <div data-jaco-block><p>Plain HTML files, nothing else.</p></div>
  </div>
</section>
```

| Rejected | Why |
| --- | --- |
| `<section data-jaco-repeater>` | repeater without a fragment |
| `<section data-jaco-repeater="nosuch.html">` | fragment file does not exist |
| `<section data-jaco-repeater="../x.html">` | invalid fragment value |
| `<section data-jaco-repeater="x.html" data-jaco-repeater--add="any">` | unknown add position |
| `<section data-jaco-repeater="x.html" data-jaco-repeater--sortable="true">` | a switch carries no value |

### Template

```html
<footer data-jaco-template="footer.html">…</footer>
<footer data-jaco-template="footers/footer-en.html">…</footer>
```

| Rejected | Why |
| --- | --- |
| `<footer data-jaco-template>` | role without its required value |
| `<footer data-jaco-template="footer">` | value without the extension |

### Any role

| Rejected | Why |
| --- | --- |
| `<div data-jaco-block data-jaco-line>` | two roles on one element |
| `<div data-jaco-is-editing>` | an editor-state name in a page is unknown |
