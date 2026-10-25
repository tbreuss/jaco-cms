import {copyFileSync, mkdirSync, rmSync, writeFileSync} from 'node:fs'
import {dirname, join, resolve} from 'node:path'
import {fileURLToPath} from 'node:url'

const root = resolve(dirname(fileURLToPath(import.meta.url)), '../../..')
const uploadFile = join(root, 'media', 'e2e-smoke.png')
const backupFile = join(root, 'jaco-data', 'backups', '20260101_000000_e2e1_index.html')
const loginHistoryFile = join(root, 'jaco-data', 'runtime', 'log', 'loginhistory.json')
const siteTemplateFile = join(root, 'jaco-data', 'templates', 'site', 'e2e-footer.html')
const siteTemplatePage = join(root, 'e2e-template-page.html')

// A valid 1×1 PNG; the upload detail view only needs a real file with an
// allowed extension.
const png = Buffer.from(
  'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=',
  'base64',
)

export default async function globalSetup() {
  mkdirSync(dirname(uploadFile), {recursive: true})
  mkdirSync(dirname(backupFile), {recursive: true})
  writeFileSync(uploadFile, png)
  mkdirSync(dirname(siteTemplateFile), {recursive: true})
  writeFileSync(siteTemplateFile, '<footer data-jaco-template="e2e-footer">E2E footer</footer>')
  writeFileSync(siteTemplatePage, '<!DOCTYPE html><html><body><footer data-jaco-template="e2e-footer">Before</footer></body></html>')
  copyFileSync(join(root, 'index.html'), backupFile)
  rmSync(loginHistoryFile, {force: true})

  // Playwright runs this after the suite if it is returned here.
  return async () => {
    rmSync(uploadFile, {force: true})
    rmSync(backupFile, {force: true})
    rmSync(loginHistoryFile, {force: true})
    rmSync(siteTemplateFile, {force: true})
    rmSync(siteTemplatePage, {force: true})
  }
}
