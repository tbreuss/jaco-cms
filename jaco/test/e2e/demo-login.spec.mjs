import {test, expect} from '@playwright/test'

test('the demo link prefills the public viewer credentials', {tag: '@smoke'}, async ({page}) => {
  await page.goto('/jaco/#!/login?demo=1')

  await expect(page.locator('#username')).toHaveValue('demo')
  await expect(page.locator('#password')).toHaveValue('demo1234')
})
