import {test, expect} from '@playwright/test'

// Functional E2E coverage for the image media picker: the API request, the
// iframe modal and the root-relative src assignment all run for real here.
test('admin can choose an existing image from the media picker', async ({page}) => {
  await page.goto('/jaco/#!/login')
  await page.fill('#username', 'admin')
  await page.fill('#password', 'test-password')
  await page.locator('form button').click()
  await expect(page).toHaveURL(/\/jaco\/#!\/$/)

  await page.evaluate(() => {
    document.getElementById('iframe').src = '/elements.html'
  })

  const frame = page.frameLocator('#iframe')
  await expect(frame.locator('h1')).toContainText('Editable content elements')
  await page.evaluate(() => window.m.route.set('/page-edit'))
  // The editor scripts are injected asynchronously; content-list controls prove
  // they are ready before the image click is dispatched.
  await expect(frame.locator('.jaco-content-add-button').first()).toBeVisible()

  const image = frame.locator('img[data-jaco-editable-image]').first()
  await expect(image).toBeVisible()
  await image.evaluate((element) => element.click())

  await expect(frame.locator('#editableMedia')).toBeVisible()
  const fixture = frame.locator('.jaco-media-item[title="media/e2e-smoke.png"]')
  await expect(fixture).toBeVisible()
  await fixture.click()

  await expect(image).toHaveAttribute('src', '/media/e2e-smoke.png')
  await expect(frame.locator('#editableMedia')).toHaveCount(0)
})
