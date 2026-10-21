import {copyFileSync, mkdirSync, rmSync, writeFileSync} from 'node:fs'
import {dirname, join, resolve} from 'node:path'
import {fileURLToPath} from 'node:url'

const root = resolve(dirname(fileURLToPath(import.meta.url)), '../../..')
const uploadFile = join(root, 'upload', 'e2e-smoke.png')
const backupFile = join(root, 'jaco', '_backup', '20260101_000000_e2e1_index.html')

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
  copyFileSync(join(root, 'index.html'), backupFile)

  // Playwright runs this after the suite if it is returned here.
  return async () => {
    rmSync(uploadFile, {force: true})
    rmSync(backupFile, {force: true})
  }
}
