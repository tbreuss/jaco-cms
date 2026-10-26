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
  // the dropdown shows the file name, extension included: the marker in a page
  // has to match it exactly
  await expect(page.locator('#templateFile option:checked')).toHaveText('e2e-footer.html')
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
  await expect(page.locator('#templateFile')).toHaveValue('e2e-footer.html')
  await expect(page.locator('#ace')).toContainText('E2E footer')
  await page.evaluate(() => {
    ace.edit('ace').setValue('<footer>After apply</footer>')
  })
  await page.getByRole('button', {name: 'Preview & apply', exact: true}).click()

  await expect(page.locator('.modal h2')).toHaveText('Apply template to all pages?')
  await expect(page.locator('.modal')).toContainText('pages with')
  const layers = await page.evaluate(() => ({
    modal: Number(getComputedStyle(document.querySelector('.modal')).zIndex),
    gutter: Number(getComputedStyle(document.querySelector('.ace_gutter')).zIndex),
  }))
  expect(layers.modal).toBeGreaterThan(layers.gutter)
  // saving and applying are two requests since they became two rights: wait for
  // the second one instead of reading the page while it is still in flight
  const applied = page.waitForResponse((response) =>
    response.url().includes('action=template/site/apply') && response.status() === 200)
  await page.locator('.modal').getByRole('button', {name: 'Save & apply to all pages'}).click()
  await applied

  const response = await page.request.get('/e2e-template-page.html')
  expect(response.ok()).toBeTruthy()
  const html = await response.text()
  expect(html).toContain('<footer data-jaco-template="e2e-footer.html">After apply</footer>')
})

test('the apply dialog does not claim there is no backup', async ({page}) => {
  // applying backs every rewritten page up, so the old warning told the opposite
  await page.goto('/jaco/#!/login')
  await page.fill('#username', 'admin')
  await page.fill('#password', 'test-password')
  await page.locator('form button').click()
  await expect(page).toHaveURL(/\/jaco\/#!\/$/)

  await page.evaluate(() => window.m.route.set('/templates'))
  await expect(page.locator('#templateFile')).toBeVisible()

  await page.getByRole('button', {name: 'Preview & apply'}).click()

  const dialog = page.locator('.modal')
  await expect(dialog).toBeVisible()
  await expect(dialog).toContainText('Every page that is rewritten is backed up first.')
  await expect(dialog).not.toContainText('No backups are created')
})

test('an editor may apply a template but not change it', async ({page}) => {
  // the markup belongs to the admin, rolling it out is the editor's job
  await page.goto('/jaco/#!/login')
  await page.fill('#username', 'editor')
  await page.fill('#password', 'test-password')
  await page.locator('form button').click()
  await expect(page).toHaveURL(/\/jaco\/#!\/$/)

  await page.evaluate(() => window.m.route.set('/templates'))
  await expect(page.locator('#templateFile')).toBeVisible()

  // a disabled button carries the reason as its accessible name, so locate the
  // labels by their visible text instead
  const save = page.locator('button', {hasText: 'Save'}).first()
  await expect(save).toHaveAttribute('aria-disabled', 'true')

  const apply = page.locator('button', {hasText: 'Preview & apply'})
  await expect(apply).toBeVisible()
  await expect(apply).not.toHaveAttribute('aria-disabled', 'true')

  await apply.click()
  const dialog = page.locator('.modal')
  await expect(dialog).toBeVisible()
  await expect(dialog).toContainText('The template is applied as it is stored.')
})
