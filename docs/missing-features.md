# Missing features

What Jaco still lacks, ranked by value for its target audiences (restaurants,
clubs, small companies, private sites). Deliberately not a roadmap, an
assessment: each item states the gap, who hurts, and the rough size.

## 1. Self-update mechanism

**Implemented.** `php jaco/cli.php sys:check-update` and `sys:update` (plus a
button on the System page) download a published release, back up the previous
`jaco/` and replace only the application directory. `settings.php`, backups,
runtime, templates, media and the public pages stay untouched. See
[install.md](install.md#updating).

## 2. Account management UI

Roles and multiple users now exist: `settings.php` lists users with `admin` and
`editor` roles, and a `readOnly` flag gives a read-only account for clients and
public demos. What is still missing is an interface to create, edit and remove
accounts from the admin instead of editing `settings.php` by hand.

Size: medium — user records, password resets and TOTP enrolment already have
CLI counterparts; the UI work is the form and the route. Protection against
concurrent editing (last write wins, backups are the only net) is a separate
item.

## 3. SEO basics

Page title, meta description and robots are managed through page settings.
Still missing: sitemap export, Open Graph management and a redirect on rename
(a renamed page leaves its old URL as a 404, internal links keep pointing at
it). For restaurants, search visibility is revenue.

Size: small to medium — sitemap from the page listing, a redirect table as
a text file and Open Graph fields on top of the existing metadata endpoint.

## 4. Contact form

Restaurants (reservations), hairdressers (appointments), small companies
(quotes) — nearly every target group needs exactly this, and a flat-file
CMS can only offer a `mailto:` hack or an external service. Scoping decision
required: a simple form mailer (one action, spam protection, destination
address in settings) is small and would complete the product for these
industries.

## 5. Drafts and preview before publish

Every save goes live immediately. No draft, no preview without saving, no
scheduling. Risky for inexperienced editors.

Size: medium — the backup machinery exists; a draft is a backup that only
goes live on publish.

## 6. Media housekeeping

Upload list without folders, without unused-file detection, without alt text
care. It grows with every menu season.

Size: small (group the listing, count references from page contents).

## Explicitly not recommended

Content multilingualism (language folders as a convention are enough for
this audience), site search (static sites of this size rarely need it),
frontend analytics.
