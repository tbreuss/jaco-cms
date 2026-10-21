import {test, expect} from '@playwright/test'

// Smoke coverage only: boot the shell, log in, open the Pages list and log out.
// Functional page CRUD is covered separately.
test('admin can log in, open the pages list and log out', {tag: '@smoke'}, async ({page}) => {
  await page.goto('/jaco/#!/login')

  await expect(page.locator('#username')).toBeVisible()
  await page.fill('#username', 'admin')
  await page.fill('#password', 'test-password')
  await page.locator('form button').click()

  await expect(page.locator('.navbar')).toBeVisible()
  await page.getByRole('button', {name: 'Content'}).click()
  await page.getByRole('menuitem', {name: 'Pages'}).click()
  await expect(page.locator('h1')).toContainText('Pages')

  await page.getByRole('button', {name: 'Account'}).click()
  await page.getByRole('menuitem', {name: 'Logout'}).click()
  await expect(page.getByRole('heading', {name: 'Logged out'})).toBeVisible()
  await page.getByRole('link', {name: 'Login again'}).click()
  await expect(page.locator('#username')).toBeVisible()
})
