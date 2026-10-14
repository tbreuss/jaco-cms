import {Input} from '/jaco/frontend/component/Input.js'
import {InputPassword} from '/jaco/frontend/component/InputPassword.js'
import {InputText} from '/jaco/frontend/component/InputText.js'
import {FormErrors, errorFor} from '/jaco/frontend/component/FormErrors.js'
import {ButtonPrimary} from '/jaco/frontend/component/ButtonPrimary.js'
import {Icon} from '/jaco/frontend/component/Icon.js'
import {Select} from '/jaco/frontend/component/Select.js'
import {LoginView} from '/jaco/frontend/view/LoginView.js'
import {PageEditModel} from '/jaco/frontend/model/PageEditModel.js'
import {ViewUploadView} from '/jaco/frontend/view/upload/ViewUploadView.js'
import {UploadListModel} from '/jaco/frontend/model/UploadListModel.js'
import {UploadListView} from '/jaco/frontend/view/upload/UploadListView.js'
import {api} from '/jaco/frontend/service/api.js'
import {tError} from '/jaco/frontend/model/i18n.js'
import {isSaveShortcut, isSaveAndCloseShortcut, bindSaveShortcut, saveShortcutLabel, saveAndCloseShortcutLabel} from '/jaco/frontend/lib/shortcuts.js'
import {getTheme, setTheme, applyTheme} from '/jaco/frontend/lib/theme.js'
import {formatDateTimeCompact, formatDateTimeFull} from '/jaco/frontend/lib/format.js'
import {state} from '/jaco/frontend/state/state.js'
const fixture = () => document.getElementById('qunit-fixture')

QUnit.module('components')

QUnit.test('Input takes the focus when autofocus is set', (assert) => {
  m.render(fixture(), m(Input, {id: 'focus-me', autofocus: true, oninput: () => {}}))

  assert.strictEqual(document.activeElement.id, 'focus-me')
})

QUnit.test('Input leaves the focus alone without autofocus', (assert) => {
  m.render(fixture(), m(Input, {id: 'no-focus', oninput: () => {}}))

  assert.notStrictEqual(document.activeElement.id, 'no-focus')
})

QUnit.test('InputText links its label to the input', (assert) => {
  const attrs = {id: 'username', label: 'Username', value: '', wrap: 'div.row', oninput: () => {}}
  m.render(fixture(), m(InputText, attrs))

  assert.strictEqual(fixture().querySelector('label').getAttribute('for'), 'username')
  assert.ok(fixture().querySelector('input#username'), 'the input is rendered')
})

QUnit.test('InputText renders its label without a wrapper as well', (assert) => {
  m.render(fixture(), m(InputText, {id: 'bare', label: 'Bare', value: '', oninput: () => {}}))

  assert.ok(fixture().querySelector('label'), 'the label is rendered')
  assert.ok(fixture().querySelector('input#bare'), 'the input is rendered')
})

QUnit.test('InputPassword renders its label without a wrapper as well', (assert) => {
  m.render(fixture(), m(InputPassword, {id: 'bare-password', label: 'Bare', value: '', oninput: () => {}}))

  assert.ok(fixture().querySelector('label'), 'the label is rendered')
  assert.strictEqual(fixture().querySelector('input').getAttribute('type'), 'password')
})

QUnit.test('Input reports what was typed', (assert) => {
  let reported = null
  m.render(fixture(), m(Input, {id: 'typed', oninput: (value) => { reported = value }}))

  const input = fixture().querySelector('input')
  input.value = 'hallo'
  input.dispatchEvent(new Event('input', {bubbles: true}))

  assert.strictEqual(reported, 'hallo')
})

QUnit.test('InputText shows its error with accessible wiring', (assert) => {
  m.render(fixture(), m(InputText, {id: 'fileName', label: 'Filename', value: '', wrap: 'div.row', error: 'Is required.', oninput: () => {}}))

  const input = fixture().querySelector('input#fileName')
  assert.strictEqual(input.getAttribute('aria-invalid'), 'true', 'field is marked invalid')
  assert.strictEqual(input.getAttribute('aria-describedby'), 'fileName-error', 'message is linked')
  const message = fixture().querySelector('#fileName-error')
  assert.ok(message, 'error element is rendered')
  assert.ok(message.classList.contains('field-error'), 'error styling hook')
  assert.strictEqual(message.textContent, 'Is required.')
})

QUnit.test('InputText without error has no error wiring', (assert) => {
  m.render(fixture(), m(InputText, {id: 'clean', label: 'Clean', value: '', oninput: () => {}}))

  assert.strictEqual(fixture().querySelector('input').getAttribute('aria-invalid'), null, 'no invalid flag')
  assert.strictEqual(fixture().querySelector('.field-error'), null, 'no error element')
})

QUnit.test('InputPassword shows its error', (assert) => {
  m.render(fixture(), m(InputPassword, {id: 'password', label: 'Password', value: '', wrap: 'div.row', error: 'Too short.', oninput: () => {}}))

  const input = fixture().querySelector('input#password')
  assert.strictEqual(input.getAttribute('type'), 'password', 'stays a password field')
  assert.strictEqual(input.getAttribute('aria-invalid'), 'true', 'field is marked invalid')
  assert.strictEqual(fixture().querySelector('#password-error').textContent, 'Too short.')
})

