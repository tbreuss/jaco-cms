import {test, expect} from '@playwright/test'

test('a resizable image opens its tools, not the media picker', async ({page}) => {
  await page.goto('/jaco/#!/login')
  await page.fill('#username', 'admin')
  await page.fill('#password', 'test-password')
  await page.locator('form button').click()
  await expect(page).toHaveURL(/\/jaco\/#!\/$/)

  await page.evaluate(() => { document.getElementById('iframe').src = '/elements.html' })
  const frame = page.frameLocator('#iframe')
  await expect(frame.locator('h1')).toContainText('Editable content elements')
  await page.evaluate(() => window.m.route.set('/page-edit'))
  await expect(frame.locator('.jaco-content-add-button').first()).toBeVisible()

  // the resizable image carries both names
  const image = frame.locator('img[data-jaco-image--resizable]').first()
  await expect(image).toBeVisible()
  await image.evaluate((el) => el.click())

  await expect(frame.locator('#editableImageExtended')).toBeVisible()
  await expect(frame.locator('#editableMedia')).toHaveCount(0)
})
