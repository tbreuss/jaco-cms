import {Input} from '/jaco/frontend/component/Input.js'
import {InputPassword} from '/jaco/frontend/component/InputPassword.js'
import {InputText} from '/jaco/frontend/component/InputText.js'
import {FormErrors, errorFor} from '/jaco/frontend/component/FormErrors.js'
import {ButtonPrimary} from '/jaco/frontend/component/ButtonPrimary.js'
import {ButtonSecondary} from '/jaco/frontend/component/ButtonSecondary.js'
import {Icon} from '/jaco/frontend/component/Icon.js'
import {app} from '/jaco/frontend/lib/app.js'
import {Select} from '/jaco/frontend/component/Select.js'
import {LoginView} from '/jaco/frontend/view/LoginView.js'
import {PageEditModel} from '/jaco/frontend/model/PageEditModel.js'
import {PageEditView, onSaveButtonClicked, onSaveAndCloseButtonClicked, setEditor} from '/jaco/frontend/view/page/PageEditView.js'
import {ViewUploadView} from '/jaco/frontend/view/upload/ViewUploadView.js'
import {PhpLogView} from '/jaco/frontend/view/PhpLogView.js'
import {PhpLogModel} from '/jaco/frontend/model/PhpLogModel.js'
import {SystemView} from '/jaco/frontend/view/SystemView.js'
import {SystemModel} from '/jaco/frontend/model/SystemModel.js'
import {UploadListModel} from '/jaco/frontend/model/UploadListModel.js'
import {UploadListView} from '/jaco/frontend/view/upload/UploadListView.js'
import {CssEditView} from '/jaco/frontend/view/CssEditView.js'
import {CssEditModel} from '/jaco/frontend/model/CssEditModel.js'
import {api} from '/jaco/frontend/service/api.js'
import {t, tError} from '/jaco/frontend/model/i18n.js'
import {isSaveShortcut, isSaveAndCloseShortcut, bindSaveShortcut, saveShortcutLabel, saveAndCloseShortcutLabel} from '/jaco/frontend/lib/shortcuts.js'
import {iframe} from '/jaco/frontend/lib/iframe.js'
import {getTheme, setTheme, applyTheme} from '/jaco/frontend/lib/theme.js'
import {aceThemeName, watchAceTheme} from '/jaco/frontend/lib/aceTheme.js'
import {dirty} from '/jaco/frontend/lib/dirty.js'
import {formatDateTimeCompact, formatDateTimeFull, formatBytes} from '/jaco/frontend/lib/format.js'
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

QUnit.test('expired token clears the session and redirects to login', async (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', 'stale-token')
  const stub = stubRequest(() => Promise.reject({code: 401}))
  const routeCalls = []
  const originalSet = m.route.set
  m.route.set = (path) => routeCalls.push(path)
  const before = state.list.length

  try {
    await api.get('page/list').catch(() => {})

    assert.strictEqual(window.sessionStorage.getItem('AUTHORIZATION_TOKEN'), null, 'token cleared')
    assert.ok(state.list.length > before, 'a danger toast was added')
    assert.deepEqual(routeCalls, ['/login'], 'redirected to login')
  } finally {
    stub.restore()
    m.route.set = originalSet
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
  }
})