QUnit.test('Select shows its error', (assert) => {
  const options = [{value: 'a.css', label: 'a.css'}]
  m.render(fixture(), m(Select, {id: 'pageTemplate', label: 'Template', value: '', options, prompt: 'Select an option', error: 'Pick one.', onchange: () => {}}))

  const select = fixture().querySelector('select#pageTemplate')
  assert.strictEqual(select.getAttribute('aria-invalid'), 'true', 'field is marked invalid')
  assert.strictEqual(select.getAttribute('aria-describedby'), 'pageTemplate-error', 'message is linked')
  assert.strictEqual(fixture().querySelector('#pageTemplate-error').textContent, 'Pick one.')
})

QUnit.test('errorFor picks the translated detail by pointer', (assert) => {
  const errors = [
    {source: {pointer: 'fileName'}, title: 'Validation Error', detail: 'Is required.'},
    {source: {pointer: ''}, title: 'Validation Error', detail: 'Authentication failed.'},
  ]

  assert.strictEqual(errorFor(errors, 'fileName'), tError('Is required.'), 'field error found')
  assert.strictEqual(errorFor(errors, 'other'), undefined, 'no error for other fields')
  assert.strictEqual(errorFor([], 'fileName'), undefined, 'no errors at all')
})

QUnit.test('FormErrors renders nothing without errors and the details with errors', (assert) => {
  m.render(fixture(), m(FormErrors, {errors: []}))
  assert.strictEqual(fixture().querySelector('.form-errors'), null)

  const errors = [{source: {pointer: ''}, title: 'Validation Error', detail: 'Authentication failed.'}]
  m.render(fixture(), m(FormErrors, {errors}))

  assert.ok(fixture().textContent.includes(tError('Authentication failed.')), 'the translated detail is rendered')
})

QUnit.test('FormErrors lists multiple errors with bullets', (assert) => {
  const errors = [
    {source: {pointer: 'fileName'}, title: 'Validation Error', detail: 'Is required.'},
    {source: {pointer: 'pageTitle'}, title: 'Validation Error', detail: 'Is required.'},
  ]
  m.render(fixture(), m(FormErrors, {errors}))

  const list = fixture().querySelector('ul.form-errors')
  assert.ok(list, 'a list is rendered')
  assert.strictEqual(list.querySelectorAll('li').length, 2, 'one item per error')
})

QUnit.test('Select follows the value attribute', (assert) => {
  const options = [{value: 'a.css', label: 'a.css'}, {value: 'b.css', label: 'b.css'}]
  m.render(fixture(), m(Select, {id: 'css', prompt: 'Select an option', options, value: '', onchange: () => {}}))
  assert.strictEqual(fixture().querySelector('select').value, '', 'prompt is shown')

  m.render(fixture(), m(Select, {id: 'css', prompt: 'Select an option', options, value: 'b.css', onchange: () => {}}))
  assert.strictEqual(fixture().querySelector('select').value, 'b.css', 'selection follows the state')
})

QUnit.test('Select shows the value when options arrive later', (assert) => {
  const render = (options, value) => {
    m.render(fixture(), m(Select, {id: 'late', prompt: 'Select an option', options, value, onchange: () => {}}))
  }
  render([], '')
  assert.strictEqual(fixture().querySelector('select').value, '', 'prompt is shown first')

  render([{value: 'style.css', label: 'style.css'}], 'style.css')
  assert.strictEqual(fixture().querySelector('select').value, 'style.css', 'loaded file is shown, not the prompt')
})

QUnit.test('Select falls back to the prompt when the selection gets lost', (assert) => {
  const render = (options, value) => {
    m.render(fixture(), m(Select, {id: 'lost', prompt: 'Select an option', options, value, onchange: () => {}}))
  }
  render([{value: 'a.css', label: 'a.css'}], 'a.css')
  assert.strictEqual(fixture().querySelector('select').value, 'a.css', 'option is selected first')

  // the selected option disappears while the value is stale, e.g. templates
  // reload between renders: the field must show the prompt, never blank
  render([], 'a.css')
  const select = fixture().querySelector('select')
  assert.strictEqual(select.selectedIndex, 0, 'prompt is selected again')
  assert.strictEqual(select.value, '', 'empty value shown')
})

QUnit.module('PageEditModel')

const stubRequest = (respond) => {
  const calls = []
  const original = m.request
  m.request = (options) => {
    calls.push(options)
    return respond()
  }

  return {
    calls,
    restore: () => { m.request = original },
  }
}

QUnit.test('save sends the document unchanged', (assert) => {
  const stub = stubRequest(() => Promise.resolve({}))
  const content = '<html><head><title>Alt</title></head><body><p>x</p></body></html>'

  try {
    PageEditModel.item = {file: 'x.html', content, title: 'Alt'}
    PageEditModel.save()

    assert.strictEqual(stub.calls.length, 1)
    assert.ok(stub.calls[0].url.includes('action=page/update'), 'the document path is used')
    assert.strictEqual(stub.calls[0].body.html, content, 'the html is not rewritten')
    assert.strictEqual(stub.calls[0].body.pageTitle, undefined, 'the title is part of the document')
  } finally {
    stub.restore()
  }
})

QUnit.module('api service error handling')

QUnit.test('network failure shows a toast instead of crashing', async (assert) => {
  const stub = stubRequest(() => Promise.reject({code: 0}))
  const before = state.list.length

  try {
    await api.get('page/list').catch(() => {})

    assert.ok(true, 'no TypeError on missing response body')
    assert.ok(state.list.length > before, 'a danger message was added')
  } finally {
    stub.restore()
  }
})

QUnit.module('login against the real api')

