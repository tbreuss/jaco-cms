import {test, expect} from '@playwright/test'

test('inline editor chrome follows the dark site theme', async ({page}) => {
  await page.goto('/jaco/#!/login')
  await page.fill('#username', 'admin')
  await page.fill('#password', 'test-password')
  await page.locator('form button').click()
  await expect(page).toHaveURL(/\/jaco\/#!\/$/)

  await page.evaluate(() => {
    localStorage.setItem('jaco-theme', 'dark')
    document.getElementById('iframe').src = '/elements.html'
  })

  const frame = page.frameLocator('#iframe')
  await expect(frame.locator('h1')).toContainText('Editable content elements')
  await expect(frame.locator('html')).toHaveAttribute('data-theme', 'dark')
  await page.evaluate(() => window.m.route.set('/page-edit'))
  await expect(frame.locator('.jaco-content-add-button').first()).toBeVisible()

  const richText = frame.locator('div[data-jaco-block]').filter({hasText: 'A rich-text block supports'})
  await richText.locator('p').first().click()
  const toolbar = frame.locator('.ql-toolbar.ql-snow')
  await expect(toolbar).toBeVisible()
  await expect(toolbar).toHaveCSS('background-color', 'rgb(15, 17, 21)')

  const image = frame.locator('img[data-jaco-image]').first()
  await image.click()
  const picker = frame.locator('.jaco-media-dialog')
  await expect(picker).toBeVisible()
  await expect(picker).toHaveCSS('background-color', 'rgb(15, 17, 21)')
})
