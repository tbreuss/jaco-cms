# Missing features

What Jaco still lacks, ranked by value for its target audiences (restaurants,
clubs, small companies, private sites). Deliberately not a roadmap, an
assessment: each item states the gap, who hurts, and the rough size.

## 1. Self-update mechanism

An installed site (ZIP extracted, no git) has no path to security updates.
The audience runs shared hosting without maintenance contracts, so
installations rot. This is the largest structural risk in the project.

Size: medium (version check against releases, copy new files, keep
`settings.php` and user content untouched).

## 2. Multiple users and roles

One login, no second editor, no roles — a real obstacle for clubs run by
volunteers and companies shared between owner and agency. Side effect: no
protection against concurrent editing (last write wins, backups are the
only net).

Size: large (user records in `settings.php` or files, session handling, UI).

## 3. SEO basics

No sitemap export, no meta description / Open Graph management, no redirect
on rename (a renamed page leaves its old URL as a 404, internal links keep
pointing at it). For restaurants, search visibility is revenue.

Size: small to medium — sitemap from the page listing, a redirect table as
a text file, meta fields as markers. Note: the sanitizer only touches the
body, so head editing needs its own path.

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