QUnit.test('the form sends one request and shows the message from the api', (assert) => {
  const done = assert.async()
  const calls = []
  const originalOpen = XMLHttpRequest.prototype.open
  XMLHttpRequest.prototype.open = function (method, url) {
    if (String(url).includes('action=login')) {
      calls.push(String(url))
    }
    return originalOpen.apply(this, arguments)
  }

  const host = document.createElement('div')
  fixture().appendChild(host)
  m.mount(host, LoginView)

  const finish = (message) => {
    XMLHttpRequest.prototype.open = originalOpen
    m.mount(host, null)
    if (message) {
      assert.ok(false, message)
    }
    done()
  }

  const form = fixture().querySelector('form')
  assert.ok(form, 'the login view renders a form')
  if (!form) {
    finish('no form to submit')
    return
  }

  const set = (id, value) => {
    const input = fixture().querySelector('#' + id)
    input.value = value
    input.dispatchEvent(new Event('input', {bubbles: true}))
  }
  set('username', 'qunit-user')
  set('password', 'definitely-wrong')

  form.requestSubmit()

  const deadline = Date.now() + 5000
  const poll = () => {
    const errors = fixture().querySelector('.form-errors')
    if (errors) {
      assert.strictEqual(calls.length, 1, 'exactly one login request')
      assert.ok(errors.textContent.includes(tError('Authentication failed.')), 'the api message is shown translated')
      assert.strictEqual(fixture().querySelector('#username').value, '', 'username is cleared')
      assert.strictEqual(fixture().querySelector('#password').value, '', 'password is cleared')
      assert.strictEqual(document.activeElement.id, 'username', 'focus is back on username')
      finish(null)
      return
    }
    if (Date.now() > deadline) {
      finish('the error from the api was not rendered in time')
      return
    }
    setTimeout(poll, 50)
  }
  setTimeout(poll, 50)
})

QUnit.module('ViewUploadView preview', {
  beforeEach: () => {
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', 'qunit-token')
    UploadListModel.load = () => Promise.resolve()
    const app = document.createElement('div')
    app.id = 'app'
    document.body.appendChild(app)
  },
  afterEach: () => {
    m.render(fixture(), null)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    UploadListModel.items = []
    document.getElementById('app')?.remove()
  }
})

const renderUploadView = (file) => {
  m.render(fixture(), m(ViewUploadView, {file}))
}

QUnit.test('images render an inline img preview', (assert) => {
  UploadListModel.items = [{file: 'pic.png', extension: 'png', size: 42, created: 1700000000}]
  renderUploadView('pic.png')

  const img = fixture().querySelector('img')
  assert.ok(img, 'an img is rendered')
  assert.strictEqual(img.getAttribute('src'), '/pic.png')
})

QUnit.test('pdfs render an embedded preview', (assert) => {
  UploadListModel.items = [{file: 'doc.pdf', extension: 'pdf', size: 42, created: 1700000000}]
  renderUploadView('doc.pdf')

  const embed = fixture().querySelector('embed[type="application/pdf"]')
  assert.ok(embed, 'an embed is rendered')
  assert.strictEqual(embed.getAttribute('src'), '/doc.pdf')
})

QUnit.test('other files render a link', (assert) => {
  UploadListModel.items = [{file: 'a.svg', extension: 'svg', size: 42, created: 1700000000}]
  renderUploadView('a.svg')

  assert.ok(fixture().querySelector('img'), 'svg counts as image')
})

QUnit.test('unknown files render a link', (assert) => {
  UploadListModel.items = [{file: 'archive.zip', extension: 'zip', size: 42, created: 1700000000}]
  renderUploadView('archive.zip')

  const link = fixture().querySelector('a[target="_blank"]')
  assert.ok(link, 'a link is rendered')
  assert.strictEqual(link.getAttribute('href'), '/archive.zip')
})

QUnit.test('metadata is shown in disabled inputs', (assert) => {
  UploadListModel.items = [{file: 'pic.png', extension: 'png', size: 1234, created: 1700000000}]
  renderUploadView('pic.png')

  const text = fixture().textContent
  assert.ok(text.includes('pic.png'), 'the filename is shown')
  const values = [...fixture().querySelectorAll('input')].map((el) => el.value)
  assert.ok(values.some((v) => /1\D234/.test(v)), 'the size is formatted, got: ' + values.join(', '))
})

QUnit.module('UploadListView size column', {
  beforeEach: () => {
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', 'qunit-token')
    UploadListModel.load = () => Promise.resolve()
    const app = document.createElement('div')
    app.id = 'app'
    document.body.appendChild(app)
  },
  afterEach: () => {
    m.render(fixture(), null)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    UploadListModel.items = []
    document.getElementById('app')?.remove()
  }
})

QUnit.test('size shows thousands separators and is right aligned', (assert) => {
  UploadListModel.items = [{file: 'big.png', extension: 'png', size: 1234567, created: 1700000000}]
  UploadListModel.isLoading = false
  m.render(fixture(), m(UploadListView))

  const cells = [...fixture().querySelectorAll('tbody td')]
  const sizeCell = cells.find((td) => td.textContent.includes('234'))
  assert.ok(sizeCell, 'the size cell is rendered')
  assert.ok(/^1\D234\D567$/.test(sizeCell.textContent), 'thousands separators, got: ' + sizeCell.textContent)
  assert.ok(sizeCell.classList.contains('text-right'), 'cell right aligned')

  // the labels are translated, so address the column by its position: file, type, size, created, actions
  const heads = [...fixture().querySelectorAll('thead th')]
  assert.strictEqual(heads.length, 5, 'five columns')
  assert.ok(heads[2].classList.contains('text-right'), 'size header right aligned')
  assert.strictEqual(
    heads.filter((th) => th.classList.contains('text-right')).length,
    1,
    'only the size column is right aligned'
  )
  const head = fixture().querySelector('.content > div')
  const style = window.getComputedStyle(head)
  assert.strictEqual(style.display, 'flex', 'header row is flex')
  assert.strictEqual(style.justifyContent, 'space-between', 'button is right aligned')
})

