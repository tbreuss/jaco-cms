import {test, expect} from '@playwright/test'

// Smoke coverage only: each route must render, or redirect for a role that may
// not use it. These tests do not exercise CRUD, saving, uploads or restores.

const passwords = {
  admin: 'test-password',
  editor: 'test-password',
  viewer: 'test-password',
}

const view = (selector, text = null) => ({selector, text})
const everyRole = (selector, text = null) => ({
  admin: view(selector, text),
  editor: view(selector, text),
  viewer: view(selector, text),
})

const routes = [
  {name: 'Start', hash: '#!/', roles: everyRole('.navbar')},
  {name: 'Pages', hash: '#!/pages', roles: everyRole('h1', 'Pages')},
  {
    name: 'Page editor',
    hash: '#!/pages/edit/index.html',
    roles: {
      admin: view('h1', 'Edit page'),
      editor: view('h1', 'Edit page'),
      viewer: view('h1', 'View page'),
    },
  },
  {name: 'Inline editor', hash: '#!/page-edit', roles: everyRole('.dialog', 'Save')},
  {
    name: 'Add page',
    hash: '#!/page-add',
    roles: {admin: view('h2', 'Add page'), editor: view('h2', 'Add page')},
  },
  {
    name: 'Copy page',
    hash: '#!/page-copy',
    roles: {admin: view('h2', 'Copy page'), editor: view('h2', 'Copy page')},
  },
  {
    name: 'Delete page',
    hash: '#!/page-delete',
    roles: {admin: view('h2', 'Delete page'), editor: view('h2', 'Delete page')},
  },
  {name: 'Jump to page', hash: '#!/page-goto', roles: everyRole('h2', 'Jump to page')},
  {name: 'Media', hash: '#!/uploads', roles: everyRole('h1', 'Media')},
  {
    name: 'Upload detail',
    hash: '#!/uploads/view/e2e-smoke.png',
    roles: everyRole('h1', 'View upload'),
  },
  {name: 'Backups', hash: '#!/backups', roles: everyRole('h1', 'Backups')},
  {
    name: 'Backup detail',
    hash: '#!/backups/view/20260101_000000_e2e1_index.html',
    roles: everyRole('h1', 'View backup'),
  },
  {
    name: 'Design',
    hash: '#!/css',
    roles: {
      admin: view('h1', 'Edit CSS'),
      editor: view('h1', 'CSS'),
      viewer: view('h1', 'CSS'),
    },
  },
  {name: 'Password', hash: '#!/password', roles: everyRole('h1', 'Change password')},
  {
    name: 'Environment',
    hash: '#!/system/env',
    roles: {admin: view('h1', 'System environment')},
  },
  {
    name: 'PHP error log',
    hash: '#!/system/log',
    roles: {admin: view('h1', 'PHP error log')},
  },
  {
    name: 'PHP info',
    hash: '#!/system/php-info',
    roles: {admin: view('h1', 'PHP info')},
  },
  {name: 'About', hash: '#!/system/about', roles: everyRole('h1', 'About')},
  {name: 'Logout', hash: '#!/logout', roles: everyRole('h2', 'Logged out')},
]

const login = async (page, role) => {
  await page.goto('/jaco/#!/login')
  await page.fill('#username', role)
  await page.fill('#password', passwords[role])
  await page.locator('form button').click()
  await expect(page.locator('.navbar')).toBeVisible()
}

test('login page renders', {tag: '@smoke'}, async ({page}) => {
  await page.goto('/jaco/#!/login')
  await expect(page.locator('#username')).toBeVisible()
})

for (const role of Object.keys(passwords)) {
  for (const route of routes) {
    const expected = route.roles[role]
    const slug = `${role}-${route.name.toLowerCase().replaceAll(' ', '-')}`

    if (!expected) {
      test(`${role} is redirected from ${route.name}`, {tag: '@smoke'}, async ({page}) => {
        await login(page, role)
        await page.goto(`/jaco/?e2e=${slug}${route.hash}`)
        await expect(page).toHaveURL(/#!\/?$/)
      })
      continue
    }

    test(`${role} can open ${route.name}`, {tag: '@smoke'}, async ({page}) => {
      await login(page, role)
      await page.goto(`/jaco/?e2e=${slug}${route.hash}`)

      const target = page.locator(expected.selector).first()
      await expect(target).toBeVisible()
      if (expected.text) {
        await expect(target).toContainText(expected.text)
      }
    })
  }
}
