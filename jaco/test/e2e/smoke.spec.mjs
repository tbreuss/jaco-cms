import {test, expect} from '@playwright/test'

// Smoke coverage only: boot the shell, log in, open the Pages list and log out.
// Functional page CRUD is covered separately.
test('admin can log in, open the pages list and log out', {tag: '@smoke'}, async ({page}) => {
  await page.goto('/jaco/#!/login')

  await expect(page.locator('#username')).toBeVisible()
  await page.fill('#username', 'admin')
  await page.fill('#password', 'test-password')
  await page.locator('form button').click()

  await expect(page).toHaveURL(/\/jaco\/#!\/$/)
  await page.getByRole('button', {name: 'Content'}).click()
  await page.getByRole('menuitem', {name: 'Pages'}).click()
  await expect(page.locator('h1')).toContainText('Pages')

  await page.getByRole('button', {name: 'Account'}).click()
  await page.getByRole('menuitem', {name: 'Logout'}).click()
  await expect(page.getByRole('heading', {name: 'Logged out'})).toBeVisible()
  await page.getByRole('link', {name: 'Login again'}).click()
  await expect(page.locator('#username')).toBeVisible()
})

// The shell spans the viewport so a short admin page can fill it. Its empty area
// must not swallow clicks meant for the site preview underneath.
test('the site preview stays usable behind the empty shell', {tag: '@smoke'}, async ({page}) => {
  await page.goto('/jaco/#!/login')
  await page.fill('#username', 'admin')
  await page.fill('#password', 'test-password')
  await page.locator('form button').click()
  await expect(page).toHaveURL(/\/jaco\/#!\/$/)

  const hit = await page.evaluate(() => {
    const element = document.elementFromPoint(
      Math.round(window.innerWidth / 2),
      Math.round(window.innerHeight / 2),
    )
    return element ? element.tagName + '#' + element.id : 'none'
  })
  expect(hit).toBe('IFRAME#iframe')

  const frame = page.frameLocator('#iframe')
  await frame.getByRole('link', {name: 'Features', exact: true}).first().click()
  await expect.poll(() => page.frames().find((item) => item !== page.mainFrame())?.url())
    .toContain('/features.html')
})
