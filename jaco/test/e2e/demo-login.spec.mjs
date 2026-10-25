import {test, expect} from '@playwright/test'

test('the demo link prefills the public viewer credentials', {tag: '@smoke'}, async ({page}) => {
  await page.goto('/jaco/#!/login?demo=1')

  await expect(page.locator('#username')).toHaveValue('demo')
  await expect(page.locator('#password')).toHaveValue('demo1234')
})

test('the demo link inside the preview does not nest the admin', async ({page}) => {
  // The preview holds the start page, whose demo link points back into the admin.
  // Followed inside the frame, the shell loads into itself: two navbars, two demo
  // banners. The link leaves the frame (target="_top"), and the shell refuses to
  // render inside one as a second line of defence.
  await page.goto('/jaco/#!/login')
  await page.fill('#username', 'readonly')
  await page.fill('#password', 'test-password')
  await page.locator('form button').click()
  await expect(page).toHaveURL(/\/jaco\/#!\/$/)
  await expect(page.locator('#iframe')).toBeVisible()

  const preview = page.frames().find((frame) => frame !== page.mainFrame())
  expect(preview, 'the preview frame is loaded').toBeTruthy()

  const target = await preview.evaluate(() => {
    const links = [...document.querySelectorAll('a')]
    const demo = links.find((link) => link.textContent.trim() === 'Open the live demo')
    return demo ? demo.getAttribute('target') : null
  })
  expect(target, 'the demo link leaves the frame').toBe('_top')

  // the shell itself breaks out of any frame it is loaded into
  const guard = await page.evaluate(async () => {
    const response = await fetch('/jaco/index.html')
    const html = await response.text()
    return html.includes('window.top !== window.self')
  })
  expect(guard, 'the admin shell refuses to render inside a frame').toBe(true)
})
