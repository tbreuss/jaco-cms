import {test, expect} from '@playwright/test'

test('admin can edit an existing video URL in the inline editor', async ({page}) => {
  await page.goto('/jaco/#!/login')
  await page.fill('#username', 'admin')
  await page.fill('#password', 'test-password')
  await page.locator('form button').click()
  await expect(page).toHaveURL(/\/jaco\/#!\/$/)

  await page.evaluate(() => {
    document.getElementById('iframe').src = '/elements.html'
  })

  const frame = page.frameLocator('#iframe')
  await expect(frame.locator('h1')).toContainText('Editable content elements')
  await page.evaluate(() => window.m.route.set('/page-edit'))
  await expect(frame.locator('.jaco-content-add-button').first()).toBeVisible()

  const richText = frame.locator('div[data-jaco-block]').filter({hasText: 'A video is added'})
  await richText.locator('p').first().click()
  const video = frame.locator('iframe.ql-video').first()
  await expect(video).toBeVisible()
  await video.scrollIntoViewIfNeeded()
  const box = await video.boundingBox()
  expect(box).not.toBeNull()
  // Iframes ignore pointer-events in the editor; click the editor area over it.
  await page.mouse.click(box.x + box.width / 2, box.y + Math.min(40, box.height / 2))

  await expect(frame.locator('#editableVideo')).toBeVisible()
  const input = frame.locator('#editableVideo input')
  await expect(input).toHaveValue(/youtube/)
  await input.fill('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
  await expect(video).toHaveAttribute('src', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
  await frame.getByRole('button', {name: 'Close'}).click()
  await expect(frame.locator('#editableVideo')).toHaveCount(0)
})