QUnit.test('created cell shows the compact time with the full time as tooltip', (assert) => {
  UploadListModel.items = [{file: 'a.png', extension: 'png', size: 1, created: 1700000000}]
  UploadListModel.isLoading = false
  m.render(fixture(), m(UploadListView))

  const cell = [...fixture().querySelectorAll('tbody td')].find((td) => td.hasAttribute('title'))
  assert.ok(cell, 'a cell with a tooltip is rendered')
  assert.ok(/^\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}:\d{2}$/.test(cell.getAttribute('title')), 'tooltip holds the full time')
  assert.ok(/^\d{2}\.\d{2}\.\d{2} \d{2}:\d{2}$/.test(cell.textContent), 'cell shows the compact time')
  assert.ok(cell.classList.contains('text-nowrap'), 'no wrapping inside the cell')
})

QUnit.module('datetime format')

QUnit.test('full and compact share day, month, year suffix and time', (assert) => {
  const full = formatDateTimeFull(1700000000)
  const compact = formatDateTimeCompact(1700000000)

  assert.ok(/^\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}:\d{2}$/.test(full), `full shape, got ${full}`)
  assert.ok(/^\d{2}\.\d{2}\.\d{2} \d{2}:\d{2}$/.test(compact), `compact shape, got ${compact}`)
  assert.strictEqual(compact.slice(0, 6), full.slice(0, 6), 'day and month match')
  assert.strictEqual(compact.slice(6, 8), full.slice(8, 10), 'two-digit year matches the full year')
  assert.strictEqual(compact.slice(-5), full.slice(11, 16), 'hours and minutes match, seconds dropped')
  assert.strictEqual(full.length, 19, 'full length')
  assert.strictEqual(compact.length, 14, 'compact length')
})

QUnit.test('missing timestamps format as empty strings', (assert) => {
  assert.strictEqual(formatDateTimeFull(null), '', 'null full')
  assert.strictEqual(formatDateTimeFull(undefined), '', 'undefined full')
  assert.strictEqual(formatDateTimeCompact(null), '', 'null compact')
  assert.strictEqual(formatDateTimeCompact(undefined), '', 'undefined compact')
})

QUnit.module('content lists', {
  beforeEach: () => {
    m.render(fixture(), null)
  },
  afterEach: () => {
    m.render(fixture(), null)
  }
})

const contentListHtml = (pos = 'top') =>
  `<div data-jaco-content-list data-jaco-fragment="news.html" data-jaco-title="Add news" data-jaco-pos="${pos}">` +
  '<div data-jaco-content-item><h2>Existing</h2></div>' +
  '</div>'

const stubFetcher = (html = '<p>Fresh</p>') => () => Promise.resolve(html)

QUnit.test('posOf reads top, bottom and defaults anything else to any', (assert) => {
  const lists = window.JacoContentLists
  const list = (pos) => {
    const el = document.createElement('div')
    if (pos !== null) {
      el.setAttribute('data-jaco-pos', pos)
    }
    return el
  }
  assert.strictEqual(lists.posOf(list('top')), 'top')
  assert.strictEqual(lists.posOf(list('bottom')), 'bottom')
  assert.strictEqual(lists.posOf(list('any')), 'any')
  assert.strictEqual(lists.posOf(list('draggable')), 'draggable')
  assert.strictEqual(lists.posOf(list(null)), 'any', 'missing pos follows')
})

QUnit.test('top inserts fetched fragments at the beginning', (assert) => {
  const done = assert.async()
  m.render(fixture(), m.trust(contentListHtml('top')))
  const list = fixture().querySelector('[data-jaco-content-list]')

  window.JacoContentLists.initContentLists(fixture(), stubFetcher())

  const button = fixture().querySelector('.jaco-content-add-button')
  assert.ok(button, 'an add button is rendered')
  assert.strictEqual(button.previousElementSibling, null, 'button is before the list')
  assert.ok(button.textContent.includes('Add news'), 'button uses the author title')

  button.click()
  setTimeout(() => {
    const items = [...list.querySelectorAll(':scope > [data-jaco-content-item]')]
    assert.strictEqual(items.length, 2, 'a block was added')
    assert.ok(items[0].textContent.includes('Fresh'), 'the new block is first')
    assert.ok(items[0].querySelector('.jaco-content-item-bar'), 'the new block has a delete overlay')
    done()
  }, 50)
})

QUnit.test('bottom appends fetched fragments at the end', (assert) => {
  const done = assert.async()
  m.render(fixture(), m.trust(contentListHtml('bottom')))
  const list = fixture().querySelector('[data-jaco-content-list]')

  window.JacoContentLists.initContentLists(fixture(), stubFetcher())

  const button = fixture().querySelector('.jaco-content-add-button')
  assert.strictEqual(button.nextElementSibling, null, 'button is after the list')

  button.click()
  setTimeout(() => {
    const items = [...list.querySelectorAll(':scope > [data-jaco-content-item]')]
    assert.strictEqual(items.length, 2, 'a block was added')
    assert.ok(items[1].textContent.includes('Fresh'), 'the new block is last')
    done()
  }, 50)
})

