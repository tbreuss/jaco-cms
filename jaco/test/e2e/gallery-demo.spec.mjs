import {test, expect} from '@playwright/test'

test('public view: the gallery image opens the dialog', async ({page}) => {
  await page.goto('/elements.html')
  const image = page.locator('.elements-gallery[data-demo-full]').first()
  await expect(image).toBeVisible()
  await image.click()
  await expect(page.locator('dialog.elements-modal')).toBeVisible()
  await expect(page.locator('dialog.elements-modal img')).toHaveAttribute('src', /gallery-full/)
})

test('editor: the same image opens the tools, not the dialog', async ({page}) => {
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

  const image = frame.locator('[data-jaco-image--resizable][data-jaco-image--lightbox]')
  await image.evaluate((el) => el.click())
  await expect(frame.locator('#editableImageExtended')).toBeVisible()
  await expect(frame.locator('dialog.elements-modal')).toHaveCount(0)
})
