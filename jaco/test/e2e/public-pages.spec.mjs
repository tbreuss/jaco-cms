import {test, expect} from '@playwright/test'

// Public pages are unauthenticated: check status, document title and the
// visible top-level heading without touching the admin installation.
const pages = [
  {path: '/', title: 'Make your existing HTML editable', h1: 'Make your existing website editable.'},
  {path: '/features.html', title: 'Features', h1: 'Jaco CMS features'},
  {path: '/docs.html', title: 'Docs', h1: 'Jaco CMS docs'},
  {path: '/download.html', title: 'Download', h1: 'Download Jaco CMS'},
  {path: '/privacy.html', title: 'Privacy', h1: 'Privacy policy'},
  {path: '/imprint.html', title: 'Imprint', h1: 'Imprint'},
  {path: '/de/index.html', title: 'Bestehende HTML-Website editierbar machen', h1: 'Bestehende Website. Einfach editierbar.'},
  {path: '/de/features.html', title: 'Funktionen', h1: 'Jaco CMS Funktionen'},
  {path: '/de/docs.html', title: 'Doku', h1: 'Jaco CMS Doku'},
  {path: '/de/download.html', title: 'Download', h1: 'Jaco CMS herunterladen'},
  {path: '/de/datenschutz.html', title: 'Datenschutz', h1: 'Datenschutzerklärung'},
  {path: '/de/impressum.html', title: 'Impressum', h1: 'Impressum'},
  {path: '/elements.html', title: 'Editable content elements', h1: 'Editable content elements'},
]

for (const pageInfo of pages) {
  test(`public page ${pageInfo.path} renders`, {tag: '@smoke'}, async ({page}) => {
    const response = await page.goto(pageInfo.path)

    expect(response?.status()).toBe(200)
    await expect(page).toHaveTitle(new RegExp(pageInfo.title.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')))
    await expect(page.locator('h1')).toContainText(pageInfo.h1)
  })
}
