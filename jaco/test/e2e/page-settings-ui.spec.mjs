import {test, expect} from '@playwright/test'

const login = async (page) => {
  await page.goto('/jaco/#!/login')
  await page.fill('#username', 'admin')
  await page.fill('#password', 'test-password')
  await page.locator('form button').click()
  await expect(page).toHaveURL(/\/jaco\/#!\/$/)
}

test('inline editor exposes page settings without opening the raw HTML view', async ({page}) => {
  await login(page)
  await page.evaluate(() => {
    document.getElementById('iframe').src = '/elements.html'
  })
  const frame = page.frameLocator('#iframe')
  await expect(frame.locator('h1')).toContainText('Editable content elements')
  await page.evaluate(() => window.m.route.set('/page-edit'))
  await expect(frame.locator('.jaco-content-add-button').first()).toBeVisible()

  await page.getByRole('button', {name: 'Page settings'}).click()
  await expect(page.locator('.modal h2')).toHaveText('Page settings')
  await expect(page.locator('#pageSettingsTitle')).toHaveValue('Editable content elements – Jaco CMS')
  await expect(page.locator('#pageSettingsDescription')).toBeEditable()
  await expect(page.locator('#pageSettingsRobots')).toBeEnabled()
  await expect(page.locator('#pageSettingsRobots')).toHaveValue('noindex, nofollow, noarchive')
  await page.getByRole('button', {name: 'Cancel'}).last().click()
  await expect(page.locator('.modal')).toHaveCount(0)
})

test('raw page editor exposes title and metadata fields', async ({page}) => {
  await login(page)
  await page.goto('/jaco/#!/pages/edit/elements.html')
  await expect(page.locator('#pageTitle')).toBeEnabled()
  await expect(page.locator('#metaDescription')).toBeEnabled()
  await expect(page.locator('#robots')).toBeEnabled()
})