QUnit.test('any puts a button before every block and at the end', (assert) => {
  const done = assert.async()
  m.render(fixture(), m.trust(contentListHtml('any')))
  const list = fixture().querySelector('[data-jaco-content-list]')

  window.JacoContentLists.initContentLists(fixture(), stubFetcher())

  const buttons = [...fixture().querySelectorAll('.jaco-content-add-button')]
  assert.strictEqual(buttons.length, 2, 'one button per block plus one at the end')
  assert.ok(list.querySelector('.jaco-content-item-bar'), 'existing blocks get a delete overlay')

  buttons[0].click()
  setTimeout(() => {
    const items = [...list.querySelectorAll(':scope > [data-jaco-content-item]')]
    assert.strictEqual(items.length, 2, 'a block was added')
    assert.ok(items[0].textContent.includes('Fresh'), 'the new block follows the clicked button')
    done()
  }, 50)
})

QUnit.test('a failed fragment load marks the button instead of dying silently', (assert) => {
  const done = assert.async()
  m.render(fixture(), m.trust(contentListHtml('top')))

  window.JacoContentLists.initContentLists(fixture(), () => Promise.reject(new Error('nope')))

  const button = fixture().querySelector('.jaco-content-add-button')
  button.click()
  setTimeout(() => {
    assert.ok(button.classList.contains('is-error'), 'the button shows the failure')
    assert.strictEqual(
      fixture().querySelectorAll('[data-jaco-content-item]').length,
      1,
      'no block was added'
    )
    done()
  }, 50)
})

const pointerEvent = (type, x, y) => new PointerEvent(type, {
  bubbles: true,
  cancelable: true,
  clientX: x ?? 0,
  clientY: y ?? 0,
  pointerType: 'mouse',
  button: 0,
})

QUnit.test('draggable adds on top and reorders via the grip', (assert) => {
  m.render(fixture(), m.trust(
    '<div data-jaco-content-list data-jaco-fragment="news.html" data-jaco-title="Add news" data-jaco-pos="draggable">' +
    '<div data-jaco-content-item><h2>First</h2></div>' +
    '<div data-jaco-content-item><h2>Second</h2></div>' +
    '</div>'
  ))
  const list = fixture().querySelector('[data-jaco-content-list]')

  window.JacoContentLists.initContentLists(fixture(), stubFetcher())

  const button = fixture().querySelector('.jaco-content-add-button')
  assert.ok(button, 'an add button is rendered')
  assert.strictEqual(button.nextElementSibling, list, 'button is before the list like top')
  assert.ok(list.querySelector('.jaco-content-grip'), 'blocks have a grip')

  const headings = () => [...list.querySelectorAll(':scope > [data-jaco-content-item] h2')]
    .map((h2) => h2.textContent)
  assert.deepEqual(headings(), ['First', 'Second'], 'initial order')

  const grips = [...list.querySelectorAll('.jaco-content-grip')]
  const secondItem = grips[1].closest('[data-jaco-content-item]')
  const secondRect = grips[1].getBoundingClientRect()
  const firstRect = grips[0].closest('[data-jaco-content-item]').getBoundingClientRect()

  grips[1].dispatchEvent(pointerEvent('pointerdown', secondRect.left, secondRect.top))
  grips[1].dispatchEvent(pointerEvent('pointermove', secondRect.left, secondRect.top + 20))
  assert.ok(secondItem.classList.contains('is-dragging'), 'dragged block is marked past the threshold')

  // move above the middle of the first block, then release
  grips[1].dispatchEvent(pointerEvent('pointermove', secondRect.left, firstRect.top + 2))
  grips[1].dispatchEvent(pointerEvent('pointerup'))
  assert.deepEqual(headings(), ['Second', 'First'], 'blocks are reordered')
  assert.strictEqual(list.querySelector('.is-dragging'), null, 'mark is cleaned up')
})

QUnit.test('a click on the grip without movement drags nothing', (assert) => {
  m.render(fixture(), m.trust(
    '<div data-jaco-content-list data-jaco-fragment="news.html" data-jaco-title="Add news" data-jaco-pos="draggable">' +
    '<div data-jaco-content-item><h2>First</h2></div>' +
    '<div data-jaco-content-item><h2>Second</h2></div>' +
    '</div>'
  ))
  const list = fixture().querySelector('[data-jaco-content-list]')

  window.JacoContentLists.initContentLists(fixture(), stubFetcher())

  const grip = list.querySelector('.jaco-content-grip')
  const rect = grip.getBoundingClientRect()
  grip.dispatchEvent(pointerEvent('pointerdown', rect.left, rect.top))
  grip.dispatchEvent(pointerEvent('pointermove', rect.left, rect.top + 2))
  grip.dispatchEvent(pointerEvent('pointerup'))

  const headings = [...list.querySelectorAll(':scope > [data-jaco-content-item] h2')]
    .map((h2) => h2.textContent)
  assert.deepEqual(headings, ['First', 'Second'], 'order unchanged below the threshold')
  assert.strictEqual(list.querySelector('.is-dragging'), null, 'no residue')
})

QUnit.test('dragging near the bottom edge scrolls down', (assert) => {
  const done = assert.async()
  const spacer = document.createElement('div')
  spacer.style.height = '3000px'
  document.body.appendChild(spacer)
  m.render(fixture(), m.trust(
    '<div data-jaco-content-list data-jaco-fragment="news.html" data-jaco-title="Add news" data-jaco-pos="draggable">' +
    '<div data-jaco-content-item><h2>First</h2></div>' +
    '<div data-jaco-content-item><h2>Second</h2></div>' +
    '</div>'
  ))
  const list = fixture().querySelector('[data-jaco-content-list]')
  const scroller = document.scrollingElement || document.documentElement
  scroller.scrollTop = 100

  window.JacoContentLists.initContentLists(fixture(), stubFetcher())

  const grip = list.querySelector('.jaco-content-grip')
  const rect = grip.getBoundingClientRect()
  grip.dispatchEvent(pointerEvent('pointerdown', rect.left, rect.top))
  grip.dispatchEvent(pointerEvent('pointermove', rect.left, rect.top + 20))

  const edgeY = (window.innerHeight || document.documentElement.clientHeight) - 10
  const moved = () => grip.dispatchEvent(pointerEvent('pointermove', rect.left, edgeY))
  moved()
  const keepAlive = setInterval(moved, 50)

  setTimeout(() => {
    clearInterval(keepAlive)
    grip.dispatchEvent(pointerEvent('pointerup'))
    assert.ok(scroller.scrollTop > 100, `scrolled down, got ${scroller.scrollTop}`)
    scroller.scrollTop = 0
    spacer.remove()
    done()
  }, 400)
})

