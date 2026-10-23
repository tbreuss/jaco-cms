import {test, expect} from '@playwright/test'

test('admin can open the site template editor', async ({page}) => {
  await page.goto('/jaco/#!/login')
  await page.fill('#username', 'admin')
  await page.fill('#password', 'test-password')
  await page.locator('form button').click()
  await expect(page).toHaveURL(/\/jaco\/#!\/$/)

  await page.goto('/jaco/#!/templates')
  await expect(page.locator('h1')).toHaveText('Site templates')
  await expect(page.locator('#templateFile')).toHaveValue('e2e-footer.html')
  await expect(page.locator('#templateName')).toHaveValue('e2e-footer')
  await expect(page.locator('#ace')).toBeVisible()
  await expect(page.locator('input[type=checkbox]')).toBeEnabled()
})


test('admin can preview and apply a site template', async ({page}) => {
  await page.goto('/jaco/#!/login')
  await page.fill('#username', 'admin')
  await page.fill('#password', 'test-password')
  await page.locator('form button').click()
  await expect(page).toHaveURL(/\/jaco\/#!\/$/)

  await page.goto('/jaco/#!/templates')
  await expect(page.locator('#ace')).toBeVisible()
  await page.evaluate(() => {
    ace.edit('ace').setValue('<footer>After apply</footer>')
  })
  await page.locator('input[type=checkbox]').check()
  await page.getByRole('button', {name: 'Save'}).first().click()

  await expect(page.locator('.modal h2')).toHaveText('Apply template to all pages?')
  await expect(page.locator('.modal')).toContainText('1 pages with 1 elements')
  await page.getByRole('button', {name: 'Apply to all'}).click()

  const response = await page.request.get('/e2e-template-page.html')
  expect(response.ok()).toBeTruthy()
  const html = await response.text()
  expect(html).toContain('<footer data-jaco-template="e2e-footer">After apply</footer>')
})