QUnit.test('expired token on the login endpoint does not redirect', async (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', 'stale-token')
  const stub = stubRequest(() => Promise.reject({code: 401}))
  const routeCalls = []
  const originalSet = m.route.set
  m.route.set = (path) => routeCalls.push(path)

  try {
    await api.post('login', {}, {body: {}}).catch(() => {})

    assert.deepEqual(routeCalls, [], 'no redirect on login endpoint')
  } finally {
    stub.restore()
    m.route.set = originalSet
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
  }
})
QUnit.test('request urls have no trailing ampersand without parameters', async (assert) => {
  const stub = stubRequest(() => Promise.resolve({}))

  try {
    await api.get('css/list').catch(() => {})

    assert.strictEqual(stub.calls.length, 1)
    assert.strictEqual(stub.calls[0].url, 'api.php?action=css/list', 'no trailing ampersand')

    await api.get('page/list', {filter: 'x', sort: 'file'}).catch(() => {})

    assert.strictEqual(stub.calls.length, 2)
    assert.strictEqual(stub.calls[1].url, 'api.php?action=page/list&filter=x&sort=file', 'parameters appended')
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

QUnit.module('login two-factor step', {
  beforeEach: () => {
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
  },
  afterEach: () => {
    m.render(fixture(), null)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
  }
})

const mountLogin = () => {
  const host = document.createElement('div')
  fixture().appendChild(host)
  m.mount(host, LoginView)
  return host
}

const fill = (id, value) => {
  const input = fixture().querySelector('#' + id)
  input.value = value
  input.dispatchEvent(new Event('input', {bubbles: true}))
}

QUnit.test('login asks for the authentication code when required', (assert) => {
  const done = assert.async()
  const stub = stubRequest(() => Promise.resolve({totpRequired: true}))
  const host = mountLogin()

  try {
    fill('username', 'qunit-user')
    fill('password', 'secret')
    fixture().querySelector('form').requestSubmit()

    setTimeout(() => {
      try {
        assert.strictEqual(stub.calls.length, 1, 'one login request')
        assert.ok(fixture().querySelector('input#totpCode'), 'code field is shown')
        assert.strictEqual(fixture().querySelector('input#username'), null, 'credentials are hidden')
        assert.strictEqual(document.activeElement.id, 'totpCode', 'focus is on the code field')
      } finally {
        stub.restore()
        m.mount(host, null)
        done()
      }
    }, 50)
  } catch (error) {
    stub.restore()
    m.mount(host, null)
    done()
    throw error
  }
})

QUnit.test('login sends the code with the credentials', (assert) => {
  const done = assert.async()
  let calls = 0
  const stub = stubRequest(() => {
    calls++
    return calls === 1
      ? Promise.resolve({totpRequired: true})
      : new Promise(() => {}) // success path navigates away, not under test
  })
  const host = mountLogin()

  try {
    fill('username', 'qunit-user')
    fill('password', 'secret')
    fixture().querySelector('form').requestSubmit()

    setTimeout(() => {
      fill('totpCode', '123456')
      fixture().querySelector('form').requestSubmit()

      setTimeout(() => {
        try {
          assert.strictEqual(stub.calls.length, 2, 'two login requests')
          assert.deepEqual(
            stub.calls[1].body,
            {username: 'qunit-user', password: 'secret', totpCode: '123456'},
            'code sent with the credentials'
          )
        } finally {
          stub.restore()
          m.mount(host, null)
          done()
        }
      }, 50)
    }, 50)
  } catch (error) {
    stub.restore()
    m.mount(host, null)
    done()
    throw error
  }
})

QUnit.test('ButtonSecondary renders the type only when given', (assert) => {
  m.render(fixture(), m(ButtonSecondary, {onclick: () => {}, type: 'button'}, 'Back'))
  assert.strictEqual(fixture().querySelector('button').getAttribute('type'), 'button', 'type is set')

  // fresh container: updating would keep the stale attribute, because
  // mithril assigns dom.type = null instead of removing it
  const fresh = document.createElement('div')
  fixture().appendChild(fresh)
  m.render(fresh, m(ButtonSecondary, {onclick: () => {}}, 'Back'))
  assert.strictEqual(fresh.querySelector('button').getAttribute('type'), null, 'no type by default')
})

QUnit.test('back button neither submits nor sticks to verify', (assert) => {
  const done = assert.async()
  const stub = stubRequest(() => Promise.resolve({totpRequired: true}))
  const host = mountLogin()

  try {
    fill('username', 'qunit-user')
    fill('password', 'secret')
    fixture().querySelector('form').requestSubmit()

    setTimeout(() => {
      const buttons = [...fixture().querySelectorAll('.row.text-right button')]
      assert.strictEqual(buttons.length, 2, 'back and verify')
      assert.notStrictEqual(window.getComputedStyle(buttons[1]).marginLeft, '0px', 'gap before verify')

      buttons[0].click()

      setTimeout(() => {
        try {
          assert.strictEqual(stub.calls.length, 1, 'no second request from back')
          assert.ok(fixture().querySelector('input#username'), 'back on the login step')
        } finally {
          stub.restore()
          m.mount(host, null)
          done()
        }
      }, 50)
    }, 50)
  } catch (error) {
    stub.restore()
    m.mount(host, null)
    done()
    throw error
  }
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

QUnit.module('PhpLogView clear button', {
  beforeEach: () => {
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', 'qunit-token')
    PhpLogModel.load = () => Promise.resolve()
    const app = document.createElement('div')
    app.id = 'app'
    document.body.appendChild(app)
  },
  afterEach: () => {
    m.render(fixture(), null)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    PhpLogModel.data = []
    document.getElementById('app')?.remove()
  }
})

QUnit.test('clear button renders with icon and label', (assert) => {
  PhpLogModel.data = []
  PhpLogModel.isLoading = false
  m.render(fixture(), m(PhpLogView))

  const button = fixture().querySelector('.content button')
  assert.ok(button, 'a button is rendered')
  assert.ok(button.querySelector('svg.icon'), 'icon replaces the text label')
  assert.strictEqual(button.querySelector('span')?.textContent, t('Clear log'), 'label is shown')
})

QUnit.test('confirming clear reloads the list, cancelling does nothing', (assert) => {
  const realConfirm = window.confirm
  const realClear = PhpLogModel.clear
  let cleared = 0
  PhpLogModel.clear = () => {
    cleared++
    return Promise.resolve()
  }

  try {
    PhpLogModel.data = []
    PhpLogModel.isLoading = false
    m.render(fixture(), m(PhpLogView))

    window.confirm = () => true
    fixture().querySelector('.content button').click()
    assert.strictEqual(cleared, 1, 'confirmed clear reloads the list')

    window.confirm = () => false
    fixture().querySelector('.content button').click()
    assert.strictEqual(cleared, 1, 'cancelled clear does nothing')
  } finally {
    window.confirm = realConfirm
    PhpLogModel.clear = realClear
  }
})

QUnit.module('SystemView requirements', {
  beforeEach: () => {
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', 'qunit-token')
    SystemModel.load = () => Promise.resolve()
    const app = document.createElement('div')
    app.id = 'app'
    document.body.appendChild(app)
  },
  afterEach: () => {
    m.render(fixture(), null)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    SystemModel.info = {}
    document.getElementById('app')?.remove()
  }
})

QUnit.test('checks render with status, label and detail', (assert) => {
  SystemModel.info = {
    checks: [
      {label: 'PHP version', ok: true, detail: '8.2.0'},
      {label: 'PHP extension dom', ok: false, detail: 'missing'},
    ],
    config: {appVersion: '0.1.0'},
    overview: {pages: 12, uploads: 3, backups: 1, projectSize: 2048, logSize: 512, logLines: 7},
  }
  m.render(fixture(), m(SystemView))

  const rows = [...fixture().querySelectorAll('tbody tr')]
  assert.strictEqual(rows.length, 8, 'two checks plus five overview entries plus one config entry')
  assert.strictEqual(rows[0].cells[0].textContent, 'PHP version', 'label shown')
  assert.strictEqual(rows[0].cells[1].textContent, '8.2.0', 'detail shown')
  assert.ok(rows[0].querySelector('.check-ok'), 'passing check is marked')
  assert.strictEqual(rows[0].cells[2].textContent, '✓', 'passing status shown last')
  assert.ok(rows[1].querySelector('.check-fail'), 'failing check is marked')
  assert.strictEqual(rows[1].cells[2].textContent, '✗', 'failing status shown last')
  assert.strictEqual(rows[2].cells[0].textContent, t('Pages'), 'pages label shown')
  assert.strictEqual(rows[2].cells[1].textContent, '12', 'pages counted')
  assert.strictEqual(rows[5].cells[1].textContent, '2 KB', 'project size formatted')
  assert.strictEqual(rows[6].cells[0].textContent, t('PHP error log'), 'log label shown')
  assert.strictEqual(rows[6].cells[1].textContent, '512 B / 7', 'log size and lines')

  SystemModel.info.overview.logSize = 0
  SystemModel.info.overview.logLines = 0
  m.render(fixture(), m(SystemView))
  assert.strictEqual(
    [...fixture().querySelectorAll('tbody tr')][6].cells[1].textContent,
    `0 B / ${t('Empty')}`,
    'empty log shows size and marker'
  )
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

QUnit.test('bytes format with units and stay empty without input', (assert) => {
  assert.strictEqual(formatBytes(null), '', 'null')
  assert.strictEqual(formatBytes(undefined), '', 'undefined')
  assert.strictEqual(formatBytes(0), '0 B', 'zero')
  assert.strictEqual(formatBytes(512), '512 B', 'bytes')
  assert.strictEqual(formatBytes(2048), '2 KB', 'kilobytes')
  assert.ok(/MB$/.test(formatBytes(5 * 1024 * 1024)), 'megabytes')
  assert.strictEqual(formatBytes(1536), '1.5 KB', 'fraction uses the point')
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

QUnit.module('import audit')

// Every module that renders mithril components. A <Capitalized> name used
// inside m(...) or m.mount(...) must be imported or defined in the same
// file, otherwise the view crashes at runtime with "X is not defined" -
// and neither node --check nor the render tests catch that (PageEditView
// shipped without its Icon import). New view/component/lib files must be
// added to auditFiles.
const auditFiles = [
  '/jaco/frontend/app.js',
  '/jaco/frontend/lib/app.js',
  '/jaco/frontend/lib/aceTheme.js',
  '/jaco/frontend/lib/format.js',
  '/jaco/frontend/lib/helpers.js',
  '/jaco/frontend/lib/iframe.js',
  '/jaco/frontend/lib/shortcuts.js',
  '/jaco/frontend/lib/theme.js',
  '/jaco/frontend/component/ButtonGroup.js',
  '/jaco/frontend/component/ButtonPrimary.js',
  '/jaco/frontend/component/ButtonSecondary.js',
  '/jaco/frontend/component/ErrorModal.js',
  '/jaco/frontend/component/Fieldset.js',
  '/jaco/frontend/component/FilterInput.js',
  '/jaco/frontend/component/FormErrors.js',
  '/jaco/frontend/component/Icon.js',
  '/jaco/frontend/component/Input.js',
  '/jaco/frontend/component/InputPassword.js',
  '/jaco/frontend/component/InputText.js',
  '/jaco/frontend/component/Label.js',
  '/jaco/frontend/component/ModalMedium.js',
  '/jaco/frontend/component/ModalSmall.js',
  '/jaco/frontend/component/Select.js',
  '/jaco/frontend/view/CssEditView.js',
  '/jaco/frontend/view/LoginView.js',
  '/jaco/frontend/view/LogoutView.js',
  '/jaco/frontend/view/PageAddView.js',
  '/jaco/frontend/view/PageCopyView.js',
  '/jaco/frontend/view/PageDeleteView.js',
  '/jaco/frontend/view/PageGotoView.js',
  '/jaco/frontend/view/PageInlineEditView.js',
  '/jaco/frontend/view/PhpInfoView.js',
  '/jaco/frontend/view/PhpLogView.js',
  '/jaco/frontend/view/StartView.js',
  '/jaco/frontend/view/SystemView.js',
  '/jaco/frontend/view/backup/BackupEditView.js',
  '/jaco/frontend/view/backup/BackupListView.js',
  '/jaco/frontend/view/page/PageEditView.js',
  '/jaco/frontend/view/page/PageListView.js',
  '/jaco/frontend/view/upload/UploadListView.js',
  '/jaco/frontend/view/upload/ViewUploadView.js',
]

QUnit.test('every used component is imported or defined locally', async (assert) => {
  for (const file of auditFiles) {
    const source = await (await fetch(file)).text()
    assert.ok(source.length > 0, `${file} loads`)

    const imported = new Set()
    for (const match of source.matchAll(/import\s*\{([^}]*)\}\s*from/g)) {
      match[1].split(',').forEach((name) => imported.add(name.trim().split(/\s+as\s+/)[0]))
    }
    const defined = new Set(
      [...source.matchAll(/(?:const|let|var|function)\s+([A-Za-z_$][A-Za-z0-9_$]*)/g)].map((match) => match[1])
    )
    const used = new Set([
      ...[...source.matchAll(/(?<![A-Za-z0-9_$])m\(\s*([A-Z][A-Za-z0-9]*)/g)].map((match) => match[1]),
      ...[...source.matchAll(/(?<![A-Za-z0-9_$])m\.mount\(\s*[A-Za-z_$][A-Za-z0-9_$]*\s*,\s*([A-Z][A-Za-z0-9]*)/g)].map((match) => match[1]),
    ])

    for (const name of used) {
      assert.ok(imported.has(name) || defined.has(name), `${file} provides ${name}`)
    }
  }
})

QUnit.test('CssEditView shows a notice when no stylesheet exists', (assert) => {
  // without a file the form has nothing to select or save; the empty
  // select and editor from the screenshot must not render
  CssEditModel.isLoading = false
  CssEditModel.list = []
  CssEditModel.item = {}
  const text = JSON.stringify(CssEditView.view())

  assert.ok(text.includes(t('No stylesheets found.')))
  assert.ok(text.includes(t('Stylesheets are .css files in the web root.')))
  assert.notOk(text.includes(t('Current stylesheet')))
})

QUnit.test('CssEditView shows loading while the list arrives', (assert) => {
  // without this the notice flashes on every visit until the response lands
  CssEditModel.isLoading = true
  CssEditModel.list = []
  CssEditModel.item = {}
  const text = JSON.stringify(CssEditView.view())

  assert.ok(text.includes(t('Loading')))
  assert.notOk(text.includes(t('No stylesheets found.')))
  CssEditModel.isLoading = false
})

QUnit.test('CssEditView shows the form when stylesheets exist', (assert) => {
  CssEditModel.isLoading = false
  CssEditModel.list = [{file: 'site.css'}]
  CssEditModel.item = {}
  const text = JSON.stringify(CssEditView.view())

  assert.notOk(text.includes(t('No stylesheets found.')))
  assert.ok(text.includes(t('Current stylesheet')))
  CssEditModel.list = []
})



QUnit.test('CssEditView survives an empty list without an editor', (assert) => {
  // regression test: the #ace div only exists once a file is listed, so the
  // editor must be created lazily instead of crashing onupdate. A second
  // synchronous render drives onupdate without any timer.
  const created = []
  window.ace = {
    edit: (id) => {
      created.push(id)
      return {resize() {}, commands: {addCommand() {}}, setValue() {}, getValue: () => '', destroy() {}}
    },
  }
  let appDiv = document.getElementById('app')
  if (!appDiv) {
    appDiv = document.createElement('div')
    appDiv.id = 'app'
    document.body.appendChild(appDiv)
  }
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', 'test-token')
  const origLoadList = CssEditModel.loadList
  const origLoad = CssEditModel.load
  CssEditModel.loadList = () => Promise.resolve()
  CssEditModel.load = () => Promise.resolve()
  CssEditModel.isLoading = false
  CssEditModel.list = []
  CssEditModel.item = {}

  m.render(fixture(), m(CssEditView))
  assert.step('empty list renders without an editor')

  // files arrive later: the editor is created exactly once, on the live div
  CssEditModel.list = [{file: 'site.css'}]
  CssEditModel.item = {}
  m.render(fixture(), m(CssEditView))
  assert.deepEqual(created, ['ace'])
  assert.verifySteps(['empty list renders without an editor'])

  m.render(fixture(), [])
  CssEditModel.loadList = origLoadList
  CssEditModel.load = origLoad
  CssEditModel.list = []
  window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
  appDiv.remove()
  delete window.ace
})

QUnit.test('aceThemeName follows the data-theme attribute', (assert) => {
  document.documentElement.setAttribute('data-theme', 'dark')
  assert.strictEqual(aceThemeName(), 'ace/theme/tomorrow_night')
  document.documentElement.setAttribute('data-theme', 'light')
  assert.strictEqual(aceThemeName(), 'ace/theme/github')
  document.documentElement.removeAttribute('data-theme')
  assert.strictEqual(aceThemeName(), 'ace/theme/github')
})

QUnit.test('watchAceTheme switches the editor and stops on disconnect', (assert) => {
  const done = assert.async()
  const applied = []
  const editor = {setTheme: (name) => applied.push(name)}
  document.documentElement.setAttribute('data-theme', 'light')

  const stop = watchAceTheme(editor)
  document.documentElement.setAttribute('data-theme', 'dark')
  setTimeout(() => {
    assert.deepEqual(applied, ['ace/theme/tomorrow_night'])
    stop()
    document.documentElement.setAttribute('data-theme', 'light')
    setTimeout(() => {
      assert.deepEqual(applied, ['ace/theme/tomorrow_night'], 'no switch after disconnect')
      document.documentElement.removeAttribute('data-theme')
      done()
    }, 50)
  }, 50)
})

QUnit.test('Save & Close does not navigate on a validation error', (assert) => {
  const done = assert.async()
  const mockEditor = {getValue: () => '<p>x</p>'}
  setEditor(mockEditor)
  PageEditModel.item = {file: 'a.html', content: '<p>a</p>'}
  state.errors = []

  const origSave = PageEditModel.save
  PageEditModel.save = () => Promise.reject({code: 422, response: [{title: 'bad', source: {pointer: ''}}]})

  const routeCalls = []
  const originalSet = m.route.set
  m.route.set = (path) => routeCalls.push(path)

  onSaveAndCloseButtonClicked({redraw: false}).then(() => {
    assert.deepEqual(routeCalls, [], 'no navigation on a validation error')
  }).catch(() => {
    assert.ok(false, 'handler should not reject')
  }).finally(() => {
    PageEditModel.save = origSave
    PageEditModel.item = {}
    state.errors = []
    m.route.set = originalSet
    setEditor(undefined)
    done()
  })
})

QUnit.test('PageEditView stamps the editor only with the requested file', (assert) => {
  // regression test: any redraw before page/view resolves (translations,
  // loader) must not lock in stale content, and late responses of a previous
  // file selection are ignored
  const setValues = []
  window.ace = {
    edit: () => ({
      resize() {}, commands: {addCommand() {}}, setValue: (value) => setValues.push(value),
      getValue: () => '', destroy() {},
    }),
  }
  let appDiv = document.getElementById('app')
  if (!appDiv) {
    appDiv = document.createElement('div')
    appDiv.id = 'app'
    document.body.appendChild(appDiv)
  }
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', 'test-token')
  const origLoad = PageEditModel.load
  PageEditModel.load = () => Promise.resolve()
  PageEditModel.item = {}

  m.render(fixture(), m(PageEditView, {file: 'a.html'}))
  m.render(fixture(), m(PageEditView, {file: 'a.html'}))
  assert.deepEqual(setValues, [], 'no stamp before the file arrives')

  PageEditModel.item = {file: 'a.html', content: '<p>a</p>'}
  m.render(fixture(), m(PageEditView, {file: 'a.html'}))
  assert.deepEqual(setValues, ['<p>a</p>'], 'stamps once the requested file arrives')

  PageEditModel.item = {file: 'b.html', content: '<p>b</p>'}
  m.render(fixture(), m(PageEditView, {file: 'a.html'}))
  assert.deepEqual(setValues, ['<p>a</p>'], 'ignores the late response of another file')

  m.render(fixture(), [])
  PageEditModel.load = origLoad
  PageEditModel.item = {}
  window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
  appDiv.remove()
  delete window.ace
})

QUnit.test('fitBelowNavbar shifts the preview below the navbar', (assert) => {
  // the fixed admin panel would otherwise cover the top of the site
  window.scrollTo(0, 0)
  const bar = document.createElement('div')
  bar.className = 'navbar'
  bar.style.height = '47px'
  document.body.appendChild(bar)
  const frame = document.createElement('iframe')
  frame.id = 'iframe'
  document.body.appendChild(frame)

  iframe.fitBelowNavbar()
  const bottom = Math.round(bar.getBoundingClientRect().bottom)
  assert.strictEqual(frame.style.top, bottom + 'px', 'preview starts at the navbar bottom edge')
  assert.ok(frame.style.height.includes('100vh'), 'preview height compensates the offset')

  // second call with nothing changed writes nothing new
  frame.style.top = 'tampered'
  iframe.fitBelowNavbar()
  assert.strictEqual(frame.style.top, bottom + 'px', 'cached offset is restored, not stacked')

  // edit mode owns the layout through CSS: any offset is cleared
  document.body.classList.add('page-edit')
  iframe.fitBelowNavbar()
  assert.strictEqual(frame.style.top, '', 'no offset in edit mode')
  assert.strictEqual(frame.style.height, '', 'no forced height in edit mode')

  document.body.classList.remove('page-edit')
  bar.remove()
  frame.remove()
})

QUnit.module('dirty registry')

QUnit.test('dirty.set registers a predicate and dirty.clear removes it', (assert) => {
  dirty.clear()
  assert.notOk(dirty.isDirty(), 'nothing dirty by default')

  dirty.set(() => true)
  assert.ok(dirty.isDirty(), 'predicate returning true marks dirty')

  dirty.set(() => false)
  assert.notOk(dirty.isDirty(), 'predicate returning false marks clean')

  dirty.clear()
  assert.notOk(dirty.isDirty(), 'clear removes the predicate')
})

QUnit.test('dirty guard blocks in-app navigation while dirty (confirm denied)', (assert) => {
  dirty.clear()
  const originalConfirm = window.confirm
  window.confirm = () => false
  dirty.set(() => true)

  try {
    const proceed = dirty._guard()
    assert.notOk(proceed, 'navigation aborted when confirm denied')
    assert.ok(dirty.isDirty(), 'still dirty after denied navigation')
  } finally {
    window.confirm = originalConfirm
    dirty.clear()
  }
})

QUnit.test('dirty guard allows in-app navigation while dirty (confirm accepted)', (assert) => {
  dirty.clear()
  const originalConfirm = window.confirm
  window.confirm = () => true
  dirty.set(() => true)

  try {
    const proceed = dirty._guard()
    assert.ok(proceed, 'navigation proceeds when confirm accepted')
    assert.notOk(dirty.isDirty(), 'dirty cleared after accepted navigation')
  } finally {
    window.confirm = originalConfirm
    dirty.clear()
  }
})

QUnit.test('dirty guard does not interfere when not dirty', (assert) => {
  dirty.clear()
  const originalConfirm = window.confirm
  let confirmCalled = false
  window.confirm = () => { confirmCalled = true; return true }
  dirty.set(() => false)

  try {
    const proceed = dirty._guard()
    assert.ok(proceed, 'navigation proceeds')
    assert.notOk(confirmCalled, 'confirm not called when not dirty')
  } finally {
    window.confirm = originalConfirm
    dirty.clear()
  }
})
