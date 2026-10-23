import {test, expect} from '@playwright/test'

test('navigation active state follows the page and language', {tag: '@smoke'}, async ({page}) => {
  await page.goto('/features.html')
  await expect(page.locator('.navigation li.active a')).toHaveText('Features')
  await expect(page.locator('.navigation li.active a')).toHaveAttribute('aria-current', 'page')

  await page.goto('/de/features.html')
  await expect(page.locator('.navigation li.active a')).toHaveText('Funktionen')
  await expect(page.locator('.navigation li.active a')).toHaveAttribute('aria-current', 'page')

  await page.goto('/privacy.html')
  await expect(page.locator('.navigation li.active')).toHaveCount(0)
  await expect(page.locator('.nav-utility a')).toHaveAttribute('href', '/de/index.html')

  await page.goto('/de/imprint.html')
  await expect(page.locator('.nav-utility a')).toHaveAttribute('href', '/index.html')
})
