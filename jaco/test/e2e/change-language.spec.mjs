import {test, expect} from '@playwright/test'

// The backend tests cover the file write and the token endpoint; this test
// follows the real browser flow, because the page has to swap its labels from
// the freshly issued token without reloading the admin shell.

const languageClaim = (page) => page.evaluate(() => {
  const token = sessionStorage.getItem('AUTHORIZATION_TOKEN')
  if (!token) return null
  const base64 = token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')
  const padded = base64.padEnd(Math.ceil(base64.length / 4) * 4, '=')
  return JSON.parse(atob(padded)).language ?? ''
})

const openLanguageView = async (page) => {
  await page.getByRole('button', {name: 'Account'}).click()
  await page.getByRole('menuitem', {name: 'Change language'}).click()
  await expect(page).toHaveURL(/\/jaco\/#!\/language$/)
  await expect(page.locator('#language')).toBeEnabled()
}

const chooseAndSave = async (page, code, submitLabel) => {
  await page.selectOption('#language', code)
  await page.getByRole('button', {name: submitLabel}).click()
}

test('an account switches its interface language and keeps it after a reload', async ({page}) => {
  let token = null

  try {
    await page.goto('/jaco/#!/login')
    await page.fill('#username', 'admin')
    await page.fill('#password', 'test-password')
    await page.locator('form button').click()
    await expect(page).toHaveURL(/\/jaco\/#!\/$/)

    // the account starts on the installation default, English
    await openLanguageView(page)
    await expect(page.locator('#language')).toHaveValue('en')
    token = await page.evaluate(() => sessionStorage.getItem('AUTHORIZATION_TOKEN'))

    await chooseAndSave(page, 'de', 'Change language')

    // the page re-renders in German immediately, without a reload
    await expect(page.locator('h1')).toHaveText('Sprache ändern')
    await expect(page.locator('.m-notification-text')).toHaveText('Die Sprache wurde geändert.')
    await expect.poll(() => languageClaim(page)).toBe('de')

    // the preference lives in the account, so a fresh page load starts German
    // from the token the server issued before
    await page.reload()
    await expect(page.locator('h1')).toHaveText('Sprache ändern')
    await expect(page.locator('#language')).toHaveValue('de')
    await expect.poll(() => languageClaim(page)).toBe('de')

    // and back: the same form switches the session to English again
    await chooseAndSave(page, 'en', 'Sprache ändern')
    await expect(page.locator('h1')).toHaveText('Change language')
    await expect(page.locator('.m-notification-text')).toHaveText('The language was changed.')
    await expect.poll(() => languageClaim(page)).toBe('en')

    token = await page.evaluate(() => sessionStorage.getItem('AUTHORIZATION_TOKEN'))
  } finally {
    // best effort: leave the shared test configuration on English even when an
    // assertion above failed, so the other specs keep their English labels
    if (token) {
      await page.request.post('/jaco/api.php?action=system/change-language', {
        headers: {Authorization: 'Bearer ' + token},
        data: {language: 'en'},
      }).catch(() => {})
    }
  }
})