QUnit.test('only draggable lists show a grip', (assert) => {
  for (const pos of ['top', 'bottom', 'any']) {
    m.render(fixture(), m.trust(contentListHtml(pos)))
    window.JacoContentLists.initContentLists(fixture(), stubFetcher())
    assert.strictEqual(
      fixture().querySelector('.jaco-content-grip'),
      null,
      `no grip for pos=${pos}`
    )
  }
})

QUnit.test('delete asks twice and removes the block on confirm', (assert) => {
  m.render(fixture(), m.trust(contentListHtml('top')))
  const list = fixture().querySelector('[data-jaco-content-list]')
  const item = list.querySelector('[data-jaco-content-item]')

  window.JacoContentLists.attachItemOverlay(item)

  const overlay = item.querySelector('.jaco-content-item-bar')
  overlay.querySelector('button').click()
  assert.ok(overlay.textContent.includes('✓'), 'confirm is shown')
  assert.ok(overlay.textContent.includes('↩'), 'cancel is shown')

  overlay.querySelectorAll('button')[1].click()
  assert.ok(item.isConnected, 'cancel keeps the block')
  assert.strictEqual(overlay.querySelectorAll('button').length, 1, 'overlay is back to delete')

  overlay.querySelector('button').click()
  overlay.querySelectorAll('button')[0].click()
  assert.notOk(item.isConnected, 'confirm removes the block')
})

QUnit.test('init twice does not duplicate buttons', (assert) => {
  m.render(fixture(), m.trust(contentListHtml('top')))

  window.JacoContentLists.initContentLists(fixture(), stubFetcher())
  window.JacoContentLists.initContentLists(fixture(), stubFetcher())

  assert.strictEqual(fixture().querySelectorAll('.jaco-content-add-button').length, 1, 'one button only')
})

QUnit.test('clicking a dynamically inserted block opens the editor toolbar', (assert) => {
  const done = assert.async()
  m.render(fixture(), m.trust(contentListHtml('top')))

  window.JacoContentLists.initContentLists(fixture(), stubFetcher('<h2 data-jaco-editable-line>Fresh headline</h2>'))
  fixture().querySelector('.jaco-content-add-button').click()

  setTimeout(() => {
    const headline = fixture().querySelector('[data-jaco-content-item] h2')
    assert.ok(headline, 'the inserted block is rendered')
    headline.dispatchEvent(new MouseEvent('click', {bubbles: true}))
    assert.ok(
      document.querySelector('.ql-toolbar'),
      'the Quill toolbar appears for dynamically inserted editables'
    )
    done()
  }, 50)
})

QUnit.test('toolbar follows across static and inserted blocks with images', (assert) => {
  const done = assert.async()
  m.render(fixture(), m.trust(contentListHtml('top')))

  const fragment = '<div class="news-text"><h2 data-jaco-editable-line>Fresh headline</h2>' +
    '<img data-jaco-editable-image height="120" src="img/spacer.png" width="120"></div>'
  window.JacoContentLists.initContentLists(fixture(), stubFetcher(fragment))
  fixture().querySelector('.jaco-content-add-button').click()

  setTimeout(() => {
    const staticHeadline = fixture().querySelector('[data-jaco-content-list] > [data-jaco-content-item] h2')
    staticHeadline.dispatchEvent(new MouseEvent('click', {bubbles: true}))
    assert.ok(document.querySelector('.ql-toolbar'), 'toolbar opens on the static block')

    const freshHeadline = [...fixture().querySelectorAll('[data-jaco-content-item] h2')]
      .find((h2) => h2.textContent.includes('Fresh'))
    freshHeadline.dispatchEvent(new MouseEvent('click', {bubbles: true}))
    assert.ok(document.querySelector('.ql-toolbar'), 'toolbar moves to the inserted block')

    staticHeadline.dispatchEvent(new MouseEvent('click', {bubbles: true}))
    assert.ok(document.querySelector('.ql-toolbar'), 'toolbar moves back to the static block')
    done()
  }, 50)
})

QUnit.test('blocks introduce no positioned ancestor for editor chrome', (assert) => {
  const done = assert.async()
  m.render(fixture(), m.trust(contentListHtml('top')))

  window.JacoContentLists.initContentLists(fixture(), stubFetcher('<h2 data-jaco-editable-line>Fresh</h2>'))
  fixture().querySelector('.jaco-content-add-button').click()

  setTimeout(() => {
    const item = [...fixture().querySelectorAll('[data-jaco-content-item]')]
      .find((el) => el.textContent.includes('Fresh'))
    assert.strictEqual(window.getComputedStyle(item).position, 'static', 'items stay static')

    const headline = item.querySelector('h2')
    headline.dispatchEvent(new MouseEvent('click', {bubbles: true}))
    const toolbar = document.querySelector('.ql-toolbar')
    assert.ok(toolbar, 'toolbar opens')
    assert.notOk(
      item.contains(toolbar.offsetParent),
      'the absolute toolbar keeps document coordinates instead of item coordinates'
    )
    done()
  }, 50)
})

