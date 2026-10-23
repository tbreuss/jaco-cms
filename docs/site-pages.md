# Public site pages

The demo site ships one English and one German tree. Both trees use the same
English file names, so a page maps to its translation by prefixing `de/`:

| English | German |
|---------|--------|
| `index.html` | `de/index.html` |
| `features.html` | `de/features.html` |
| `docs.html` | `de/docs.html` |
| `download.html` | `de/download.html` |
| `privacy.html` | `de/privacy.html` |
| `imprint.html` | `de/imprint.html` |

`404.html` and `elements.html` exist in English only.

Keep the pattern for new pages: an English slug in both trees. Renaming a page
means updating the internal links, the `canonical`, `hreflang` and `og:url`
values of both language versions, and — for the navigation and footer — the
templates under `jaco-data/templates/site/`.

The language switch in `assets/nav.js` sends visitors to the other language's
`index.html`, so no per-page link table is needed there.

## Crawler entry points

`robots.txt` keeps crawlers out of `/jaco/`, `/jaco-data/` and `/media/` and
points at the sitemap. `sitemap.xml` lists every public page in both languages
with `xhtml:link` alternates for `en`, `de` and `x-default`.

Both files are hand maintained: a new page must be added to `sitemap.xml` and
to the deploy payload in `.github/workflows/jobs.yml`. The staging guard in
`.htaccess` (`X-Robots-Tag: noindex, nofollow`) still blocks indexing until it
is removed for the go-live.
