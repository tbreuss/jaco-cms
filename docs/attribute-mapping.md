# Data attribute contract

The `data-jaco-*` attributes are the contract between a page and the editor.
They are written by hand into HTML that a customer already owns, so every name
here is public API, not an internal detail.

**This file is the plan, not the current state.** It describes what the contract
is going to be; the code implements part of it. Which names exist *today* is
decided by `ALLOWED_ATTRIBUTES` in `backend/core/html.php`, and which of them
the pages use is what the contract test compares. Where the two differ, this
document wins as the target and the code is behind - never the other way round.

The gap between plan and code is tracked in `docs/backlog.md` ("Move the pages,
templates and docs to the new contract" and its neighbours). The document does
not repeat the list: a second copy of a work list goes stale the first day.

## The grammar

```text
data-jaco-<role>[="value"]               a role: the editor acts on the element
data-jaco-<role>--<property>[="value"]   a property: it configures that role
```

A role name may contain single hyphens; the double hyphen separates the property
from its role. Whether a value is absent, optional or required is a property of
the name, not of the grammar: a role can carry a value
(`data-jaco-template="footer.html"`) and a property can be a switch
(`data-jaco-repeater--sortable`).

The pairing is mechanical: a property is valid only on an element that carries
the role in front of the `--`. The **closed list of names** decides which roles
and properties exist and which values they take; an unknown name is an error
(see [What the server validates](#what-the-server-validates)).

## What may change on save

The markup describes what the editor may do. Saving changes content - text,
images, links - never that description.

| Written by author | Changed by editor | Changed by server |
| --- | --- | --- |
| every `data-jaco-*` | never | never |
| `src`, `alt` | yes, the file and its alternative text | `src`, when an upload is stored |
| `href`, `target` | yes, in the link dialog | never |
| text inside `block`, `line` | yes | no, formatting outside the tools is removed |
| `width`, `height` | never | never |

A value the editor would have to rewrite is a value the editor is not allowed to
change: that is why `--mode="choice"` remembers nothing (see [Image](#image)). A
page that does not fit the contract is **refused on save, not repaired** - a
marker that disappears silently is worse than a save that stops and says why.

The one exception is content: an upload replaces the `src` of the image it
belongs to, and the server writes that path. Everything else in the markup stays
as the author wrote it.

### Roles

A role is what the editor does to the element. At most **one role per element**;
an element without a role carries no property.

| Role | Value | Meaning |
| --- | --- | --- |
| `data-jaco-block` | none | multi-line text |
| `data-jaco-line` | none | one line of text |
| `data-jaco-link` | none | link, edited in a dialog |
| `data-jaco-image` | none | image; the editor replaces the file and scales nothing |
| `data-jaco-repeater` | **required** | container of repeatable blocks; the value is the fragment file |
| `data-jaco-template` | **required** | site template instance; the value is the template file |

### Properties

**A property is optional, and a role without it does the least it can.** More
function is asked for explicitly. A forgotten property gives a smaller editor,
which shows at once and does no harm; the opposite default would grant more than
the author meant, unnoticed. A value is required only where without it something
is missing (the fragment, the template file). The kind of property decides its
name:

- **mode** (`--mode`): values that exclude each other and make the same element
  work differently. At most one per role.
- **switch** (`--sortable`): present or absent, never with a value. `="true"`
  and `="false"` do not exist: in HTML the presence of an attribute is the
  answer.
- **named value** (`--position`, `--label`, `--tools`): the value means
  something of its own.

| Property | On | Value | Meaning |
| --- | --- | --- | --- |
| `data-jaco-block--tools` | `data-jaco-block` | `basic`, `full` | what the editor offers; without it `plain` |
| `data-jaco-line--tools` | `data-jaco-line` | `basic` | what the editor offers; without it `plain` |
| `data-jaco-image--mode` | `data-jaco-image` | `fill`, `fit`, `choice` | how the file is scaled to `width` x `height`; without it the file is only replaced |
| `data-jaco-repeater--label` | `data-jaco-repeater` | text | label of the add button; without it "Add block" in the editor language |
| `data-jaco-repeater--position` | `data-jaco-repeater` | `top`, `bottom`, `any` | where the add button stands; without it `top` |
| `data-jaco-repeater--sortable` | `data-jaco-repeater` | none | blocks can be reordered by the grip |

Properties describe what the editor offers or does. What the page looks like is
the page's own CSS.

## Text

`block` and `line` are two roles: they say what the editor does, several lines
or one. `--tools` says what each offers; without it the element is `plain`.

| Role | `--tools` | Offers |
| --- | --- | --- |
| `data-jaco-block` | none = `plain` | no formatting, paragraphs only |
| | `basic` | bold, italic, underline, strike, link (a dialog sets the URL) |
| | `full` | headings, bold, italic, underline, strike, quote, code, lists, indent, link (a dialog sets the URL), video (an `<iframe>` in the text) |
| `data-jaco-line` | none = `plain` | no formatting |
| | `basic` | bold, italic, underline, strike, link (a dialog sets the URL) |

`basic` is the same in both roles; `full` adds structure and exists for `block`
only. In a `plain` block Enter starts a new paragraph.

**The tools are not roles.** `link` and `video` in this table are buttons in the
toolbar, not `data-jaco-link` and not a marker of their own. They produce
ordinary markup inside the text - an `<a href>` or an `<iframe>` - and that
markup belongs to the text, not to the contract. The size the video dialog gives
the `<iframe>` is part of the content it produces; the contract has no rule
about it, and nothing in it is a `data-jaco-*` name.

Only `link` shares its word with a role, so only that one needs separating:

- the **button** wraps selected text in an `<a href>`. It lives in the toolbar,
  exists only while the block is edited, and produces markup a page does not
  have to know.
- the **role** `data-jaco-link` marks an `<a>` that is edited as a whole, with
  its URL and its target, and cannot stand inside a `block` (see
  [Nesting](#nesting)).

The button is listed here because `--tools` decides whether it appears, and
because its output is what the server has to keep: a `<strong>` needs `basic`,
an `<a>` from the button needs `basic` too.

**The tools decide what survives a save.** The server binds the allowed tags to
the value, so a `<strong>` in a `plain` block - or an `<em>` in a `plain` line -
is removed the first time the page is saved, also when nobody touched that
block. That is deliberate: a plain block is plain. It also means that switching
a block from `full` to `basic` removes every heading and list in it on the next
save, and switching back does not bring them back. Choose the value when the
page is written, not later.

## Image

| Form | What happens to the file | The editor chooses |
| --- | --- | --- |
| `data-jaco-image` | replaced as it is, nothing is scaled; `width` and `height` are layout only | - |
| `+ data-jaco-image--mode="fill"` | the file is `width` x `height`; the image covers that box and what does not fit is cut | nothing |
| `+ data-jaco-image--mode="fit"` | the file is `width` x `height`; the whole image sits in that box and the rest stays transparent | nothing |
| `+ data-jaco-image--mode="choice"` | like `fill` or `fit`, as chosen in the dialog, for that upload | `fit` or `fill` |

**The file always has the size of the markup.** A scaled upload is a
`width` x `height` image, whatever its source was: `fill` covers the box, `fit`
puts the whole picture into it and leaves the rest transparent. The box is the
layout, so what the editor shows is what the page shows.

**`width` and `height` belong to the page.** The editor reads them and never
writes them; there is no field for them in the dialog, and what the author
wrote stays written. They say two things at once - how much room the layout
reserves and which format the file is cut to - which is acceptable because
only one side ever reads them.

They are not duplicated into `data-jaco-image--width`: two numbers that have
to agree would eventually disagree, and without the plain HTML attributes the
browser reserves no space before the image loads.

A mode needs at least one of them, otherwise there is nothing to scale to; with
only one, `fill` and `fit` are the same and scale in proportion; both are
accepted.

**The bare role is the only spelling of "replace".** There is no
`--mode="none"`: a mode is a mode, and leaving it out is how a page says "do not
touch the file".

**This is a change for existing pages.** Until now every `data-jaco-image` was
cut to its `width` x `height`, because that was the only thing the editor could
do. A page written against that behaviour keeps it by adding `--mode="fill"`;
left bare, the same image starts being replaced uncut the first time a file is
uploaded. Eight markers in this repository are affected, all on the two
elements reference pages and two of them inside code samples; the work list is
in the backlog, not here.

**`choice` stores nothing.** It lets the editor pick `fit` or `fill` for the
file being uploaded, and the pick does not survive as a value: the attribute
keeps saying `choice`, and the next upload starts on `fill` again. That is
intended - the markup says what the page allows, not what happened once. A page
whose logo must always be fitted writes `--mode="fit"` and leaves the editor no
choice to make.

The dialog is the same in every form: alt text, choose a file, choose from the
media library. `choice` adds the `fit`/`fill` switch, and only for the file
that is about to be uploaded. Replacing the file clears the alt text and puts
the cursor in its field, because the old text describes another picture. An
unscaled upload has no size limit of its own. A file with another aspect ratio
than the markup needs `height: auto` in the CSS.

## File values

The fragment of `data-jaco-repeater` (relative to
`jaco-data/templates/content/`) and the file of `data-jaco-template` (relative
to `jaco-data/templates/site/`) follow **one syntax**: the path of the file
relative to its directory, the same string the admin lists and the APIs take.

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

The value is **validated, not sanitized**: a value normalised into another one
would bind a page to a file nobody wrote. A value that does not match is an
error on save, and the API refuses it the same way; one function does this for
both roles, the APIs and apply-to-all. A file that does not exist is an
**error** for a repeater, because without its fragment it cannot add anything,
and a **warning** for a template, because a page may reference a template before
its file is uploaded.

## Repeater

The value is the fragment, a file in `jaco-data/templates/content/` that
provides a new block (see [File values](#file-values)).

Every **direct child** of the container is one block, with a delete bar and,
with `--sortable`, a grip. Nothing else stands in the container: a heading
belongs in a wrapper around it, and text directly in the container is an error
(whitespace and comments are ignored). A fragment has **exactly one root
element**, because it becomes one block; two roots would become two blocks. The
editor's own elements (buttons, bars) are never saved.

**`--position` says where the add button stands.** The button is the thing the
attribute describes; that a new block lands where the button was pressed follows
from it. It is independent of `--sortable`.

| Value | Button | New block lands |
| --- | --- | --- |
| `top` (default) | one, before the first block | in front |
| `bottom` | one, after the last block | at the end |
| `any` | one before **every** block, and one after the last | where the button was pressed |

`any` gives **n + 1 buttons for n blocks**, never two in the same gap: the
button belongs to the block it stands in front of, and sits *before* it, not
after. With three blocks the column reads button, block, button, block, button,
block, button.

Without `--label` the button says "Add block", translated by the editor, so the
page does not decide the language of the button.

## Template

The value is the template file in `jaco-data/templates/site/` (see [File
values](#file-values)). Apply-to-all replaces every instance with that value
across the project. The value is **required**: a template without a name has no
group to apply to.

## Nesting

Which role may stand inside which is decided by the click: the editor opens the
innermost editor that the click hits, and `block` and `line` take the click
before anything inside them.

| Inside | Allowed roles |
| --- | --- |
| `block`, `line` | none: an `<a>` or `<img>` inside is plain content, not a role |
| `link` | `image` (the image is edited by a click on it, the link by a click beside it) |
| `repeater` | any role, inside the blocks |
| `template` | any role |
| `image` | none |

A role inside a `block` or `line` is an error on save; it could never be
reached.

## What the server validates

| Rule | Message |
| --- | --- |
| `data-jaco-<role>--<property>` only on an element that carries `data-jaco-<role>` | property without its role |
| at most one role per element | two roles on one element |
| `data-jaco-block`, `-line`, `-link`, `-image` carry no value | unexpected value on a role |
| `data-jaco-repeater`, `data-jaco-template` without a value | repeater without a fragment, template without a name |
| their value matches the pattern in [File values](#file-values) | invalid fragment or template value |
| `data-jaco-repeater` value names a file in `jaco-data/templates/content/` | error: unknown fragment |
| the fragment has exactly one root element | fragment with several roots |
| no text directly in a repeater container | text outside a block |
| no role inside `data-jaco-block` or `data-jaco-line` | role inside a text element |
| `data-jaco-template` value names a file in `jaco-data/templates/site/` | warning: unknown template, the page is still saved |
| `data-jaco-repeater--position` is `top`, `bottom` or `any` | unknown button position |
| `data-jaco-repeater--sortable` carries no value | unexpected value |
| `data-jaco-block--tools` is `basic` or `full`; `data-jaco-line--tools` is `basic` | unknown tools |
| `data-jaco-line--tools="full"` | `full` exists for `block` only |
| `data-jaco-image--mode` is `fill`, `fit` or `choice` | unknown mode |
| `data-jaco-image--mode` needs `width` or `height` on the element | mode without a dimension: an error, not a silent fallback to "replace" |
| unknown `data-jaco-*` name | unknown name |

Errors are reported before saving, not silently dropped.

**An unknown name stops the save.** It is not stripped: a marker that vanishes
silently leaves a page that looks saved and no longer works, and nobody knows
why. The author sees the name and can fix it. The cost is that a page with a
typo cannot be saved until the typo is gone - which is the point.

## Where the contract lives

`backend/core/html.php` (the whitelist `ALLOWED_ATTRIBUTES`), the selectors in
`frontend/editor.js` and `frontend/contentlists.js`, the pages and templates,
and this file as the plan. A test compares the whitelist with the selectors, the
stylesheets, the guides and the pages - not with this file, which is ahead of
the code by design. It follows the pattern of `IgnoreRulesTest` and
`MenuRoleTest`.

### The plan and the code

Two different questions, two different answers:

- **Which names exist today?** `ALLOWED_ATTRIBUTES` answers it, and only it.
  Every other mention is a copy that can drift; no test parses this document,
  because a test that reads the plan would fail as long as the plan is ahead of
  the code.
- **Which names should exist?** This document answers it, and it is deliberately
  written in the present tense: the target reads better than a wish list, and
  the backlog carries the work list.

The plan names twelve names and no editor state:

```php
'data-jaco-block', 'data-jaco-line', 'data-jaco-link', 'data-jaco-image',
'data-jaco-repeater', 'data-jaco-template',
'data-jaco-block--tools', 'data-jaco-line--tools', 'data-jaco-image--mode',
'data-jaco-repeater--label', 'data-jaco-repeater--position',
'data-jaco-repeater--sortable',
```

The whitelist holds more than that today (among them `data-jaco-repeater-item`
and `data-jaco-repeater--add`) and less (`--tools` and `--mode` do not exist
yet). That difference is the work, not an error: the contract test guards the
places against each other, and the backlog closes the gap in one hard break.

### Serialisation

The browser writes every empty attribute as `=""` (`<div data-jaco-block="">`).
`getHTML()` in `frontend/lib/iframe.js` undoes that with **one generic rule**:
an empty `data-jaco-*` attribute loses its `=""`, whatever it is called, so a
new role needs no line there. Other attributes keep their empty value: `<a
href="">` is a link to the current page, not an absent one.

### Editor state

The editor keeps its state **out of the DOM** and sets no `data-jaco-*`
attribute while it runs, so the page it saves is the page the author wrote. What
it needs - the open block, the file name of an upload, the size badge - lives in
the editor (a class, a `WeakSet`, a `WeakMap`). An editor-state name that turns
up in a page is an unknown name and is rejected on save.

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

Valid forms first, then what the server rejects with a message on save instead
of ignoring it.

### Block

```html
<div data-jaco-block><p>A paragraph.</p></div>
<div data-jaco-block data-jaco-block--tools="basic"><p>With <strong>formatting</strong> and a <a href="contact.html">link</a>.</p></div>
<div data-jaco-block data-jaco-block--tools="full"><h2>A heading</h2><ul><li>A list</li></ul></div>
```

The `<a>` above is ordinary content: written into the text by hand or with the
toolbar button, without a marker, because it is not edited as a whole. An `<a
data-jaco-link>` inside the block would be the other thing, and it is rejected
(see the last row).

| Rejected | Why |
| --- | --- |
| `<div data-jaco-block data-jaco-block--tools="plain">` | `plain` is the absence of the property |
| `<div data-jaco-block data-jaco-block--align="center">` | unknown name |
| `<div data-jaco-block="x">` | value on a role that takes none |
| `<div data-jaco-block><a data-jaco-link href="a.html">x</a></div>` | role inside a text element |

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
<img data-jaco-image data-jaco-image--mode="choice" src="media/blog.jpg" alt="Post" width="1200" height="600">
```

| Rejected | Why |
| --- | --- |
| `<img data-jaco-image--mode="fill" width="640">` | property without its role |
| `<img data-jaco-image data-jaco-image--mode="fill">` | a mode without `width` or `height` |
| `<img data-jaco-image data-jaco-image--mode="basic" width="640">` | unknown mode: the bare role is the only spelling of "replace" |

### Repeater

```html
<section data-jaco-repeater="feature-en.html">…</section>
<section data-jaco-repeater="feature-en.html" data-jaco-repeater--position="bottom">…</section>
<section data-jaco-repeater="feature-en.html" data-jaco-repeater--position="any">…</section>
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
| `<section data-jaco-repeater="x.html">text<div>…</div></section>` | text outside a block |
| `<section data-jaco-repeater="x.html" data-jaco-repeater--position="middle">` | unknown button position |
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
