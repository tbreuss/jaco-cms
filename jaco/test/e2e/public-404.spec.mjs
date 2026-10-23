import {test, expect} from '@playwright/test'

test('a missing public URL serves the branded 404 page with status 404', {tag: '@smoke'}, async ({page}) => {
  const response = await page.goto('/this-page-does-not-exist')

  expect(response?.status()).toBe(404)
  await expect(page.locator('h1')).toContainText('404')
  await expect(page.locator('meta[name="robots"]')).toHaveAttribute('content', /noindex/)
})
