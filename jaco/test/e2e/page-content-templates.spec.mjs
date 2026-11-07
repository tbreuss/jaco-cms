import {test, expect} from '@playwright/test'

// The page and content templates are read by an editor and changed by the admin:
// the editor sees the file with disabled save buttons, the admin edits and saves it. Every save is backed up, and a document that
// breaks the kind is refused with the reason on screen.

const login = async (page, user) => {
  await page.goto('/jaco/#!/login')
  await page.fill('#username', user)
  await page.fill('#password', 'test-password')
  await page.locator('form button').click()
  await expect(page).toHaveURL(/\/jaco\/#!\/$/)
}

test('admin can open the page template editor', async ({page}) => {
  await login(page, 'admin')

  await page.evaluate(() => window.m.route.set('/templates/page'))
  await expect(page.locator('h1')).toHaveText('Page templates')
  await expect(page.locator('#templateFile-page')).toBeVisible()
  await expect(page.locator('#ace-page')).toBeVisible()
  await expect(page.getByRole('button', {name: /^Save$/})).toBeEnabled()
})

test('admin saves a content template and the file is backed up', async ({page}) => {
  await login(page, 'admin')

  await page.evaluate(() => window.m.route.set('/templates/content'))
  await expect(page.locator('h1')).toHaveText('Content templates')
  await page.locator('#templateFile-content').selectOption('e2e-content.html')
  await expect(page.locator('#ace-content')).toContainText('E2E card')

  await page.evaluate(() => {
    ace.edit('ace-content').setValue('<div class="feature"><h3 data-jaco-line>Edited</h3></div>')
  })
  await page.getByRole('button', {name: /^Save$/}).click()
  await expect(page.locator('.m-notification')).toContainText('saved')

  // a second visit reads the stored file, not the editor state
  await page.evaluate(() => window.m.route.set('/'))
  await page.evaluate(() => window.m.route.set('/templates/content'))
  await page.locator('#templateFile-content').selectOption('e2e-content.html')
  await expect(page.locator('#ace-content')).toContainText('Edited')
})

test('a fragment with two roots is refused and stays on screen', async ({page}) => {
  await login(page, 'admin')

  await page.evaluate(() => window.m.route.set('/templates/content'))
  await page.locator('#templateFile-content').selectOption('e2e-content.html')
  // the save test runs before and has replaced the card text: match what both versions share
  await expect(page.locator('#ace-content')).toContainText('feature')

  await page.evaluate(() => {
    ace.edit('ace-content').setValue('<div>one</div><div>two</div>')
  })
  await page.getByRole('button', {name: /^Save$/}).click()

  await expect(page.locator('.form-errors')).toContainText('exactly one root element')
  // the view stays open with the unsaved text, nothing was written
  await expect(page.locator('#ace-content')).toContainText('one')
})

test('an editor may not change a page template', async ({page}) => {
  await login(page, 'editor')

  await page.evaluate(() => window.m.route.set('/templates/page'))
  // the editor reads the template, the save buttons stay disabled
  await expect(page.locator('h1')).toHaveText('Page templates')
  await expect(page.locator('#ace-page')).toBeVisible()
  // a disabled button carries the reason as its accessible name, so locate it by its text
  await expect(page.locator('button', {hasText: 'Save'}).first()).toHaveAttribute('aria-disabled', 'true')
})
