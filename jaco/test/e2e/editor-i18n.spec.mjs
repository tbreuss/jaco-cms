import {test, expect} from '@playwright/test'

test('Quill header labels follow the user language', async ({page}) => {
  await page.goto('/jaco/#!/login')
  await page.fill('#username', 'admin_de')
  await page.fill('#password', 'test-password')
  await page.locator('form button').click()
  await expect(page).toHaveURL(/\/jaco\/#!\/$/)

  await page.evaluate(() => {
    document.getElementById('iframe').src = '/elements.html'
  })

  const frame = page.frameLocator('#iframe')
  await expect(frame.locator('h1')).toContainText('Editable content elements')
  await page.evaluate(() => window.m.route.set('/page-edit'))
  await expect(frame.locator('.jaco-content-add-button').first()).toBeVisible()

  const richText = frame.locator('div[data-jaco-block]').filter({hasText: 'A rich-text block supports'})
  await richText.locator('p').first().click()

  await expect(frame.locator('.ql-picker.ql-header .ql-picker-label')).toHaveAttribute('data-label', 'Normal')
  await expect(frame.locator('.ql-picker.ql-header .ql-picker-item[data-value="1"]')).toHaveAttribute('data-label', 'Überschrift 1')

  const tooltip = frame.locator('.ql-tooltip')
  await expect(tooltip).toHaveCount(1)
  const linkLabel = await tooltip.evaluate((element) => {
    element.setAttribute('data-mode', 'link')
    return getComputedStyle(element, '::before').content
  })
  expect(linkLabel).toBe('"Link eingeben:"')

  const saveLabel = await tooltip.evaluate((element) => {
    element.classList.add('ql-editing')
    return getComputedStyle(element.querySelector('a.ql-action'), '::after').content
  })
  expect(saveLabel).toBe('"Speichern"')
})
