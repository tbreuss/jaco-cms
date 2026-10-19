#!/usr/bin/env node
/**
 * Runs the QUnit page in a headless Chrome and reports the result.
 *
 * There are no npm dependencies: the devtools protocol is spoken through the built in
 * WebSocket (node >= 22) and the browser is the Chrome that is already installed.
 *
 *   node jaco/test/js/runner.mjs [--url=...] [--timeout=30000] [--chrome=/path/to/chrome]
 */
import {spawn, spawnSync} from 'node:child_process'
import {existsSync, mkdtempSync, rmSync} from 'node:fs'
import {createServer} from 'node:net'
import {tmpdir} from 'node:os'
import {join} from 'node:path'

const options = Object.fromEntries(
  process.argv.slice(2).map((argument) => {
    const [key, ...value] = argument.replace(/^--/, '').split('=')
    return [key, value.length > 0 ? value.join('=') : 'true']
  }),
)

const url = options.url ?? 'http://127.0.0.1:8899/jaco/test/js/index.html'
const timeoutMs = Number(options.timeout ?? 30000)

const findChrome = () => {
  const candidates = [
    options.chrome,
    process.env.CHROME_BIN,
    '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    '/Applications/Chromium.app/Contents/MacOS/Chromium',
    'google-chrome',
    'google-chrome-stable',
    'chromium',
    'chromium-browser',
  ].filter(Boolean)

  for (const candidate of candidates) {
    if (candidate.includes('/')) {
      if (existsSync(candidate)) return candidate
    } else if (spawnSync('which', [candidate]).status === 0) {
      return candidate
    }
  }

  return null
}

const freePort = () => new Promise((resolve, reject) => {
  const server = createServer()
  server.on('error', reject)
  server.listen(0, '127.0.0.1', () => {
    const {port} = server.address()
    server.close(() => resolve(port))
  })
})

const waitForDevtools = async (port) => {
  const deadline = Date.now() + 20000
  while (Date.now() < deadline) {
    try {
      const response = await fetch(`http://127.0.0.1:${port}/json/version`)
      if (response.ok) return
    } catch {
      // not up yet
    }
    await new Promise((resolve) => setTimeout(resolve, 200))
  }
  throw new Error('the devtools endpoint did not come up')
}

const waitForServer = async (url) => {
  const deadline = Date.now() + 20000
  while (Date.now() < deadline) {
    try {
      const response = await fetch(url)
      if (response.ok) return
    } catch {
      // not up yet
    }
    await new Promise((resolve) => setTimeout(resolve, 250))
  }
  throw new Error(`the server did not serve ${url}`)
}

const chrome = findChrome()
if (chrome === null) {
  console.error('No Chrome found. Set CHROME_BIN or pass --chrome=/path/to/chrome.')
  process.exit(2)
}

await waitForServer(url)

// The suite logs in with a deliberately wrong password ('login against the real
// api' in tests.js) and the api counts every failure towards the login throttle:
// three attempts block the address for ten minutes. Without this reset the next
// run inside that window fails on an error the test never triggered, and the
// red run says nothing about the change under test. The file holds failed
// attempts only and is gitignored, so clearing it is safe.
const loginHistory = join(import.meta.dirname, '../../runtime/log/loginhistory.json')
if (existsSync(loginHistory)) {
  rmSync(loginHistory, {force: true})
  console.log('Reset the login throttle (runtime/log/loginhistory.json)')
}

const port = await freePort()
const profile = mkdtempSync(join(tmpdir(), 'jaco-qunit-'))
const browser = spawn(chrome, [
  '--headless=new',
  '--no-sandbox',
  '--disable-gpu',
  '--disable-dev-shm-usage',
  '--no-first-run',
  '--no-default-browser-check',
  `--user-data-dir=${profile}`,
  `--remote-debugging-port=${port}`,
  'about:blank',
], {stdio: 'ignore'})

let exitCode = 0

try {
  await waitForDevtools(port)

  const targets = await (await fetch(`http://127.0.0.1:${port}/json/list`)).json()
  const page = targets.find((target) => target.type === 'page')
  if (!page) {
    throw new Error('the browser has no page target')
  }

  const socket = new WebSocket(page.webSocketDebuggerUrl)
  await new Promise((resolve, reject) => {
    socket.addEventListener('open', resolve, {once: true})
    socket.addEventListener('error', reject, {once: true})
  })

  let nextId = 0
  const pending = new Map()
  socket.addEventListener('message', (event) => {
    const message = JSON.parse(event.data)
    if (message.id && pending.has(message.id)) {
      pending.get(message.id)(message)
      pending.delete(message.id)
    }
  })
  const send = (method, params = {}) => new Promise((resolve) => {
    const id = ++nextId
    pending.set(id, resolve)
    socket.send(JSON.stringify({id, method, params}))
  })
  const evaluate = async (expression) => {
    const response = await send('Runtime.evaluate', {expression, returnByValue: true})
    return response.result?.result?.value
  }

  await send('Page.enable')
  await send('Runtime.enable')
  await send('Page.navigate', {url})

  const deadline = Date.now() + timeoutMs
  let result = null
  while (Date.now() < deadline) {
    result = await evaluate('window.__qunitResult ?? null')
    if (result) {
      break
    }
    await new Promise((resolve) => setTimeout(resolve, 200))
  }

  socket.close()

  if (!result) {
    console.error(`The qunit run did not finish within ${timeoutMs} ms (${url}).`)
    exitCode = 1
  } else {
    const {total, passed, failed, skipped} = result.counts
    console.log(`QUnit: ${passed}/${total} passed, ${failed} failed, ${skipped} skipped (${url})`)

    for (const failure of result.failures) {
      console.error(`  failed: ${failure}`)
    }

    exitCode = failed > 0 ? 1 : 0
  }
} catch (error) {
  console.error(`Runner error: ${error.message}`)
  exitCode = 1
} finally {
  browser.kill('SIGTERM')
  await new Promise((resolve) => {
    if (browser.exitCode !== null) {
      resolve()
      return
    }
    browser.once('exit', resolve)
    setTimeout(resolve, 3000).unref()
  })

  try {
    rmSync(profile, {recursive: true, force: true})
  } catch {
    // a temporary profile that cannot be removed is not a test failure
  }
}

process.exit(exitCode)
