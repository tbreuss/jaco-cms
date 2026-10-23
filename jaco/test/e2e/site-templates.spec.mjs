import {test, expect} from '@playwright/test'

test('admin can open the site template editor', async ({page}) => {
  await page.goto('/jaco/#!/login')
  await page.fill('#username', 'admin')
  await page.fill('#password', 'test-password')
  await page.locator('form button').click()
  await expect(page).toHaveURL(/\/jaco\/#!\/$/)

  await page.goto('/jaco/#!/templates')
  await expect(page.locator('h1')).toHaveText('Site templates')
  await page.locator('#templateFile').selectOption('e2e-footer.html')
  await expect(page.locator('#templateFile')).toHaveValue('e2e-footer.html')
  await expect(page.locator('#templateName')).toHaveValue('e2e-footer')
  await expect(page.locator('#ace')).toBeVisible()
  await expect(page.getByRole('button', {name: 'Preview & apply', exact: true})).toBeEnabled()
})


test('admin can preview and apply a site template', async ({page}) => {
  await page.goto('/jaco/#!/login')
  await page.fill('#username', 'admin')
  await page.fill('#password', 'test-password')
  await page.locator('form button').click()
  await expect(page).toHaveURL(/\/jaco\/#!\/$/)

  await page.goto('/jaco/#!/templates')
  await expect(page.locator('#ace')).toBeVisible()
  await page.locator('#templateFile').selectOption('e2e-footer.html')
  await expect(page.locator('#templateName')).toHaveValue('e2e-footer')
  await expect(page.locator('#ace')).toContainText('E2E footer')
  await page.evaluate(() => {
    ace.edit('ace').setValue('<footer>After apply</footer>')
  })
  await page.getByRole('button', {name: 'Preview & apply', exact: true}).click()

  await expect(page.locator('.modal h2')).toHaveText('Apply template to all pages?')
  await expect(page.locator('.modal')).toContainText('1 pages with 1 elements')
  const layers = await page.evaluate(() => ({
    modal: Number(getComputedStyle(document.querySelector('.modal')).zIndex),
    gutter: Number(getComputedStyle(document.querySelector('.ace_gutter')).zIndex),
  }))
  expect(layers.modal).toBeGreaterThan(layers.gutter)
  await page.locator('.modal').getByRole('button', {name: 'Save & apply to all pages'}).click()

  const response = await page.request.get('/e2e-template-page.html')
  expect(response.ok()).toBeTruthy()
  const html = await response.text()
  expect(html).toContain('<footer data-jaco-template="e2e-footer">After apply</footer>')
})