QUnit.test('gallery full version is capped at lightbox width', (assert) => {
  const done = assert.async()
  const canvas = document.createElement('canvas')
  canvas.width = 3000
  canvas.height = 10
  window.scaleDataUrlToWidth(canvas.toDataURL('image/png'), 'image/png', 1920, (full) => {
    const image = new Image()
    image.onload = () => {
      assert.strictEqual(image.width, 1920, 'wide images are capped, aspect kept')
      assert.strictEqual(image.height, 6, 'height follows the aspect ratio')
      done()
    }
    image.src = full
  })
})

QUnit.test('gallery full version keeps small images as they are', (assert) => {
  const done = assert.async()
  const canvas = document.createElement('canvas')
  canvas.width = 800
  canvas.height = 10
  window.scaleDataUrlToWidth(canvas.toDataURL('image/png'), 'image/png', 1920, (full) => {
    const image = new Image()
    image.onload = () => {
      assert.strictEqual(image.width, 800, 'no upscaling')
      done()
    }
    image.src = full
  })
})

QUnit.test('the block toolbar offers video embeds', (assert) => {
  m.render(fixture(), m.trust('<div data-jaco-editable><p>with video</p></div>'))

  fixture().querySelector('[data-jaco-editable] p')
    .dispatchEvent(new MouseEvent('click', {bubbles: true}))

  assert.ok(document.querySelector('.ql-toolbar .ql-video'), 'video button is in the toolbar')
})

QUnit.test('chrome is stripped but content markers survive', (assert) => {
  m.render(fixture(), m.trust(contentListHtml('top')))

  window.JacoContentLists.initContentLists(fixture(), stubFetcher())

  window.JacoContentLists.removeContentListChrome(fixture())

  assert.strictEqual(fixture().querySelector('.jaco-content-add-button'), null, 'buttons are gone')
  assert.strictEqual(fixture().querySelector('.jaco-content-item-bar'), null, 'overlays are gone')
  const list = fixture().querySelector('[data-jaco-content-list]')
  assert.ok(list, 'the list survives')
  assert.strictEqual(list.getAttribute('data-jaco-fragment'), 'news.html', 'fragment reference survives')
  assert.strictEqual(list.getAttribute('data-jaco-pos'), 'top', 'position survives')
  assert.ok(list.querySelector('[data-jaco-content-item]'), 'blocks survive')
})

QUnit.test('Icon renders decorative svg paths', (assert) => {
  for (const name of ['edit', 'delete', 'view', 'restore', 'clear', 'save', 'check', 'back', 'upload', 'login']) {
    m.render(fixture(), m(Icon, {name}))
    const svg = fixture().querySelector('svg.icon')
    assert.ok(svg, `${name} renders an svg`)
    assert.strictEqual(svg.getAttribute('aria-hidden'), 'true', `${name} is decorative`)
    assert.ok(svg.querySelector('path, circle'), `${name} has drawing instructions`)
  }
})

QUnit.test('row action buttons carry icons with accessible names', (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', 'qunit-token')
  const realLoad = UploadListModel.load
  UploadListModel.load = () => Promise.resolve()
  const app = document.createElement('div')
  app.id = 'app'
  document.body.appendChild(app)

  UploadListModel.items = [{file: 'pic.png', extension: 'png', size: 42, created: 1700000000}]
  UploadListModel.isLoading = false
  m.render(fixture(), m(UploadListView))

  const buttons = [...fixture().querySelectorAll('tbody td.text-right button')]
  assert.strictEqual(buttons.length, 2, 'view and delete buttons')
  for (const button of buttons) {
    assert.ok(button.querySelector('svg.icon'), 'icon replaces the text label')
    assert.ok(button.getAttribute('aria-label'), 'accessible name is present')
    assert.ok(button.getAttribute('title'), 'tooltip is present')
  }

  UploadListModel.load = realLoad
  UploadListModel.items = []
  window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
  m.render(fixture(), null)
  document.getElementById('app')?.remove()
})

QUnit.test('ButtonPrimary forwards the title tooltip', (assert) => {
  m.render(fixture(), m(ButtonPrimary, {onclick: () => {}, title: 'Save (Ctrl+S)'}, 'Save'))
  assert.strictEqual(
    fixture().querySelector('button.primary').getAttribute('title'),
    'Save (Ctrl+S)',
    'hint is hoverable'
  )

  m.render(fixture(), m(ButtonPrimary, {onclick: () => {}}, 'Save'))
  assert.strictEqual(
    fixture().querySelector('button.primary').getAttribute('title'),
    null,
    'no empty title without hint'
  )
})

QUnit.module('shortcuts', {
  beforeEach: () => {
    m.render(fixture(), null)
  },
  afterEach: () => {
    m.render(fixture(), null)
  }
})

const keydown = (target, init) => {
  const event = new KeyboardEvent('keydown', Object.assign({bubbles: true, cancelable: true}, init))
  target.dispatchEvent(event)
  return event
}

