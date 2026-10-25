import {test, expect} from '@playwright/test'

// Smoke coverage only: each route must render, or redirect for a role that may
// not use it. These tests do not exercise CRUD, saving, uploads or restores.
//
// Routes are changed on the already loaded app instead of reloading the whole
// admin shell for every assertion. Deep links are covered by smoke.spec.mjs.

const passwords = {
  admin: 'test-password',
  editor: 'test-password',
}

const view = (selector, text = null) => ({selector, text})
const everyRole = (selector, text = null) => ({
  admin: view(selector, text),
  editor: view(selector, text),
})

const routes = [
  {name: 'Start', path: '/', roles: everyRole('.navbar')},
  {name: 'Pages', path: '/pages', roles: everyRole('h1', 'Pages')},
  {
    name: 'Page editor',
    path: '/pages/edit/index.html',
    roles: {
      admin: view('h1', 'Edit page'),
      editor: view('h1', 'Edit page'),
    },
  },
  {name: 'Inline editor', path: '/page-edit', roles: everyRole('.dialog', 'Save')},
  {
    name: 'Add page',
    path: '/page-add',
    roles: {admin: view('h2', 'Add page'), editor: view('h2', 'Add page')},
  },
  {
    name: 'Copy page',
    path: '/page-copy',
    roles: {admin: view('h2', 'Copy page'), editor: view('h2', 'Copy page')},
  },
  {
    name: 'Delete page',
    path: '/page-delete',
    roles: {admin: view('h2', 'Delete page'), editor: view('h2', 'Delete page')},
  },
  {name: 'Jump to page', path: '/page-goto', roles: everyRole('h2', 'Jump to page')},
  {name: 'Media', path: '/uploads', roles: everyRole('h1', 'Media')},
  {
    name: 'Upload detail',
    path: '/uploads/view/e2e-smoke.png',
    roles: everyRole('h1', 'View upload'),
  },
  {
    name: 'Backups',
    path: '/backups',
    roles: {
      admin: view('h1', 'Backups'),
      // an editor restores his own work
      editor: view('h1', 'Backups'),
    },
  },
  {
    name: 'Backup detail',
    path: '/backups/view/20260101_000000_e2e1_index.html',
    roles: {
      admin: view('h1', 'View backup'),
      editor: view('h1', 'View backup'),
    },
  },
  {
    name: 'Design',
    path: '/css',
    roles: {
      admin: view('h1', 'Edit CSS'),
      // an editor reads the stylesheet, the heading says so
      editor: view('h1', 'CSS'),
    },
  },
  {
    name: 'Templates',
    path: '/templates',
    roles: {
      admin: view('h1', 'Site templates'),
      // an editor reads and applies site templates, only the markup stays admin
      editor: view('h1', 'Site templates'),
    },
  },
  {name: 'Password', path: '/password', roles: everyRole('h1', 'Change password')},
  {
    name: 'Environment',
    path: '/system/env',
    // admin area: the load answers 403 and the layout explains it
    roles: {admin: view('h1', 'System environment'), editor: view('h1', 'Access denied')},
  },
  {
    name: 'PHP error log',
    path: '/system/log',
    roles: {admin: view('h1', 'PHP error log'), editor: view('h1', 'Access denied')},
  },
  {
    name: 'PHP info',
    path: '/system/php-info',
    roles: {admin: view('h1', 'PHP info'), editor: view('h1', 'Access denied')},
  },
  {name: 'About', path: '/system/about', roles: everyRole('h1', 'About')},
  {name: 'Logout', path: '/logout', roles: everyRole('h2', 'Logged out')},
]

const login = async (page, role) => {
  await page.goto('/jaco/#!/login')
  await page.fill('#username', role)
  await page.fill('#password', passwords[role])
  await page.locator('form button').click()
  await expect(page).toHaveURL(/\/jaco\/#!\/$/)
}

test('login page renders', {tag: '@smoke'}, async ({page}) => {
  await page.goto('/jaco/#!/login')
  await expect(page.locator('#username')).toBeVisible()
})

for (const role of Object.keys(passwords)) {
  test(`${role} can open every admin route`, {tag: '@smoke'}, async ({page}) => {
    await login(page, role)

    for (const route of routes) {
      await test.step(route.name, async () => {
        const expected = route.roles[role]
        await page.evaluate((path) => window.m.route.set(path), route.path)

        if (!expected) {
          await expect(page).toHaveURL(/#!\/?$/)
          return
        }

        const target = page.locator(expected.selector).first()
        await expect(target).toBeVisible()
        if (expected.text) {
          await expect(target).toContainText(expected.text)
        }
      })
    }
  })
}

test('a refused page explains itself without an uncaught error', async ({page}) => {
  const uncaught = []
  page.on('pageerror', (error) => uncaught.push(error.message))

  await login(page, 'editor')
  await page.evaluate(() => window.m.route.set('/system/php-info'))

  await expect(page.locator('h1').first()).toContainText('Access denied')
  // the rejected request must be handled by the model, not left as an unhandled
  // rejection in the console
  await page.waitForTimeout(500)
  expect(uncaught).toEqual([])
})

test('a denial does not survive a role change', async ({page}) => {
  await login(page, 'editor')
  await page.evaluate(() => window.m.route.set('/system/php-info'))
  await expect(page.locator('h1').first()).toContainText('Access denied')

  // log in as an admin without reloading: a reload would reset the module state
  // that this test is about
  await page.evaluate(() => {
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    window.m.route.set('/login')
  })
  await expect(page.locator('#username')).toBeVisible()
  await page.fill('#username', 'admin')
  await page.fill('#password', 'test-password')
  await page.locator('form button').click()
  await expect(page).toHaveURL(/\/jaco\/#!\/$/)

  await page.evaluate(() => window.m.route.set('/system/php-info'))
  await expect(page.locator('h1').first()).toContainText('PHP info')
})

test('a read-only admin sees why the server pages stay closed', async ({page}) => {
  // the demo restriction is not a missing role: the explanation has to say so
  await page.goto('/jaco/#!/login')
  await page.fill('#username', 'readonly')
  await page.fill('#password', 'test-password')
  await page.locator('form button').click()
  await expect(page).toHaveURL(/\/jaco\/#!\/$/)

  await page.evaluate(() => window.m.route.set('/system/php-info'))

  const notice = page.locator('.main .content')
  await expect(notice.locator('h1')).toContainText('Access denied')
  await expect(notice.locator('.denied-reason')).toContainText('Not available in the demo')
  await expect(notice.locator('.denied-detail')).toContainText('shows details of the server')
})
