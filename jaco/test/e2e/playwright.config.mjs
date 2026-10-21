import {defineConfig} from '@playwright/test'
import {dirname, resolve} from 'node:path'
import {fileURLToPath} from 'node:url'

const settingsFile = resolve(dirname(fileURLToPath(import.meta.url)), 'settings.php')

export default defineConfig({
  testDir: '.',
  testMatch: '**/*.spec.mjs',
  fullyParallel: false,
  workers: 1,
  timeout: 30000,
  expect: {
    timeout: 10000,
  },
  reporter: [['list'], ['html', {open: 'never'}]],
  use: {
    baseURL: 'http://127.0.0.1:8899',
    channel: 'chrome',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  webServer: {
    command: 'php -S 127.0.0.1:8899 -t ../../..',
    url: 'http://127.0.0.1:8899/jaco/',
    reuseExistingServer: !process.env.CI,
    timeout: 30000,
    env: {
      ...process.env,
      JACO_SETTINGS_FILE: settingsFile,
    },
  },
})