QUnit.test('isSaveShortcut matches cmd/ctrl+s without modifiers', (assert) => {
  assert.ok(isSaveShortcut({metaKey: true, ctrlKey: false, altKey: false, shiftKey: false, key: 's'}), 'cmd+s')
  assert.ok(isSaveShortcut({metaKey: false, ctrlKey: true, altKey: false, shiftKey: false, key: 's'}), 'ctrl+s')
  assert.ok(isSaveShortcut({metaKey: true, ctrlKey: false, altKey: false, shiftKey: false, key: 'S'}), 'cmd+shift produces capital S key')
  assert.notOk(isSaveShortcut({metaKey: false, ctrlKey: false, altKey: false, shiftKey: false, key: 's'}), 'plain s')
  assert.notOk(isSaveShortcut({metaKey: true, ctrlKey: false, altKey: false, shiftKey: false, key: 'x'}), 'cmd+x')
  assert.notOk(isSaveShortcut({metaKey: true, ctrlKey: false, altKey: true, shiftKey: false, key: 's'}), 'alt combinations are left alone')
  assert.notOk(isSaveShortcut({metaKey: true, ctrlKey: false, altKey: false, shiftKey: true, key: 's'}), 'shift belongs to save-and-close')
})

QUnit.test('isSaveAndCloseShortcut needs shift on top', (assert) => {
  assert.ok(isSaveAndCloseShortcut({metaKey: true, ctrlKey: false, altKey: false, shiftKey: true, key: 's'}), 'cmd+shift+s')
  assert.ok(isSaveAndCloseShortcut({metaKey: false, ctrlKey: true, altKey: false, shiftKey: true, key: 'S'}), 'ctrl+shift+S')
  assert.notOk(isSaveAndCloseShortcut({metaKey: true, ctrlKey: false, altKey: false, shiftKey: false, key: 's'}), 'plain save is not save-and-close')
  assert.notOk(isSaveAndCloseShortcut({metaKey: true, ctrlKey: false, altKey: true, shiftKey: true, key: 's'}), 'alt combinations are left alone')
})

QUnit.test('bindSaveShortcut triggers save and swallows the browser dialog', (assert) => {
  let saved = 0
  let closed = 0
  const unbind = bindSaveShortcut(fixture(), {
    onSave: () => { saved++ },
    onSaveAndClose: () => { closed++ },
  })

  const plain = keydown(fixture(), {key: 's', metaKey: true})
  assert.strictEqual(saved, 1, 'cmd+s saves')
  assert.ok(plain.defaultPrevented, 'browser dialog is swallowed')

  keydown(fixture(), {key: 's', metaKey: true, shiftKey: true})
  assert.strictEqual(closed, 1, 'cmd+shift+s saves and closes')
  assert.strictEqual(saved, 1, 'plain save did not fire again')

  keydown(fixture(), {key: 's'})
  assert.strictEqual(saved, 1, 'typing s does nothing')

  unbind()
  keydown(fixture(), {key: 's', metaKey: true})
  assert.strictEqual(saved, 1, 'unbound listener stays silent')
})

QUnit.test('bindSaveShortcut works without a close handler', (assert) => {
  let saved = 0
  const unbind = bindSaveShortcut(fixture(), {onSave: () => { saved++ }})

  keydown(fixture(), {key: 's', metaKey: true, shiftKey: true})
  assert.strictEqual(saved, 0, 'shift+s without close handler does nothing')

  keydown(fixture(), {key: 's', ctrlKey: true})
  assert.strictEqual(saved, 1, 'ctrl+s saves')

  unbind()
})

QUnit.test('shortcut labels match the platform convention', (assert) => {
  for (const label of [saveShortcutLabel(), saveAndCloseShortcutLabel()]) {
    assert.ok(/S$/.test(label), `ends with S, got ${label}`)
    assert.ok(label.includes('⌘') || label.includes('Ctrl'), `mac or pc style, got ${label}`)
  }
  assert.notStrictEqual(saveShortcutLabel(), saveAndCloseShortcutLabel(), 'save and save-and-close differ')
})

QUnit.test('bindSaveShortcut works on a separate document like the iframe', (assert) => {
  let saved = 0
  const other = document.implementation.createHTMLDocument()
  const unbind = bindSaveShortcut(other, {onSave: () => { saved++ }})

  keydown(other, {key: 's', metaKey: true})
  assert.strictEqual(saved, 1, 'shortcut fires in the other document')

  keydown(fixture(), {key: 's', metaKey: true})
  assert.strictEqual(saved, 1, 'the main document stays silent')

  unbind()
})

QUnit.module('theme', {
  beforeEach: () => {
    try {
      localStorage.removeItem('jaco-theme')
    } catch {
      // private mode etc.
    }
    document.documentElement.removeAttribute('data-theme')
  },
  afterEach: () => {
    try {
      localStorage.removeItem('jaco-theme')
    } catch {
      // private mode etc.
    }
    document.documentElement.removeAttribute('data-theme')
  }
})

QUnit.test('theme defaults to auto and persists the choice', (assert) => {
  assert.strictEqual(getTheme(), 'auto', 'no stored choice means auto')

  setTheme('dark')
  assert.strictEqual(getTheme(), 'dark', 'choice is stored')
  assert.strictEqual(document.documentElement.getAttribute('data-theme'), 'dark', 'attribute is set')

  setTheme('light')
  assert.strictEqual(document.documentElement.getAttribute('data-theme'), 'light', 'attribute follows')
})

QUnit.test('auto resolves through the OS setting', (assert) => {
  const realMatchMedia = window.matchMedia
  try {
    window.matchMedia = () => ({matches: true, addEventListener: () => {}})
    applyTheme('auto')
    assert.strictEqual(document.documentElement.getAttribute('data-theme'), 'dark', 'dark OS means dark')

    window.matchMedia = () => ({matches: false, addEventListener: () => {}})
    applyTheme('auto')
    assert.strictEqual(document.documentElement.getAttribute('data-theme'), 'light', 'light OS means light')

    applyTheme('dark')
    assert.strictEqual(document.documentElement.getAttribute('data-theme'), 'dark', 'explicit dark wins over OS')
  } finally {
    window.matchMedia = realMatchMedia
  }
})
