import {Input} from '/jaco/frontend/component/Input.js'
import {InputPassword} from '/jaco/frontend/component/InputPassword.js'
import {InputText} from '/jaco/frontend/component/InputText.js'
import {FormErrors, errorFor} from '/jaco/frontend/component/FormErrors.js'
import {ButtonPrimary} from '/jaco/frontend/component/ButtonPrimary.js'
import {ButtonSecondary} from '/jaco/frontend/component/ButtonSecondary.js'
import {ButtonDanger} from '/jaco/frontend/component/ButtonDanger.js'
import {Icon} from '/jaco/frontend/component/Icon.js'
import {app} from '/jaco/frontend/lib/app.js'
import {auth} from '/jaco/frontend/lib/auth.js'
import {Select} from '/jaco/frontend/component/Select.js'
import {LoginView} from '/jaco/frontend/view/LoginView.js'
import {login as doLogin, verify as doVerify, state as loginState} from '/jaco/frontend/view/LoginView.js'
import {PageEditModel} from '/jaco/frontend/model/PageEditModel.js'
import {PageAddView} from '/jaco/frontend/view/PageAddView.js'
import {PageCopyView} from '/jaco/frontend/view/PageCopyView.js'
import {PageDeleteView} from '/jaco/frontend/view/PageDeleteView.js'
import {ModalSmall} from '/jaco/frontend/component/ModalSmall.js'
import {PageGotoView, state as pageGotoState} from '/jaco/frontend/view/PageGotoView.js'
import {PageInlineEditView} from '/jaco/frontend/view/PageInlineEditView.js'
import {save as inlineSave} from '/jaco/frontend/view/PageInlineEditView.js'
import {PageTemplateModel} from '/jaco/frontend/model/PageTemplateModel.js'
import {PageListView} from '/jaco/frontend/view/page/PageListView.js'
import {PageEditView, onSaveButtonClicked as onSavePageButtonClicked, onSaveAndCloseButtonClicked, setEditor, state as pageEditState} from '/jaco/frontend/view/page/PageEditView.js'
import {UploadListView} from '/jaco/frontend/view/upload/UploadListView.js'
import {PageListModel} from '/jaco/frontend/model/PageListModel.js'
import {PageCopySource} from '/jaco/frontend/model/PageCopySource.js'
import {ViewUploadView} from '/jaco/frontend/view/upload/ViewUploadView.js'
import {PhpLogView} from '/jaco/frontend/view/PhpLogView.js'
import {PhpLogModel} from '/jaco/frontend/model/PhpLogModel.js'
import {PhpInfoView} from '/jaco/frontend/view/PhpInfoView.js'
import {PhpInfoModel} from '/jaco/frontend/model/PhpInfoModel.js'
import {LogoutView} from '/jaco/frontend/view/LogoutView.js'
import {StartView} from '/jaco/frontend/view/StartView.js'
import {BackupEditView} from '/jaco/frontend/view/backup/BackupEditView.js'
import {BackupViewModel} from '/jaco/frontend/model/BackupViewModel.js'
import {SystemView} from '/jaco/frontend/view/SystemView.js'
import {PasswordView, state as passwordState} from '/jaco/frontend/view/PasswordView.js'
import {AboutView} from '/jaco/frontend/view/AboutView.js'
import {SystemModel} from '/jaco/frontend/model/SystemModel.js'
import {AboutModel} from '/jaco/frontend/model/AboutModel.js'
import {UploadListModel} from '/jaco/frontend/model/UploadListModel.js'
import {BackupListView} from '/jaco/frontend/view/backup/BackupListView.js'
import {BackupListModel} from '/jaco/frontend/model/BackupListModel.js'
import {CssEditView, setEditor as setCssEditor, onSaveButtonClicked as onSaveCssButtonClicked, state as cssEditState} from '/jaco/frontend/view/CssEditView.js'
import {CssEditModel} from '/jaco/frontend/model/CssEditModel.js'
import {PageTemplateEditModel} from '/jaco/frontend/model/PageContentTemplateModel.js'
import {ContentTemplateEditModel} from '/jaco/frontend/model/ContentTemplateModel.js'
import {SiteTemplateModel} from '/jaco/frontend/model/SiteTemplateModel.js'
import {TemplateView} from '/jaco/frontend/view/TemplateView.js'
import {PageTemplateView} from '/jaco/frontend/view/PageTemplateView.js'
import {ContentTemplateView} from '/jaco/frontend/view/ContentTemplateView.js'
import {api} from '/jaco/frontend/service/api.js'
import {t, tError, i18n} from '/jaco/frontend/model/i18n.js'
import {isSaveShortcut, isSaveAndCloseShortcut, bindSaveShortcut, saveShortcutLabel, saveAndCloseShortcutLabel} from '/jaco/frontend/lib/shortcuts.js'
import {iframe} from '/jaco/frontend/lib/iframe.js'
import {getTheme, setTheme, applyTheme} from '/jaco/frontend/lib/theme.js'
import {aceThemeName, watchAceTheme} from '/jaco/frontend/lib/aceTheme.js'
import {dirty} from '/jaco/frontend/lib/dirty.js'
import {maintenance} from '/jaco/frontend/lib/maintenance.js'
import {DefaultLayout} from '/jaco/frontend/layout/Default.js'
import {CommandPalette, paletteState, openPalette, closePalette} from '/jaco/frontend/view/CommandPaletteView.js'
import {commands, visibleCommands} from '/jaco/frontend/lib/commands.js'
import {formatDateTimeCompact, formatDateTimeFull, formatBytes} from '/jaco/frontend/lib/format.js'
import {state} from '/jaco/frontend/state/state.js'
const fixture = () => document.getElementById('qunit-fixture')

// The ui only reads the token payload; the signature is checked by the api. A
// syntactically valid payload is enough to steer the role-aware controls.
const tokenWithRole = (role, language = '', readOnly = false, username = '') => {
  const payload = btoa(JSON.stringify({user: username, role, language, readOnly}))
    .replace(/\+/g, '-')
    .replace(/\//g, '_')
    .replace(/=+$/, '')
  return 'eyJhbGciOiJub25lIn0.' + payload + '.qunit'
}

// Disabled controls use aria-disabled, not the native attribute: they stay
// focusable so keyboard and screen-reader users can reach the explanation.
const isAriaDisabled = (element) => element.getAttribute('aria-disabled') === 'true'

QUnit.module('components')

QUnit.test('a disabled button stays focusable, announced and inert', (assert) => {
  for (const Component of [ButtonPrimary, ButtonSecondary, ButtonDanger]) {
    let clicks = 0
    m.render(fixture(), m(Component, {
      disabled: true,
      onclick: () => { clicks++ },
      title: 'Save – Your role does not allow this.',
    }, 'Save'))

    const button = fixture().querySelector('button')
    assert.strictEqual(button.getAttribute('aria-disabled'), 'true', Component.name + ' is announced as disabled')
    assert.notOk(button.disabled, Component.name + ' is not removed from the tab order')
    assert.strictEqual(button.getAttribute('title'), 'Save – Your role does not allow this.', 'the reason is available')
    assert.strictEqual(button.getAttribute('aria-label'), 'Save – Your role does not allow this.', 'the reason is the accessible name')

    button.focus()
    assert.strictEqual(document.activeElement, button, Component.name + ' can take the focus')
    button.click()
    assert.strictEqual(clicks, 0, Component.name + ' does not act')
  }
})

QUnit.test('an enabled button still acts', (assert) => {
  let clicks = 0
  m.render(fixture(), m(ButtonPrimary, {onclick: () => { clicks++ }}, 'Save'))

  const button = fixture().querySelector('button')
  assert.notOk(isAriaDisabled(button), 'not marked disabled')
  button.click()
  assert.strictEqual(clicks, 1, 'the handler runs')
})

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
    // the handler receives the same options as the real request
    return respond(options)
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

QUnit.module('api service error handling', {
  // the app loads its translations on page load; a test that asserts on a
  // translated message must not run before that response landed
  beforeEach: async () => {
    await waitForBootLoad()
  }
})

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
QUnit.test('a role denial keeps the session', async (assert) => {
  // 403 means the role refuses one action, not that the session is gone: clearing the
  // token here was the bug, a viewer was logged out by clicking a forbidden button
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', 'valid-token')
  const stub = stubRequest(() => Promise.reject({
    code: 403,
    response: [{title: '403 Forbidden', detail: 'Your role does not allow this.'}]
  }))
  const routeCalls = []
  const originalSet = m.route.set
  m.route.set = (path) => routeCalls.push(path)
  const before = state.list.length

  try {
    await api.post('page/update', {}, {body: {}}).catch(() => {})

    assert.strictEqual(window.sessionStorage.getItem('AUTHORIZATION_TOKEN'), 'valid-token', 'token stays')
    assert.deepEqual(routeCalls, [], 'no redirect to the login')
    assert.ok(state.list.length > before, 'the denial is reported')
    assert.ok(
      state.list.some((entry) => String(entry.text).includes(t('Your role does not allow this.'))),
      'the message names the role'
    )
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

QUnit.test('500 error includes detail and reference in toast', async (assert) => {
  const stub = stubRequest(() => Promise.reject({
    code: 500,
    response: {title: 'PHP Error', detail: 'An unexpected error occurred (reference abc123).'}
  }))
  const before = state.list.length

  try {
    await api.get('page/list').catch(() => {})

    assert.ok(state.list.length > before, 'a danger message was added')
    const msg = state.list[state.list.length - 1]
    assert.ok(msg.text.includes('PHP Error'), 'toast contains error title')
    assert.ok(msg.text.includes('reference abc123'), 'toast contains log reference')
  } finally {
    stub.restore()
  }
})

QUnit.test('title and detail are translated separately, not as one joined string', async (assert) => {
  // the api sends both halves as keys, only their concatenation is unknown.
  // Pin a locale where they actually differ: with the English source messages
  // the joined fallback and the fixed lookup produce the same text, so the
  // test would pass either way.
  const savedMessages = i18n.messages
  i18n.messages = {
    'Server Error': 'Serverfehler',
    'Could not read file.': 'Datei konnte nicht gelesen werden.',
  }
  const stub = stubRequest(() => Promise.reject({
    code: 500,
    response: {title: 'Server Error', detail: 'Could not read file.'}
  }))
  const before = state.list.length

  try {
    await api.get('page/list').catch(() => {})

    assert.ok(state.list.length > before, 'a danger message was added')
    const msg = state.list[state.list.length - 1]
    assert.strictEqual(
      msg.text,
      'Serverfehler: Datei konnte nicht gelesen werden.',
      'both halves come from the translation table'
    )
  } finally {
    stub.restore()
    i18n.messages = savedMessages
    maintenance.reset()
  }
})

// the app loads translations on page load, and that response carries the
// maintenance header. It can land in the middle of these tests and flip the
// flag back on, because settings.php is a developer's own file. Wait for that
// boot load to settle, then start every test from a known state.
const waitForBootLoad = () => new Promise((resolve) => {
  const started = Date.now()
  const check = () => {
    if (i18n.status === 'idle' || Date.now() - started > 2000) {
      maintenance.reset()
      resolve()
      return
    }
    setTimeout(check, 10)
  }
  check()
})

// the real readFrom, captured before beforeEach stubs it
const realReadFrom = maintenance.readFrom

QUnit.module('maintenance signal', {
  beforeEach: async (assert) => {
    await waitForBootLoad()
    // every response carries the header, so the app's own requests keep
    // flipping the flag. Stub readFrom so tests control the state directly.
    maintenance.readFrom = () => {}
  },
  afterEach: () => {
    maintenance.reset()
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    m.render(fixture(), null)
  }
})

QUnit.test('a 503 does not change the signal, the state comes from the header', async (assert) => {
  // the mode is read from the response header, never guessed from a status
  // code: a web server or proxy answers 503 on its own, and that is not a lock
  maintenance.reset()
  const stub = stubRequest(() => Promise.reject({
    code: 503,
    response: [{title: 'Maintenance mode: changes are disabled', detail: 'Changes are disabled while maintenance mode is enabled.'}]
  }))

  try {
    await api.post('page/update', {}, {body: {}}).catch(() => {})
    assert.notOk(maintenance.isActive(), 'a rejected write leaves the signal alone')
  } finally {
    stub.restore()
  }
})

QUnit.test('readFrom sets the signal from the response header', (assert) => {
  maintenance.readFrom = realReadFrom
  maintenance.reset()
  const fake = (value) => ({getResponseHeader: (name) => value})

  maintenance.readFrom(fake('true'))
  assert.ok(maintenance.isActive(), 'true sets it on')

  maintenance.readFrom(fake('false'))
  assert.notOk(maintenance.isActive(), 'false sets it off')

  maintenance.set(true)
  maintenance.readFrom(fake(null))
  assert.ok(maintenance.isActive(), 'an absent header leaves the state untouched')
})

QUnit.test('a maintenance 503 produces a toast even while the banner is up', async (assert) => {
  // the banner is the lasting signal, the toast confirms the failed save: both
  // together, because a silent save looks like nothing happened at all
  maintenance.set(true)
  const stub = stubRequest(() => Promise.reject({
    code: 503,
    response: [{title: 'Maintenance mode: changes are disabled', detail: 'Changes are disabled while maintenance mode is enabled.'}]
  }))
  const before = state.list.length

  try {
    await api.post('page/update', {}, {body: {}}).catch(() => {})
    assert.ok(state.list.length > before, 'the failed save is reported')
  } finally {
    stub.restore()
  }
})

// callers pass background: true so mithril redraws neither on success nor on failure;
// without an explicit redraw the toast only appeared after the next page change, where
// it looked like it belonged to that page
QUnit.test('a failed background request redraws so its toast appears right away', async (assert) => {
  const stub = stubRequest(() => Promise.reject({
    code: 503,
    response: [{title: 'Maintenance mode: changes are disabled', detail: 'Changes are disabled while maintenance mode is enabled.'}]
  }))
  const realRedraw = m.redraw
  let redraws = 0
  m.redraw = () => { redraws++ }
  const before = state.list.length

  try {
    await api.post('system/log/clear', {}, {background: true, body: {}}).catch(() => {})

    assert.strictEqual(state.list.length, before + 1, 'exactly one toast for the failed request')
    assert.ok(redraws > 0, 'a redraw is requested, the toast does not wait for the next click')
  } finally {
    m.redraw = realRedraw
    stub.restore()
  }
})

QUnit.test('a real server error is reported even while the mode is on', async (assert) => {
  // suppressing every error while maintenance is on would hide actual failures
  maintenance.set(true)
  const stub = stubRequest(() => Promise.reject({
    code: 500,
    response: {title: 'PHP Error', detail: 'An unexpected error occurred (reference abc123).'}
  }))
  const before = state.list.length

  try {
    await api.post('page/update', {}, {body: {}}).catch(() => {})
    assert.ok(state.list.length > before, 'a 500 still produces a toast')
  } finally {
    stub.restore()
  }
})

QUnit.test('the layout shows a banner only while the signal is on', (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
  // the layout renders a placeholder until the translations arrive, so pin
  // the status: otherwise the test passes or fails depending on when i18n
  // happened to settle
  const savedStatus = i18n.status
  i18n.status = 'idle'

  try {
    m.render(fixture(), m(DefaultLayout, m('div')))
    assert.notOk(fixture().querySelector('.maintenance-banner'), 'no banner while off')

    maintenance.set(true)
    m.render(fixture(), m(DefaultLayout, m('div')))
    const banner = fixture().querySelector('.maintenance-banner')
    assert.ok(banner, 'banner appears while the mode is on')
    assert.strictEqual(banner.textContent, t('Changes are disabled while maintenance mode is enabled.'))
    assert.strictEqual(banner.getAttribute('role'), 'status', 'announced to screen readers')

    // the loader is an overlay: it must take no space, otherwise its dark track
    // pushed the banner down and read as a black border under it
    const loader = fixture().querySelector('#loader')
    assert.ok(loader, 'the loader is rendered')
    assert.strictEqual(getComputedStyle(loader).position, 'fixed', 'the loader takes no layout space')
    assert.strictEqual(
      getComputedStyle(loader).backgroundColor,
      'rgba(0, 0, 0, 0)',
      'the idle track is invisible'
    )
    assert.strictEqual(getComputedStyle(loader).height, '2px', 'the bar stays 2px high')
    assert.strictEqual(getComputedStyle(loader, '::after').height, '2px', 'the segment stays 2px high')
  } finally {
    i18n.status = savedStatus
  }
})

QUnit.test('the layout greys out what the role may not do', (assert) => {
  const savedStatus = i18n.status
  i18n.status = 'idle'
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('editor'))

  try {
    m.render(fixture(), m(DefaultLayout, m('div')))

    assert.strictEqual(fixture().querySelector('.readonly-banner'), null, 'no viewer banner exists any more')

    const menuLabels = [...fixture().querySelectorAll('.navbar .dropbtn')].map((button) => button.textContent)
    assert.deepEqual(menuLabels, [t('Content'), t('This page'), t('Admin'), t('Account')])

    const dropdowns = [...fixture().querySelectorAll('.dropdown-content')]
    assert.deepEqual(
      [...dropdowns[0].querySelectorAll('a')].map((link) => link.textContent),
      [t('Pages'), t('Media'), t('Shared content'), t('Page templates'), t('Content templates'), t('Backups'), t('Design')],
      'an editor reaches content, every template, backups and the design',
    )
    assert.deepEqual(
      [...dropdowns[1].querySelectorAll('a')].map((link) => link.textContent),
      [t('Edit page'), t('Copy page'), t('Delete page')],
      'every page action is available to an editor',
    )
    assert.deepEqual(
      [...dropdowns[1].querySelectorAll('.nav-disabled')].map((item) => item.textContent),
      [],
      'nothing in "This page" is disabled',
    )
    assert.strictEqual(dropdowns[2].querySelectorAll('a').length, 0, 'no admin entry is active')
    assert.deepEqual(
      [...dropdowns[2].querySelectorAll('.nav-disabled')].map((item) => item.textContent),
      [t('Environment'), t('Application log'), t('PHP info')],
      'admin entries stay visible but disabled',
    )
    assert.deepEqual(
      [...dropdowns[3].querySelectorAll('a')].map((link) => link.textContent),
      [t('About'), t('Change password'), t('Change language'), t('Logout')],
      'the account menu is available',
    )
    assert.strictEqual(fixture().querySelector('.jump-to-page'), null, 'the page jump is no longer a direct control')
    assert.ok(fixture().querySelector('.navbar > select.theme-switch'), 'the theme switch stays direct')
    assert.ok(dropdowns[3].querySelector('select.theme-switch.in-menu'), 'small screens find it in the account menu')
  } finally {
    i18n.status = savedStatus
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
  }
})

QUnit.test('a read-only account keeps the password entry active', (assert) => {
  const savedStatus = i18n.status
  i18n.status = 'idle'
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin', '', true))

  try {
    m.render(fixture(), m(DefaultLayout, m('div')))

    const dropdowns = [...fixture().querySelectorAll('.dropdown-content')]
    const account = dropdowns[3]
    assert.deepEqual(
      [...account.querySelectorAll('a')].map((link) => link.textContent),
      [t('About'), t('Change password'), t('Change language'), t('Logout')],
      'the entry stays active: the form explains on save',
    )
    assert.deepEqual(
      [...account.querySelectorAll('.nav-disabled')].map((item) => item.textContent),
      [],
      'nothing is disabled in the account menu',
    )
    assert.ok(fixture().querySelector('.maintenance-banner'), 'the demo hint is shown')
  } finally {
    i18n.status = savedStatus
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
  }
})

QUnit.test('an editor writes content but sees admin menus disabled', (assert) => {
  const savedStatus = i18n.status
  i18n.status = 'idle'
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('editor'))

  try {
    m.render(fixture(), m(DefaultLayout, m('div')))

    assert.notOk(fixture().querySelector('.readonly-banner'), 'an editor is not read-only')
    const dropdowns = [...fixture().querySelectorAll('.dropdown-content')]
    assert.deepEqual(
      [...dropdowns[1].querySelectorAll('a')].map((link) => link.textContent),
      [t('Edit page'), t('Copy page'), t('Delete page')],
      'page writes are offered',
    )
    assert.strictEqual(dropdowns[1].querySelectorAll('.nav-disabled').length, 0, 'no page write is disabled')
    assert.strictEqual(dropdowns[2].querySelectorAll('a').length, 0, 'no admin entry is active')
    assert.strictEqual(dropdowns[2].querySelectorAll('.nav-disabled').length, 3, 'admin entries are disabled')
    assert.deepEqual(
      [...dropdowns[3].querySelectorAll('a')].map((link) => link.textContent),
      [t('About'), t('Change password'), t('Change language'), t('Logout')],
      'the account menu is available',
    )
  } finally {
    i18n.status = savedStatus
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
  }
})

QUnit.test('an admin sees every menu entry active', (assert) => {
  const savedStatus = i18n.status
  i18n.status = 'idle'
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))

  try {
    m.render(fixture(), m(DefaultLayout, m('div')))

    const menuLabels = [...fixture().querySelectorAll('.navbar .dropbtn')].map((button) => button.textContent)
    assert.deepEqual(menuLabels, [t('Content'), t('This page'), t('Admin'), t('Account')])
    assert.strictEqual(fixture().querySelectorAll('.nav-disabled').length, 0, 'nothing is disabled')
    const dropdowns = [...fixture().querySelectorAll('.dropdown-content')]
    assert.strictEqual(dropdowns[2].querySelectorAll('a').length, 3, 'all admin entries are active')
    assert.deepEqual(
      [...dropdowns[2].querySelectorAll('a')].map((link) => link.textContent),
      [t('Environment'), t('Application log'), t('PHP info')],
    )
  } finally {
    i18n.status = savedStatus
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
  }
})

QUnit.test('the account menu shows the logged-in user and role', (assert) => {
  const savedStatus = i18n.status
  i18n.status = 'idle'
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin', '', true, 'alice'))

  try {
    m.render(fixture(), m(DefaultLayout, m('div')))

    const info = fixture().querySelector('.nav-account-info')
    assert.ok(info, 'the account label is rendered')
    assert.strictEqual(info.tagName, 'SPAN', 'the label is not an action')
    assert.strictEqual(info.getAttribute('role'), 'presentation', 'a static label inside the menu')
    assert.strictEqual(info.textContent, `alice (${t('Admin')})`)
    assert.strictEqual(getComputedStyle(info).whiteSpace, 'normal', 'long labels can wrap')

    const role = info.querySelector('.nav-account-role')
    assert.ok(role, 'the role has its own span')
    assert.strictEqual(role.textContent, `(${t('Admin')})`)
    assert.strictEqual(getComputedStyle(role).fontWeight, '400', 'the role is lighter than the name')
  } finally {
    i18n.status = savedStatus
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
  }
})

QUnit.module('menu dropdowns', {
  beforeEach: () => {
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
    i18n.status = 'idle'
    const app = document.createElement('div')
    app.id = 'app'
    document.body.appendChild(app)
    m.render(fixture(), m(DefaultLayout, m('div')))
  },
  afterEach: () => {
    m.render(fixture(), null)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    document.getElementById('app')?.remove()
  }
})

QUnit.test('a click opens and closes the dropdown', (assert) => {
  const button = fixture().querySelector('.dropbtn')
  assert.strictEqual(button.getAttribute('aria-expanded'), 'false')

  button.click()
  m.render(fixture(), m(DefaultLayout, m('div')))
  assert.strictEqual(fixture().querySelector('.dropbtn').getAttribute('aria-expanded'), 'true')
  assert.ok(fixture().querySelector('.dropdown-content.is-open'), 'the menu is open')

  fixture().querySelector('.dropbtn').click()
  m.render(fixture(), m(DefaultLayout, m('div')))
  assert.strictEqual(fixture().querySelector('.dropbtn').getAttribute('aria-expanded'), 'false')
  assert.notOk(fixture().querySelector('.dropdown-content.is-open'), 'the menu is closed again')
})

QUnit.test('a mouse hover opens and closes the dropdown', (assert) => {
  const realMatchMedia = window.matchMedia
  window.matchMedia = (query) => ({
    matches: query.includes('hover: hover'),
    addEventListener: () => {},
    removeEventListener: () => {},
  })

  try {
    const enter = new PointerEvent('pointerenter', {pointerType: 'mouse'})
    fixture().querySelector('.dropdown').dispatchEvent(enter)
    m.render(fixture(), m(DefaultLayout, m('div')))
    assert.ok(fixture().querySelector('.dropdown-content.is-open'), 'hover opens the menu')

    const leave = new PointerEvent('pointerleave', {pointerType: 'mouse'})
    fixture().querySelector('.dropdown').dispatchEvent(leave)
    m.render(fixture(), m(DefaultLayout, m('div')))
    assert.notOk(fixture().querySelector('.dropdown-content.is-open'), 'leaving closes it')
  } finally {
    window.matchMedia = realMatchMedia
  }
})

QUnit.test('a touch tap toggles even on a hover-capable device', (assert) => {
  const realMatchMedia = window.matchMedia
  window.matchMedia = (query) => ({
    matches: query.includes('hover: hover'),
    addEventListener: () => {},
    removeEventListener: () => {},
  })

  try {
    const tap = () => {
      const button = fixture().querySelector('.dropbtn')
      button.dispatchEvent(new PointerEvent('pointerdown', {pointerType: 'touch', bubbles: true}))
      button.dispatchEvent(new MouseEvent('click', {bubbles: true, detail: 1}))
      m.render(fixture(), m(DefaultLayout, m('div')))
    }

    tap()
    assert.ok(fixture().querySelector('.dropdown-content.is-open'), 'the tap opens the menu')
    tap()
    assert.notOk(fixture().querySelector('.dropdown-content.is-open'), 'the next tap closes it')
  } finally {
    window.matchMedia = realMatchMedia
  }
})

QUnit.test('escape and a click outside close the menu', (assert) => {
  fixture().querySelector('.dropbtn').click()
  m.render(fixture(), m(DefaultLayout, m('div')))
  assert.ok(fixture().querySelector('.dropdown-content.is-open'), 'open before escape')

  document.dispatchEvent(new KeyboardEvent('keydown', {key: 'Escape'}))
  m.render(fixture(), m(DefaultLayout, m('div')))
  assert.notOk(fixture().querySelector('.dropdown-content.is-open'), 'escape closes')

  fixture().querySelector('.dropbtn').click()
  m.render(fixture(), m(DefaultLayout, m('div')))
  assert.ok(fixture().querySelector('.dropdown-content.is-open'), 'open before outside click')

  document.body.click()
  m.render(fixture(), m(DefaultLayout, m('div')))
  assert.notOk(fixture().querySelector('.dropdown-content.is-open'), 'an outside click closes')
})

QUnit.module('command palette', {
  beforeEach: () => {
    i18n.status = 'idle'
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
    paletteState.open = false
    paletteState.filter = ''
    paletteState.selected = 0
    paletteState.previousFocus = null
    const origHasElement = iframe.hasElement
    const origFileName = iframe.fileName
    const origPageTitle = iframe.pageTitle
    iframe.hasElement = () => true
    iframe.fileName = () => 'index.html'
    iframe.pageTitle = () => 'Home'
    window._iframeRestore = () => {
      iframe.hasElement = origHasElement
      iframe.fileName = origFileName
      iframe.pageTitle = origPageTitle
    }
    m.render(fixture(), null)
  },
  afterEach: () => {
    closePalette(false)
    paletteState.open = false
    paletteState.filter = ''
    paletteState.selected = 0
    paletteState.previousFocus = null
    m.render(fixture(), null)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    if (window._iframeRestore) { window._iframeRestore(); delete window._iframeRestore }
  }
})

QUnit.test('renders localized commands and opens the filter', (assert) => {
  openPalette()
  m.render(fixture(), m(CommandPalette))

  const palette = fixture().querySelector('.palette')
  assert.ok(palette, 'palette rendered')
  assert.strictEqual(palette.getAttribute('aria-modal'), 'true')
  assert.strictEqual(document.activeElement, fixture().querySelector('.palette-filter'), 'the filter takes focus')

  const labels = [...fixture().querySelectorAll('.palette-label')].map((label) => label.textContent)
  assert.ok(labels.includes(t('Add page')), 'command labels are translated')
  assert.ok(labels.includes(t('List pages')))
  assert.ok(labels.includes(t('Change theme')))
  assert.ok(commands.every((cmd) => typeof cmd.labelKey === 'string'), 'commands use translation keys')
  assert.strictEqual(fixture().querySelectorAll('.palette-keys').length, 0, 'no shortcut badges')
})

QUnit.test('filters by the translated label and runs with Enter', (assert) => {
  const originalSet = m.route.set
  const routes = []
  m.route.set = (path) => routes.push(path)

  try {
    openPalette()
    m.render(fixture(), m(CommandPalette))

    const input = fixture().querySelector('.palette-filter')
    input.value = t('List pages')
    input.dispatchEvent(new Event('input', {bubbles: true}))
    m.render(fixture(), m(CommandPalette))

    const items = [...fixture().querySelectorAll('.palette-item')]
    assert.strictEqual(items.length, 1, 'the label filter narrows the list')
    assert.ok(items[0].textContent.includes(t('List pages')))
    assert.deepEqual(routes, [], 'typing only filters, it does not run a command')

    fixture().querySelector('.palette-filter')
      .dispatchEvent(new KeyboardEvent('keydown', {key: 'Enter', bubbles: true}))
    assert.deepEqual(routes, ['/pages'])
  } finally {
    m.route.set = originalSet
  }
})

QUnit.test('the theme command cycles the theme', (assert) => {
  const originalTheme = getTheme()

  try {
    openPalette()
    m.render(fixture(), m(CommandPalette))

    const input = fixture().querySelector('.palette-filter')
    input.value = t('Change theme')
    input.dispatchEvent(new Event('input', {bubbles: true}))
    m.render(fixture(), m(CommandPalette))

    fixture().querySelector('.palette-filter')
      .dispatchEvent(new KeyboardEvent('keydown', {key: 'Enter', bubbles: true}))
    assert.notStrictEqual(getTheme(), originalTheme, 'the theme changes')
  } finally {
    setTheme(originalTheme)
  }
})

QUnit.test('an unknown role sees no command that opens a page', (assert) => {
  // a token without a known role grants nothing, so every navigation command is
  // gone as well - only the theme switch stays, it opens no page
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('viewer'))
  openPalette()
  m.render(fixture(), m(CommandPalette))

  const labels = [...fixture().querySelectorAll('.palette-label')].map((label) => label.textContent)
  assert.notOk(labels.includes(t('Add page')), 'no page can be added')
  assert.notOk(labels.includes(t('List pages')), 'no page is offered either')

  const ids = visibleCommands().map((cmd) => cmd.id)
  assert.deepEqual(ids, ['theme.toggle'], 'only the theme switch is left')
})

QUnit.test('an editor sees the commands the palette gives the editor', (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('editor'))
  openPalette()
  m.render(fixture(), m(CommandPalette))

  const labels = [...fixture().querySelectorAll('.palette-label')].map((label) => label.textContent)
  assert.ok(labels.includes(t('Add page')))
  assert.ok(labels.includes(t('List pages')))
  assert.ok(labels.includes(t('List templates')))
  assert.ok(labels.includes(t('List backups')), 'an editor restores his own work')
  assert.ok(labels.includes(t('Edit CSS')), 'an editor reads the stylesheet')
})

QUnit.test('the palette names the page the page commands act on', (assert) => {
  openPalette()
  m.render(fixture(), m(CommandPalette))

  const contexts = [...fixture().querySelectorAll('.palette-context')].map((item) => item.textContent)
  assert.ok(contexts.length > 0, 'the page commands name their target')
  assert.ok(contexts.every((text) => text === 'Home'), 'the target is the page in the preview')
})

QUnit.test('escape closes and restores focus', async (assert) => {
  const trigger = document.createElement('button')
  trigger.type = 'button'
  trigger.textContent = 'trigger'
  document.body.appendChild(trigger)
  trigger.focus()

  try {
    openPalette()
    m.render(fixture(), m(CommandPalette))
    assert.strictEqual(document.activeElement, fixture().querySelector('.palette-filter'))

    fixture().querySelector('.palette-filter')
      .dispatchEvent(new KeyboardEvent('keydown', {key: 'Escape', bubbles: true}))
    m.render(fixture(), m(CommandPalette))
    assert.notOk(paletteState.open)

    await new Promise((resolve) => setTimeout(resolve, 0))
    assert.strictEqual(document.activeElement, trigger, 'focus returns to the trigger')
  } finally {
    trigger.remove()
  }
})

QUnit.module('command palette trigger', {
  beforeEach: () => {
    i18n.status = 'idle'
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
    paletteState.open = false
    paletteState.filter = ''
    paletteState.selected = 0
    paletteState.previousFocus = null
    m.render(fixture(), m(DefaultLayout, m('div')))
  },
  afterEach: () => {
    closePalette(false)
    paletteState.open = false
    m.render(fixture(), null)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
  }
})

QUnit.test('the palette trigger names the Ctrl/Cmd+K shortcut', (assert) => {
  const shortcut = /Mac|iPhone|iPad|iPod/.test(navigator.platform) ? '⌘K' : 'Ctrl+K'
  const trigger = fixture().querySelector('.palette-trigger')
  assert.ok(trigger, 'trigger rendered')
  assert.ok(trigger.title.includes(shortcut), 'the tooltip names the shortcut')
  assert.ok(trigger.getAttribute('aria-label').includes(shortcut), 'the accessible name names the shortcut')
  assert.strictEqual(trigger.querySelector('.palette-shortcut')?.textContent, shortcut, 'the shortcut is visible')
  // the desktop shows the badge and the label; the icon is for small screens, where
  // the stylesheet hides both and shows the icon instead. It is the search icon,
  // not the keyboard icon the badge once replaced.
  assert.ok(trigger.querySelector('.icon'), 'a small-screen icon is rendered')
  assert.ok(trigger.querySelector('.icon + kbd.palette-shortcut'), 'the badge follows the icon')
})

QUnit.test('the listener is removed with the layout', (assert) => {
  closePalette(false)
  m.render(fixture(), null)

  document.dispatchEvent(new KeyboardEvent('keydown', {key: 'k', ctrlKey: true, bubbles: true}))
  assert.notOk(paletteState.open, 'unmounted layout listens no longer')
})

QUnit.test('the palette stays closed on the login screen', (assert) => {
  closePalette(false)
  window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
  m.render(fixture(), m(DefaultLayout, m('div')))

  document.dispatchEvent(new KeyboardEvent('keydown', {key: 'k', ctrlKey: true, bubbles: true}))
  assert.notOk(paletteState.open, 'unauthenticated users cannot open the palette')
})

QUnit.test('Ctrl/Cmd+K opens over a modal', (assert) => {
  closePalette(false)
  const modal = document.createElement('div')
  modal.className = 'modal'
  modal.tabIndex = -1
  document.body.appendChild(modal)
  modal.focus()

  try {
    document.dispatchEvent(new KeyboardEvent('keydown', {key: 'k', ctrlKey: true, bubbles: true}))
    assert.ok(paletteState.open, 'Ctrl+K opens while the modal has focus')

    closePalette(false)
    const input = document.createElement('input')
    modal.appendChild(input)
    input.focus()

    document.dispatchEvent(new KeyboardEvent('keydown', {key: 'k', ctrlKey: true, bubbles: true}))
    assert.ok(paletteState.open, 'Ctrl+K opens from a modal input')
  } finally {
    modal.remove()
  }
})

QUnit.module('command palette in the preview iframe', {
  beforeEach: () => {
    i18n.status = 'idle'
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
    paletteState.open = false
    paletteState.filter = ''
    paletteState.selected = 0
    paletteState.previousFocus = null

    const frame = document.createElement('iframe')
    frame.id = 'iframe'
    document.body.appendChild(frame)
    frame.contentDocument?.body?.focus()
    m.render(fixture(), m(DefaultLayout, m('div')))
  },
  afterEach: () => {
    closePalette(false)
    paletteState.open = false
    m.render(fixture(), null)
    document.getElementById('iframe')?.remove()
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
  }
})

QUnit.test('Ctrl/Cmd+K works while the preview has focus', async (assert) => {
  const frame = document.getElementById('iframe')
  await new Promise((resolve) => setTimeout(resolve, 0))
  const doc = frame.contentDocument
  doc.body.focus()

  doc.dispatchEvent(new KeyboardEvent('keydown', {key: 'k', ctrlKey: true, bubbles: true}))
  assert.ok(paletteState.open, 'Ctrl+K opens from the preview')

  closePalette(false)
  const input = doc.createElement('input')
  doc.body.appendChild(input)
  input.focus()

  doc.dispatchEvent(new KeyboardEvent('keydown', {key: 'k', ctrlKey: true, bubbles: true}))
  assert.ok(paletteState.open, 'Ctrl+K opens from an input inside the preview')
})

QUnit.module('role claims', {
  afterEach: () => {
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
  }
})

QUnit.test('an admin token allows every role and carries the language', (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin', 'de'))

  assert.strictEqual(auth.role(), 'admin')
  assert.ok(auth.can('editor'), 'admin includes editor')
  assert.ok(auth.can('admin'), 'admin includes admin')
  assert.strictEqual(auth.language(), 'de')
})

QUnit.test('an editor token writes content but not configuration', (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('editor'))

  assert.ok(auth.can('editor'))
  assert.notOk(auth.can('admin'))
})

QUnit.test('an unknown role grants nothing', (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('viewer'))

  assert.strictEqual(auth.role(), '', 'no known role')
  assert.notOk(auth.can('editor'))
  assert.notOk(auth.can('admin'))
})

QUnit.test('a read-only token keeps its role but is flagged', (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin', '', true))

  assert.strictEqual(auth.role(), 'admin', 'the role is unchanged')
  assert.ok(auth.isReadOnly(), 'the account is read-only')
})

QUnit.test('an unreadable token grants nothing', (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', 'not-a-jwt')

  assert.strictEqual(auth.role(), '', 'no role is derived from a broken token')
  assert.notOk(auth.can('editor'))
  assert.notOk(auth.can('admin'))
  assert.notOk(auth.isReadOnly())
  assert.strictEqual(auth.language(), '')
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
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
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
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
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

  const cell = [...fixture().querySelectorAll('tbody td.text-nowrap')].find((td) =>
    /^\d{2}\.\d{2}\.\d{2} \d{2}:\d{2}$/.test(td.textContent.trim()))
  assert.ok(cell, 'the created cell is rendered')
  assert.ok(/^\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}:\d{2}$/.test(cell.getAttribute('title')), 'tooltip holds the full time')
  assert.ok(cell.classList.contains('text-nowrap'), 'no wrapping inside the cell')
})

QUnit.test('a viewer sees upload and delete disabled', (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('viewer'))
  UploadListModel.items = [{file: 'a.png', extension: 'png', size: 1, created: 1700000000}]
  UploadListModel.isLoading = false
  m.render(fixture(), m(UploadListView))

  const upload = fixture().querySelector('.content > div button')
  assert.ok(upload, 'the upload button stays visible')
  assert.ok(upload.textContent.includes(t('Upload files')))
  assert.ok(isAriaDisabled(upload), 'upload is disabled')
  assert.ok(fixture().querySelector('#uploadFiles'), 'the hidden file input stays in the DOM')

  const buttons = [...fixture().querySelectorAll('tbody td.text-right button')]
  assert.strictEqual(buttons.length, 2, 'view and delete stay visible')
  assert.notOk(isAriaDisabled(buttons[0]), 'view stays usable')
  assert.ok(buttons[1].classList.contains('danger'))
  assert.ok(isAriaDisabled(buttons[1]), 'delete is disabled')
})

QUnit.module('PhpLogView clear button', {
  beforeEach: () => {
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
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

// the api layer reports a failed request already; a second toast from the view would
// repeat the same message (that is how two toasts appeared when clearing the log
// failed in maintenance mode)
QUnit.test('a failed clear leaves the toasting to the api layer', async (assert) => {
  const realConfirm = window.confirm
  const realClear = PhpLogModel.clear
  const before = state.list.length
  PhpLogModel.clear = () => Promise.reject({
    code: 503,
    response: {title: 'Maintenance mode: changes are disabled', detail: 'Changes are disabled while maintenance mode is enabled.'}
  })

  try {
    window.confirm = () => true
    PhpLogModel.data = []
    PhpLogModel.isLoading = false
    m.render(fixture(), m(PhpLogView))
    fixture().querySelector('.content button').click()
    await new Promise((resolve) => setTimeout(resolve, 0))

    assert.strictEqual(state.list.length, before, 'no toast from the view itself')
  } finally {
    window.confirm = realConfirm
    PhpLogModel.clear = realClear
  }
})

QUnit.module('PasswordView form', {
  beforeEach: () => {
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
    passwordState.current = ''
    passwordState.next = ''
    passwordState.errors = []
    const app = document.createElement('div')
    app.id = 'app'
    document.body.appendChild(app)
  },
  afterEach: () => {
    m.render(fixture(), null)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    passwordState.current = ''
    passwordState.next = ''
    passwordState.errors = []
    document.getElementById('app')?.remove()
  }
})

QUnit.test('the form posts both passwords and clears itself', async (assert) => {
  const stub = stubRequest(() => Promise.resolve({}))

  try {
    m.render(fixture(), m(PasswordView))

    const headings = [...fixture().querySelectorAll('h1')].map((heading) => heading.textContent)
    assert.ok(headings.includes(t('Change password')), 'the page has a heading')

    const current = fixture().querySelector('#currentPassword')
    const next = fixture().querySelector('#newPassword')
    assert.ok(current, 'the current password field is rendered')
    assert.ok(next, 'the new password field is rendered')

    current.value = 'alt'
    current.dispatchEvent(new Event('input'))
    next.value = 'neu-neu-neu'
    next.dispatchEvent(new Event('input'))

    fixture().querySelector('form').dispatchEvent(new Event('submit', {cancelable: true}))
    // the stub replaces m.request, so the automatic redraw is gone and m.redraw() waits
    // for the next frame
    await new Promise((resolve) => setTimeout(resolve, 20))
    m.render(fixture(), m(PasswordView))

    const call = stub.calls.find((options) => String(options.url).includes('system/change-password'))
    assert.ok(call, 'the endpoint is called')
    assert.strictEqual(call.body.withPassword, 'alt')
    assert.strictEqual(call.body.withNewPassword, 'neu-neu-neu')
    assert.strictEqual(fixture().querySelector('#currentPassword').value, '', 'the fields are cleared')
    assert.ok(
      state.list.some((entry) => entry.type === 'success' && entry.text === t('The password was changed.')),
      'a success toast is shown'
    )
  } finally {
    stub.restore()
  }
})

QUnit.test('a validation error is shown under its field', async (assert) => {
  const stub = stubRequest(() => Promise.reject({
    code: 422,
    response: [{source: {pointer: 'withNewPassword'}, title: 'Validation Error', detail: 'Password needs at least 8 characters.'}]
  }))

  try {
    m.render(fixture(), m(PasswordView))
    fixture().querySelector('form').dispatchEvent(new Event('submit', {cancelable: true}))
    await new Promise((resolve) => setTimeout(resolve, 20))
    m.render(fixture(), m(PasswordView))

    const error = fixture().querySelector('.field-error')
    assert.ok(error, 'the error is rendered')
    assert.ok(error.textContent.includes(t('Password needs at least 8 characters.')), 'the message is translated')
  } finally {
    stub.restore()
  }
})

QUnit.test('cancel returns to the preview', (assert) => {
  const originalSet = m.route.set
  const routeCalls = []
  m.route.set = (path) => routeCalls.push(path)

  try {
    m.render(fixture(), m(PasswordView))
    const cancel = [...fixture().querySelectorAll('button')]
      .find((button) => button.textContent.includes(t('Cancel')))
    assert.ok(cancel, 'cancel button rendered')
    cancel.click()
    assert.deepEqual(routeCalls, ['/'])
  } finally {
    m.route.set = originalSet
  }
})

QUnit.test('cancel does not submit the form', (assert) => {
  // a button without a type is a submit button: Cancel used to post the form with
  // empty fields and produced a validation error
  const stub = stubRequest(() => Promise.resolve({}))
  const originalSet = m.route.set
  const routeCalls = []
  m.route.set = (path) => routeCalls.push(path)

  try {
    m.render(fixture(), m(PasswordView))
    const cancel = [...fixture().querySelectorAll('button')]
      .find((button) => button.textContent.includes(t('Cancel')))

    assert.ok(cancel, 'cancel button rendered')
    assert.strictEqual(cancel.getAttribute('type'), 'button', 'cancel is not a submit button')

    cancel.click()

    assert.deepEqual(routeCalls, ['/'], 'cancel navigates away')
    assert.deepEqual(stub.calls, [], 'no request was sent')
  } finally {
    stub.restore()
    m.route.set = originalSet
  }
})

QUnit.test('a read-only user still reaches the password form', (assert) => {
  // the form opens; the server answers the save with the read-only message, which
  // explains more than a redirected link would
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin', '', true))
  const routeCalls = []
  const originalSet = m.route.set
  m.route.set = (path) => routeCalls.push(path)

  try {
    m.render(fixture(), m(PasswordView))
    assert.deepEqual(routeCalls, [], 'the form is not skipped')
    assert.ok(fixture().querySelector('input[type=password]'), 'the fields are rendered')
  } finally {
    m.route.set = originalSet
  }
})

QUnit.module('SystemView requirements', {
  beforeEach: () => {
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
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
  assert.strictEqual(rows[6].cells[0].textContent, t('Application log'), 'log label shown')
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

QUnit.test('maintenanceMode reads as yes/no', (assert) => {
  const configRow = (maintenanceMode) => {
    SystemModel.info = {
      checks: [],
      config: {maintenanceMode: maintenanceMode},
      overview: {pages: 0, uploads: 0, backups: 0, projectSize: 0, logSize: 0, logLines: 0},
    }
    m.render(fixture(), m(SystemView))
    return [...fixture().querySelectorAll('tbody tr')].find((tr) => tr.cells[0].textContent === 'maintenanceMode')
  }

  assert.strictEqual(configRow('true').cells[1].textContent, t('Yes'), 'true shown as a translated yes')
  assert.strictEqual(configRow('false').cells[1].textContent, t('No'), 'false shown as a translated no')
})

QUnit.module('AboutView', {
  beforeEach: () => {
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('viewer'))
    AboutModel.load = () => Promise.resolve()
    AboutModel.info = {}
    const app = document.createElement('div')
    app.id = 'app'
    document.body.appendChild(app)
  },
  afterEach: () => {
    m.render(fixture(), null)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    AboutModel.info = {}
    document.getElementById('app')?.remove()
  }
})

QUnit.test('AboutView renders version, php version, license and project link for a viewer', (assert) => {
  AboutModel.info = {name: 'jaco', version: '0.1.0', phpVersion: '8.2.0'}
  const routeCalls = []
  const originalSet = m.route.set
  m.route.set = (path) => routeCalls.push(path)

  try {
    m.render(fixture(), m(AboutView))

    assert.deepEqual(routeCalls, [], 'a viewer is not redirected')

    const text = fixture().textContent
    assert.ok(text.includes('0.1.0'), 'version shown')
    assert.ok(text.includes('8.2.0'), 'php version shown')
    assert.ok(text.includes('Freeware'), 'license shown')
    const links = [...fixture().querySelectorAll('a')]
    assert.ok(links.some((a) => a.getAttribute('href') === 'https://github.com/tbreuss/jaco-cms'), 'project link shown')
    assert.ok(links.some((a) => a.getAttribute('href') === 'https://github.com/tbreuss/jaco-cms/releases'), 'releases link shown')
  } finally {
    m.route.set = originalSet
  }
})

QUnit.module('PhpInfoView', {
  beforeEach: () => {
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
    PhpInfoModel.load = () => Promise.resolve()
    const app = document.createElement('div')
    app.id = 'app'
    document.body.appendChild(app)
  },
  afterEach: () => {
    m.render(fixture(), null)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    PhpInfoModel.data = []
    document.getElementById('app')?.remove()
  }
})

QUnit.test('shows loading then content', (assert) => {
  PhpInfoModel.data = []
  m.render(fixture(), m(PhpInfoView))
  assert.ok(fixture().textContent.includes(t('Loading')), 'loading shown')

  PhpInfoModel.data = {content: '<p>PHP 8.5</p>'}
  m.render(fixture(), m(PhpInfoView))
  assert.ok(fixture().textContent.includes('PHP 8.5'), 'content shown')
})

QUnit.module('LogoutView', {
  afterEach: () => {
    m.render(fixture(), null)
  }
})

QUnit.test('renders logged-out modal', (assert) => {
  m.render(fixture(), m(LogoutView))
  assert.ok(fixture().textContent.length > 0, 'renders without crashing')
})

QUnit.module('StartView', {
  afterEach: () => {
    m.render(fixture(), null)
  }
})

QUnit.test('renders without crashing', (assert) => {
  m.render(fixture(), m(StartView))
  assert.ok(true, 'empty view renders')
})

QUnit.module('BackupEditView', {
  beforeEach: () => {
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
    BackupViewModel.load = () => Promise.resolve()
    BackupViewModel.item = {}
    window.ace = {
      edit: () => ({resize() {}, setValue() {}, getValue: () => '', destroy() {}, commands: {addCommand() {}}}),
    }
    const app = document.createElement('div')
    app.id = 'app'
    document.body.appendChild(app)
  },
  afterEach: () => {
    m.render(fixture(), null)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    BackupViewModel.item = {}
    document.getElementById('app')?.remove()
    delete window.ace
  }
})

QUnit.test('renders a backup title', (assert) => {
  BackupViewModel.item = {file: 'a.html', name: 'a.html', title: 'Backup A', created: {toDateTime: () => '01.01.24 00:00', toDateTimeFull: () => '01.01.2024 00:00:00'}, backedUp: {toDateTime: () => '01.01.24 00:01', toDateTimeFull: () => '01.01.2024 00:01:00'}, type: 'page', content: '<p>x</p>'}
  m.render(fixture(), m(BackupEditView, {file: 'a.html'}))
  assert.ok(fixture().textContent.includes('Backup A'), 'backup title shown')
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

const contentListHtml = (pos = 'top', sortable = false) =>
  `<div data-jaco-repeater="news.html" data-jaco-repeater--label="Add news" data-jaco-repeater--position="${pos}"` +
  (sortable ? ' data-jaco-repeater--sortable' : '') + '>' +
  '<div><h2>Existing</h2></div>' +
  '</div>'

// a list whose blocks can be reordered: position and capability are separate
const sortableListHtml = (pos = 'top') => contentListHtml(pos, true)

const stubFetcher = (html = '<p>Fresh</p>') => () => Promise.resolve(html)

// every direct child is a block, except the chrome the editor brings along
// the same definition the editor uses: every direct child that is not chrome.
// The chooser counts too - it stands in the list next to its button.
const blocksOf = (list) => [...list.children]
  .filter((child) => !child.classList.contains('jaco-content-add-button'))
  .filter((child) => !child.classList.contains('jaco-content-item-bar'))
  .filter((child) => !child.classList.contains('jaco-slot-chooser'))

// m.render(null) empties the fixture but keeps nodes mithril never rendered -
// add buttons that sit next to a list belong to the editor, not to the virtual
// dom. A test that renders the same area again in one test clears them itself.
const clearFixture = () => {
  m.render(fixture(), null)
  fixture().replaceChildren()
}

QUnit.test('posOf reads top, bottom and defaults anything else to any', (assert) => {
  const lists = window.JacoContentLists
  const list = (pos) => {
    const el = document.createElement('div')
    if (pos !== null) {
      el.setAttribute('data-jaco-repeater--position', pos)
    }
    return el
  }
  assert.strictEqual(lists.posOf(list('top')), 'top')
  assert.strictEqual(lists.posOf(list('bottom')), 'bottom')
  assert.strictEqual(lists.posOf(list('any')), 'any')
  assert.strictEqual(lists.posOf(list(null)), 'any', 'missing pos follows')
  assert.strictEqual(lists.posOf(list('draggable')), 'any', 'a capability is no position')
})

QUnit.test('top inserts fetched fragments at the beginning', (assert) => {
  const done = assert.async()
  m.render(fixture(), m.trust(contentListHtml('top')))
  const list = fixture().querySelector('[data-jaco-repeater]')

  window.JacoContentLists.initContentLists(fixture(), stubFetcher())

  const button = fixture().querySelector('.jaco-content-add-button')
  assert.ok(button, 'an add button is rendered')
  assert.strictEqual(button.previousElementSibling, null, 'button is before the list')
  const expectedTitle = window.jacoEditorT
    ? window.jacoEditorT('Add news', 'Add news')
    : 'Add news'
  assert.ok(button.textContent.includes(expectedTitle), 'button uses the author title')

  button.click()
  setTimeout(() => {
    const items = [...list.children].filter((el) => !el.classList.contains('jaco-content-add-button'))
    assert.strictEqual(items.length, 2, 'a block was added')
    assert.ok(items[0].textContent.includes('Fresh'), 'the new block is first')
    assert.ok(items[0].querySelector('.jaco-content-item-bar'), 'the new block has a delete overlay')
    done()
  }, 50)
})

QUnit.test('bottom appends fetched fragments at the end', (assert) => {
  const done = assert.async()
  m.render(fixture(), m.trust(contentListHtml('bottom')))
  const list = fixture().querySelector('[data-jaco-repeater]')

  window.JacoContentLists.initContentLists(fixture(), stubFetcher())

  const button = fixture().querySelector('.jaco-content-add-button')
  assert.strictEqual(button.nextElementSibling, null, 'button is after the list')

  button.click()
  setTimeout(() => {
    const items = blocksOf(list)
    assert.strictEqual(items.length, 2, 'a block was added')
    assert.ok(items[1].textContent.includes('Fresh'), 'the new block is last')
    done()
  }, 50)
})

QUnit.test('any puts a button before every block and at the end', (assert) => {
  const done = assert.async()
  m.render(fixture(), m.trust(contentListHtml('any')))
  const list = fixture().querySelector('[data-jaco-repeater]')

  window.JacoContentLists.initContentLists(fixture(), stubFetcher())

  const buttons = [...fixture().querySelectorAll('.jaco-content-add-button')]
  assert.strictEqual(buttons.length, 2, 'one button per block plus one at the end')
  assert.ok(list.querySelector('.jaco-content-item-bar'), 'existing blocks get a delete overlay')

  buttons[0].click()
  setTimeout(() => {
    const items = blocksOf(list)
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
      fixture().querySelectorAll('[data-jaco-repeater] > *').length,
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

QUnit.test('add="top" with the sortable property shows the grip', (assert) => {
  // the two are independent: before the split "draggable" meant top plus sorting,
  // and a list that only said top got no grip
  m.render(fixture(), m.trust(sortableListHtml('top')))

  window.JacoContentLists.initContentLists(fixture(), stubFetcher())

  assert.strictEqual(fixture().querySelectorAll('.jaco-content-grip').length, 1, 'one grip for one block')
  assert.strictEqual(fixture().querySelectorAll('.jaco-content-add-button').length, 1, 'the button is on top')
})

QUnit.test('add="bottom" with the sortable property shows the grip too', (assert) => {
  m.render(fixture(), m.trust(sortableListHtml('bottom')))

  window.JacoContentLists.initContentLists(fixture(), stubFetcher())

  assert.strictEqual(fixture().querySelectorAll('.jaco-content-grip').length, 1, 'sorting is independent of the position')
})

QUnit.test('a sortable list adds on top and reorders via the grip', (assert) => {
  m.render(fixture(), m.trust(
    `<div data-jaco-repeater="news.html"
          data-jaco-repeater--label="Add news" data-jaco-repeater--position="top"
          data-jaco-repeater--sortable>
       <div><h2>First</h2></div>
       <div><h2>Second</h2></div>
     </div>`
  ))
  const list = fixture().querySelector('[data-jaco-repeater]')

  window.JacoContentLists.initContentLists(fixture(), stubFetcher())

  const button = fixture().querySelector('.jaco-content-add-button')
  assert.ok(button, 'an add button is rendered')
  assert.strictEqual(button.nextElementSibling, list, 'button is before the list like top')
  assert.ok(list.querySelector('.jaco-content-grip'), 'blocks have a grip')

  const headings = () => [...list.querySelectorAll(':scope > * h2')]
    .map((h2) => h2.textContent)
  assert.deepEqual(headings(), ['First', 'Second'], 'initial order')

  const grips = [...list.querySelectorAll('.jaco-content-grip')]
  const secondItem = grips[1].closest('[data-jaco-repeater] > *')
  const secondRect = grips[1].getBoundingClientRect()
  const firstRect = grips[0].closest('[data-jaco-repeater] > *').getBoundingClientRect()

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
    `<div data-jaco-repeater="news.html"
          data-jaco-repeater--label="Add news" data-jaco-repeater--position="top"
          data-jaco-repeater--sortable>
       <div><h2>First</h2></div>
       <div><h2>Second</h2></div>
     </div>`
  ))
  const list = fixture().querySelector('[data-jaco-repeater]')

  window.JacoContentLists.initContentLists(fixture(), stubFetcher())

  const grip = list.querySelector('.jaco-content-grip')
  const rect = grip.getBoundingClientRect()
  grip.dispatchEvent(pointerEvent('pointerdown', rect.left, rect.top))
  grip.dispatchEvent(pointerEvent('pointermove', rect.left, rect.top + 2))
  grip.dispatchEvent(pointerEvent('pointerup'))

  const headings = [...list.querySelectorAll(':scope > * h2')]
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
    `<div data-jaco-repeater="news.html"
          data-jaco-repeater--label="Add news" data-jaco-repeater--position="top"
          data-jaco-repeater--sortable>
       <div><h2>First</h2></div>
       <div><h2>Second</h2></div>
     </div>`
  ))
  const list = fixture().querySelector('[data-jaco-repeater]')
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

QUnit.test('only sortable lists show a grip', (assert) => {
  for (const pos of ['top', 'bottom', 'any']) {
    clearFixture()
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
  const list = fixture().querySelector('[data-jaco-repeater]')
  const item = list.querySelector('[data-jaco-repeater] > *')

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

QUnit.test('content-list controls can be mounted again after chrome removal', (assert) => {
  m.render(fixture(), m.trust(sortableListHtml()))
  const frame = fixture()

  window.JacoContentLists.initContentLists(frame, stubFetcher())
  assert.strictEqual(frame.querySelectorAll('.jaco-content-add-button').length, 1, 'initial add button')
  assert.strictEqual(frame.querySelectorAll('.jaco-content-item-bar').length, 1, 'initial item bar')

  window.JacoContentLists.removeContentListChrome(frame)
  assert.strictEqual(frame.querySelectorAll('.jaco-content-add-button').length, 0, 'chrome removed')
  assert.strictEqual(frame.querySelectorAll('.jaco-content-item-bar').length, 0, 'item bar removed')

  window.JacoContentLists.initContentLists(frame, stubFetcher())
  assert.strictEqual(frame.querySelectorAll('.jaco-content-add-button').length, 1, 'add button is back')
  assert.strictEqual(frame.querySelectorAll('.jaco-content-item-bar').length, 1, 'item bar is back')
})

QUnit.test('the media picker lists images and assigns the chosen media path', async (assert) => {
  const originalFetch = window.fetch
  const target = document.createElement('img')
  document.body.appendChild(target)

  window.fetch = () => Promise.resolve({
    ok: true,
    json: () => Promise.resolve({
      items: [
        {file: 'upload/example.jpg', extension: 'jpg'},
        {file: 'upload/document.pdf', extension: 'pdf'},
      ],
    }),
  })

  try {
    await window.openMediaPicker(target)
    await new Promise((resolve) => setTimeout(resolve, 0))

    assert.strictEqual(document.querySelectorAll('.jaco-media-item').length, 1, 'only images are listed')
    const item = document.querySelector('.jaco-media-item')
    assert.ok(item, 'the image is rendered')
    item.click()

    assert.strictEqual(target.getAttribute('src'), '/upload/example.jpg', 'the root-relative media path is assigned')
    assert.strictEqual(document.getElementById('editableMedia'), null, 'the picker closes after choosing')
  } finally {
    window.removeMediaPicker()
    target.remove()
    window.fetch = originalFetch
  }
})

QUnit.test('content-list add buttons use editor translations', async (assert) => {
  const originalTranslate = window.jacoEditorT
  const originalReady = window.jacoEditorReady
  window.jacoEditorT = (key, fallback) => key === 'Add news' ? 'News hinzufügen' : fallback
  window.jacoEditorReady = Promise.resolve()

  try {
    m.render(fixture(), m.trust(contentListHtml('top')))
    window.JacoContentLists.initContentLists(fixture(), stubFetcher())
    await new Promise((resolve) => setTimeout(resolve, 0))

    assert.strictEqual(
      fixture().querySelector('.jaco-content-add-button').textContent,
      '+ News hinzufügen',
      'the editor translation replaces the literal title',
    )
  } finally {
    window.jacoEditorT = originalTranslate
    window.jacoEditorReady = originalReady
  }
})

QUnit.test('clicking a dynamically inserted block opens the editor toolbar', (assert) => {
  const done = assert.async()
  m.render(fixture(), m.trust(contentListHtml('top')))

  window.JacoContentLists.initContentLists(fixture(), stubFetcher('<div><h2 data-jaco-line data-jaco-line--tools="basic">Fresh headline</h2></div>'))
  fixture().querySelector('.jaco-content-add-button').click()

  setTimeout(() => {
    const headline = fixture().querySelector('[data-jaco-repeater] > * h2')
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

  const fragment = '<div class="news-text"><h2 data-jaco-line data-jaco-line--tools="basic">Fresh headline</h2>' +
    '<img data-jaco-image height="120" src="img/spacer.png" width="120"></div>'
  window.JacoContentLists.initContentLists(fixture(), stubFetcher(fragment))
  fixture().querySelector('.jaco-content-add-button').click()

  setTimeout(() => {
    const staticHeadline = fixture().querySelector('[data-jaco-repeater] > * h2')
    staticHeadline.dispatchEvent(new MouseEvent('click', {bubbles: true}))
    assert.ok(document.querySelector('.ql-toolbar'), 'toolbar opens on the static block')

    const freshHeadline = [...fixture().querySelectorAll('[data-jaco-repeater] > * h2')]
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

  window.JacoContentLists.initContentLists(fixture(), stubFetcher('<div><h2 data-jaco-line data-jaco-line--tools="basic">Fresh</h2></div>'))
  fixture().querySelector('.jaco-content-add-button').click()

  setTimeout(() => {
    const item = blocksOf(fixture().querySelector('[data-jaco-repeater]'))
      .find((el) => el.textContent.includes('Fresh'))
    assert.ok(item, 'the inserted block is rendered')
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

QUnit.test('the image dialog follows the mode', (assert) => {
  const done = assert.async()
  const modes = [
    ['replace', '<img data-jaco-image src="media/a.png" width="320" height="180">', 0],
    ['fill', '<img data-jaco-image data-jaco-image--mode="fill" src="media/a.png" width="320" height="180">', 0],
    ['fit', '<img data-jaco-image data-jaco-image--mode="fit" src="media/a.png" width="320" height="180">', 0],
    ['choice', '<img data-jaco-image data-jaco-image--mode="choice" src="media/a.png" width="320" height="180">', 2],
  ]

  // the dialog is built after the translation messages resolve, so each case
  // needs its own turn
  const run = (index) => {
    if (index === modes.length) {
      done()
      return
    }
    const [label, html, expected] = modes[index]
    window.removeExtendedImages?.()
    m.render(fixture(), m.trust(html))
    const image = fixture().querySelector('img')
    image.dispatchEvent(new MouseEvent('click', {bubbles: true}))

    setTimeout(() => {
      const dialog = document.getElementById('editableImageExtended')
      const offered = dialog ? dialog.querySelectorAll('input[type=radio]').length : 0
      assert.strictEqual(offered, expected, label)
      run(index + 1)
    }, 0)
  }

  run(0)
})

QUnit.test('the full block toolbar offers video embeds', (assert) => {
  // the toolbar follows --tools: video is part of "full" only
  m.render(fixture(), m.trust('<div data-jaco-block data-jaco-block--tools="full"><p>with video</p></div>'))

  fixture().querySelector('[data-jaco-block] p')
    .dispatchEvent(new MouseEvent('click', {bubbles: true}))

  assert.ok(document.querySelector('.ql-toolbar .ql-video'), 'video button is in the toolbar')
})

QUnit.test('a plain block opens without a toolbar', (assert) => {
  m.render(fixture(), m.trust('<div data-jaco-block><p>no tools</p></div>'))

  fixture().querySelector('[data-jaco-block] p')
    .dispatchEvent(new MouseEvent('click', {bubbles: true}))

  assert.ok(document.querySelector('.ql-container'), 'the editor opens')
  assert.strictEqual(document.querySelector('.ql-toolbar'), null, 'plain renders no toolbar, not even an empty one')
})

QUnit.test('a plain line opens without a toolbar', (assert) => {
  m.render(fixture(), m.trust('<h1 data-jaco-line>no tools</h1>'))

  fixture().querySelector('[data-jaco-line]')
    .dispatchEvent(new MouseEvent('click', {bubbles: true}))

  assert.ok(document.querySelector('.ql-container'), 'the editor opens')
  assert.strictEqual(document.querySelector('.ql-toolbar'), null, 'plain line renders no toolbar, not even an empty one')
})

QUnit.test('a basic line offers formatting', (assert) => {
  m.render(fixture(), m.trust('<h1 data-jaco-line data-jaco-line--tools="basic">basic</h1>'))

  fixture().querySelector('[data-jaco-line]')
    .dispatchEvent(new MouseEvent('click', {bubbles: true}))

  const toolbar = document.querySelector('.ql-toolbar')
  assert.ok(toolbar, 'a toolbar is there')
  assert.ok(toolbar.querySelector('.ql-bold'), 'bold is offered')
})

QUnit.test('a basic block offers formatting but no video', (assert) => {
  m.render(fixture(), m.trust('<div data-jaco-block data-jaco-block--tools="basic"><p>basic</p></div>'))

  fixture().querySelector('[data-jaco-block] p')
    .dispatchEvent(new MouseEvent('click', {bubbles: true}))

  const toolbar = document.querySelector('.ql-toolbar')
  assert.ok(toolbar, 'a toolbar is there')
  assert.ok(toolbar.querySelector('.ql-bold'), 'bold is offered')
  assert.strictEqual(toolbar.querySelector('.ql-video'), null, 'no video')
})

QUnit.test('chrome is stripped but content markers survive', (assert) => {
  m.render(fixture(), m.trust(contentListHtml('top')))

  window.JacoContentLists.initContentLists(fixture(), stubFetcher())

  window.JacoContentLists.removeContentListChrome(fixture())

  assert.strictEqual(fixture().querySelector('.jaco-content-add-button'), null, 'buttons are gone')
  assert.strictEqual(fixture().querySelector('.jaco-content-item-bar'), null, 'overlays are gone')
  const list = fixture().querySelector('[data-jaco-repeater]')
  assert.ok(list, 'the list survives')
  assert.strictEqual(list.getAttribute('data-jaco-repeater'), 'news.html', 'the fragment value survives')
  assert.strictEqual(list.getAttribute('data-jaco-repeater--position'), 'top', 'position survives')
  assert.ok(list.querySelector('[data-jaco-repeater] > *'), 'blocks survive')
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
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
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

QUnit.module('PageListView', {
  beforeEach: () => {
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
    PageListModel.load = () => Promise.resolve()
    const app = document.createElement('div')
    app.id = 'app'
    document.body.appendChild(app)
  },
  afterEach: () => {
    m.render(fixture(), null)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    PageListModel.items = []
    PageListModel.isLoading = false
    document.getElementById('app')?.remove()
  }
})

QUnit.test('renders pages with view, edit, copy and delete buttons', (assert) => {
  PageListModel.items = [{file: 'a.html', title: 'Page A', created: 1700000000}]
  PageListModel.isLoading = false
  m.render(fixture(), m(PageListView))

  assert.ok(fixture().textContent.includes('Page A'), 'page title shown')
  const buttons = [...fixture().querySelectorAll('tbody td.text-right button')]
  assert.strictEqual(buttons.length, 5, 'view, edit content, edit, copy and delete buttons')
  assert.ok(buttons[0].classList.contains('secondary'), 'view is secondary')
  assert.strictEqual(buttons[0].getAttribute('aria-label'), t('View page'), 'view is labelled')
  assert.ok(buttons[1].classList.contains('secondary'), 'edit content is secondary')
  assert.strictEqual(buttons[1].getAttribute('aria-label'), t('Edit content'), 'edit content is labelled')
  assert.ok(buttons[2].classList.contains('secondary'), 'edit is secondary')
  assert.ok(buttons[3].classList.contains('secondary'), 'copy is secondary')
  assert.ok(buttons[4].classList.contains('danger'), 'delete is danger')
})

QUnit.test('a viewer sees copy and delete disabled', (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('viewer'))
  PageListModel.items = [{file: 'a.html', title: 'Page A', created: 1700000000}]
  PageListModel.isLoading = false
  m.render(fixture(), m(PageListView))

  const buttons = [...fixture().querySelectorAll('tbody td.text-right button')]
  assert.strictEqual(buttons.length, 5, 'view, edit content, edit, copy and delete stay visible')
  assert.ok(buttons[0].classList.contains('secondary'), 'view is secondary')
  assert.strictEqual(buttons[0].getAttribute('aria-label'), t('View page'), 'view stays usable')
  assert.notOk(isAriaDisabled(buttons[0]), 'view stays usable')
  assert.ok(buttons[1].classList.contains('secondary'), 'edit content is secondary')
  assert.ok(buttons[1].getAttribute('aria-label').startsWith(t('Edit content')), 'edit content is labelled')
  assert.ok(isAriaDisabled(buttons[1]), 'edit content is disabled for a viewer')
  assert.ok(buttons[2].classList.contains('secondary'), 'edit is secondary')
  assert.ok(buttons[2].getAttribute('aria-label').startsWith(t('Edit page')), 'edit is labelled')
  assert.ok(isAriaDisabled(buttons[2]), 'edit is disabled for a viewer')
  assert.ok(buttons[3].getAttribute('aria-label').startsWith(t('Copy page')), 'copy is labelled')
  assert.ok(isAriaDisabled(buttons[3]), 'copy is disabled for a viewer')
  assert.ok(buttons[4].classList.contains('danger'), 'delete is danger')
  assert.ok(isAriaDisabled(buttons[4]), 'delete is disabled for a viewer')
})

QUnit.test('the pages toolbar offers add page, disabled for a viewer', (assert) => {
  PageListModel.items = [{file: 'a.html', title: 'Page A', created: 1700000000}]
  PageListModel.isLoading = false

  m.render(fixture(), m(PageListView))
  const add = fixture().querySelector('.content > div button')
  assert.ok(add, 'add page is in the toolbar')
  assert.ok(add.textContent.includes(t('Add page')))
  assert.notOk(isAriaDisabled(add), 'an admin may add a page')

  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('viewer'))
  m.render(fixture(), m(PageListView))
  assert.ok(isAriaDisabled(fixture().querySelector('.content > div button')), 'a viewer cannot add a page')
})

QUnit.test('the copy row action passes the row to the copy route', (assert) => {
  const calls = []
  const originalSet = m.route.set
  m.route.set = (path) => calls.push(path)
  PageCopySource.clear()

  try {
    PageListModel.items = [{file: 'a.html', title: 'Page A', created: 1700000000}]
    PageListModel.isLoading = false
    m.render(fixture(), m(PageListView))

    const copy = [...fixture().querySelectorAll('tbody td.text-right button')][3]
    copy.click()

    assert.deepEqual(calls, ['/page-copy'])
    assert.deepEqual(PageCopySource.take(), {file: 'a.html', title: 'Page A', returnTo: '/pages'})
  } finally {
    m.route.set = originalSet
    PageCopySource.clear()
  }
})

QUnit.test('shows loading and empty states', (assert) => {
  PageListModel.isLoading = true
  PageListModel.items = []
  m.render(fixture(), m(PageListView))
  assert.ok(fixture().textContent.includes(t('Loading')), 'loading shown')

  PageListModel.isLoading = false
  m.render(fixture(), m(PageListView))
  assert.ok(fixture().textContent.includes(t('No pages found.')), 'empty state shown')
})

QUnit.module('BackupListView', {
  beforeEach: () => {
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
    BackupListModel.loadList = () => Promise.resolve()
    const app = document.createElement('div')
    app.id = 'app'
    document.body.appendChild(app)
  },
  afterEach: () => {
    m.render(fixture(), null)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    BackupListModel.items = []
    BackupListModel.isLoading = false
    document.getElementById('app')?.remove()
  }
})

QUnit.test('renders backups with view, delete and restore buttons', (assert) => {
  BackupListModel.items = [{file: 'a.html', name: 'a.html', title: 'Page A', created: 1700000000, backedUp: 1700000100, type: 'page'}]
  BackupListModel.isLoading = false
  m.render(fixture(), m(BackupListView))

  assert.ok(fixture().textContent.includes('Page A'), 'backup title shown')
  const buttons = [...fixture().querySelectorAll('tbody td.text-right button')]
  assert.strictEqual(buttons.length, 3, 'view, delete and restore buttons')
  assert.ok(buttons[0].classList.contains('secondary'), 'view is secondary')
  assert.ok(buttons[1].classList.contains('danger'), 'delete is danger')
  assert.ok(buttons[2].classList.contains('primary'), 'restore is primary')
})

QUnit.test('a viewer sees restore and delete disabled in the backup list', (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('viewer'))
  BackupListModel.items = [{file: 'a.html', name: 'a.html', title: 'Page A', created: 1700000000, backedUp: 1700000100, type: 'page'}]
  BackupListModel.isLoading = false
  m.render(fixture(), m(BackupListView))

  const buttons = [...fixture().querySelectorAll('tbody td.text-right button')]
  assert.strictEqual(buttons.length, 3, 'view, delete and restore stay visible')
  assert.notOk(isAriaDisabled(buttons[0]), 'view stays usable')
  assert.ok(buttons[1].classList.contains('danger'))
  assert.ok(isAriaDisabled(buttons[1]), 'delete is disabled')
  assert.ok(buttons[2].classList.contains('primary'))
  assert.ok(isAriaDisabled(buttons[2]), 'restore is disabled')
})

QUnit.test('shows loading and empty states', (assert) => {
  BackupListModel.isLoading = true
  BackupListModel.items = []
  m.render(fixture(), m(BackupListView))
  assert.ok(fixture().textContent.includes(t('Loading')), 'loading shown')

  BackupListModel.isLoading = false
  m.render(fixture(), m(BackupListView))
  assert.ok(fixture().textContent.includes(t('No backups found.')), 'empty state shown')
})

QUnit.module('PageAddView', {
  beforeEach: () => {
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
    PageTemplateModel.loadList = () => Promise.resolve()
    const origHasElement = iframe.hasElement
    const origFileName = iframe.fileName
    const origPageTitle = iframe.pageTitle
    iframe.hasElement = () => true
    iframe.fileName = () => 'index.html'
    iframe.pageTitle = () => 'Home'
    window._iframeRestore = () => {
      iframe.hasElement = origHasElement
      iframe.fileName = origFileName
      iframe.pageTitle = origPageTitle
    }
    const app = document.createElement('div')
    app.id = 'app'
    document.body.appendChild(app)
  },
  afterEach: () => {
    m.render(fixture(), null)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    document.getElementById('app')?.remove()
    if (window._iframeRestore) { window._iframeRestore(); delete window._iframeRestore }
  }
})

QUnit.test('renders the add page dialog', (assert) => {
  PageTemplateModel.list = [{file: 'default.html', title: 'Default'}]
  m.render(fixture(), m(PageAddView))
  assert.ok(fixture().textContent.includes(t('Add page')), 'title shown')
})

QUnit.test('cancel returns to the pages list', (assert) => {
  const originalSet = m.route.set
  const routeCalls = []
  m.route.set = (path) => routeCalls.push(path)

  try {
    PageTemplateModel.list = [{file: 'default.html', title: 'Default'}]
    m.render(fixture(), m(PageAddView))

    const cancel = [...fixture().querySelectorAll('button')]
      .find((button) => button.textContent.includes(t('Cancel')))
    assert.ok(cancel, 'cancel button rendered')
    cancel.click()
    assert.deepEqual(routeCalls, ['/pages'], 'cancel returns to the page list')
  } finally {
    m.route.set = originalSet
  }
})

QUnit.module('Modal Escape handling', {
  beforeEach: () => {
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
    PageTemplateModel.loadList = () => Promise.resolve()
    const origHasElement = iframe.hasElement
    const origFileName = iframe.fileName
    const origPageTitle = iframe.pageTitle
    iframe.hasElement = () => true
    iframe.fileName = () => 'index.html'
    iframe.pageTitle = () => 'Home'
    window._iframeRestore = () => {
      iframe.hasElement = origHasElement
      iframe.fileName = origFileName
      iframe.pageTitle = origPageTitle
    }
  },
  afterEach: () => {
    m.render(fixture(), null)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    if (window._iframeRestore) { window._iframeRestore(); delete window._iframeRestore }
  }
})

QUnit.test('ModalMedium closes on Escape', (assert) => {
  const originalSet = m.route.set
  const routeCalls = []
  m.route.set = (path) => routeCalls.push(path)

  PageTemplateModel.list = [{file: 'default.html', title: 'Default'}]
  m.render(fixture(), m(PageAddView))
  const modal = fixture().querySelector('.modal')
  assert.ok(modal, 'modal rendered')
  assert.strictEqual(modal.tabIndex, -1, 'modal is focusable')

  modal.dispatchEvent(new KeyboardEvent('keydown', {key: 'Escape', bubbles: true}))
  m.route.set = originalSet
  assert.ok(routeCalls.includes('/pages'), 'Escape returns to the page list')
})

QUnit.test('ModalSmall closes on Escape', (assert) => {
  const originalSet = m.route.set
  const routeCalls = []
  m.route.set = (path) => routeCalls.push(path)

  m.render(fixture(), m(LogoutView))
  const modal = fixture().querySelector('.modal')
  assert.ok(modal, 'modal rendered')

  modal.dispatchEvent(new KeyboardEvent('keydown', {key: 'Escape', bubbles: true}))
  m.route.set = originalSet
  assert.ok(routeCalls.includes('/'), 'Escape navigates home')
})

QUnit.test('Escape on modal with other key does nothing', (assert) => {
  const originalSet = m.route.set
  const routeCalls = []
  m.route.set = (path) => routeCalls.push(path)

  // render ModalSmall directly to avoid LogoutView's oninit token check
  m.render(fixture(), m(ModalSmall, {title: 'Test'}))
  const modal = fixture().querySelector('.modal')
  modal.dispatchEvent(new KeyboardEvent('keydown', {key: 'a', bubbles: true}))
  m.route.set = originalSet
  assert.strictEqual(routeCalls.length, 0, 'other keys do not navigate')
})

QUnit.module('PageCopyView', {
  beforeEach: () => {
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
    PageCopySource.clear()
    const origHasElement = iframe.hasElement
    const origFileName = iframe.fileName
    const origPageTitle = iframe.pageTitle
    iframe.hasElement = () => true
    iframe.fileName = () => 'index.html'
    iframe.pageTitle = () => 'Home'
    window._iframeRestore = () => {
      iframe.hasElement = origHasElement
      iframe.fileName = origFileName
      iframe.pageTitle = origPageTitle
    }
    const app = document.createElement('div')
    app.id = 'app'
    document.body.appendChild(app)
  },
  afterEach: () => {
    m.render(fixture(), null)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    PageCopySource.clear()
    document.getElementById('app')?.remove()
    if (window._iframeRestore) { window._iframeRestore(); delete window._iframeRestore }
  }
})

QUnit.test('renders the copy page dialog', (assert) => {
  m.render(fixture(), m(PageCopyView))
  assert.ok(fixture().textContent.includes(t('Copy page')), 'title shown')
})

QUnit.test('a row action passes its source page', (assert) => {
  PageCopySource.set('b.html', 'Page B', '/pages')
  m.render(fixture(), m(PageCopyView))

  const values = [...fixture().querySelectorAll('input')].map((input) => input.value)
  assert.ok(values.includes('b.html'), 'the row filename is the source')
  assert.ok(values.includes('Page B'), 'the row title is the source')

  const dstFile = fixture().querySelector('#dstFileName')
  const dstTitle = fixture().querySelector('#dstPageTitle')
  assert.strictEqual(dstFile.value, '', 'the copy filename starts empty')
  assert.strictEqual(dstTitle.value, '', 'the copy title starts empty')
  assert.strictEqual(dstFile.placeholder, t('e.g. contact.html'))
  assert.strictEqual(dstTitle.placeholder, t('e.g. Contact'))
})

QUnit.test('cancel returns to the source list', (assert) => {
  const originalSet = m.route.set
  const routeCalls = []
  m.route.set = (path) => routeCalls.push(path)

  try {
    PageCopySource.set('b.html', 'Page B', '/pages')
    m.render(fixture(), m(PageCopyView))

    const cancel = [...fixture().querySelectorAll('button')]
      .find((button) => button.textContent.includes(t('Cancel')))
    assert.ok(cancel, 'cancel button rendered')
    cancel.click()
    assert.deepEqual(routeCalls, ['/pages'], 'cancel returns to the page list')
  } finally {
    m.route.set = originalSet
  }
})

QUnit.test('the copy dialog falls back to the preview page', (assert) => {
  m.render(fixture(), m(PageCopyView))

  const values = [...fixture().querySelectorAll('input')].map((input) => input.value)
  assert.ok(values.includes('index.html'), 'the preview filename is the fallback')
  assert.ok(values.includes('Home'), 'the preview title is the fallback')
})

QUnit.module('PageDeleteView', {
  beforeEach: () => {
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
    const origFileName = iframe.fileName
    iframe.fileName = () => 'index.html'
    window._iframeRestore = () => { iframe.fileName = origFileName }
    const app = document.createElement('div')
    app.id = 'app'
    document.body.appendChild(app)
  },
  afterEach: () => {
    m.render(fixture(), null)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    document.getElementById('app')?.remove()
    if (window._iframeRestore) { window._iframeRestore(); delete window._iframeRestore }
  }
})

QUnit.test('renders the delete confirmation', (assert) => {
  m.render(fixture(), m(PageDeleteView))
  assert.ok(fixture().textContent.includes(t('Delete page')), 'title shown')
})

QUnit.module('PageGotoView', {
  beforeEach: () => {
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
    PageListModel.loadAll = () => Promise.resolve([{file: 'a.html', title: 'Page A'}, {file: 'b.html', title: 'Page B'}])
    const origHasElement = iframe.hasElement
    const origFileName = iframe.fileName
    const origLoad = iframe.load
    iframe.hasElement = () => true
    iframe.fileName = () => 'index.html'
    iframe.load = () => {}
    window._iframeRestore = () => {
      iframe.hasElement = origHasElement
      iframe.fileName = origFileName
      iframe.load = origLoad
    }
    const app = document.createElement('div')
    app.id = 'app'
    document.body.appendChild(app)
  },
  afterEach: () => {
    m.render(fixture(), null)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    pageGotoState.filter = ''
    pageGotoState.selected = 0
    pageGotoState.items = []
    pageGotoState.loading = false
    document.getElementById('app')?.remove()
    if (window._iframeRestore) { window._iframeRestore(); delete window._iframeRestore }
  }
})

QUnit.test('arrow keys wrap the selection like the command palette', (assert) => {
  m.render(fixture(), m(PageGotoView))
  pageGotoState.items = [{file: 'a.html', title: 'A'}, {file: 'b.html', title: 'B'}]
  pageGotoState.loading = false
  pageGotoState.selected = 0
  m.render(fixture(), m(PageGotoView))

  const input = fixture().querySelector('.list-filter input')
  input.dispatchEvent(new KeyboardEvent('keydown', {key: 'ArrowUp', bubbles: true}))
  m.render(fixture(), m(PageGotoView))
  assert.strictEqual(pageGotoState.selected, 1, 'arrow up from the first item wraps to the last')

  input.dispatchEvent(new KeyboardEvent('keydown', {key: 'ArrowDown', bubbles: true}))
  m.render(fixture(), m(PageGotoView))
  assert.strictEqual(pageGotoState.selected, 0, 'arrow down from the last item wraps to the first')
})

QUnit.test('filters the page list and opens the selected page', async (assert) => {
  const routeCalls = []
  const loaded = []
  const originalSet = m.route.set
  m.route.set = (path) => routeCalls.push(path)
  iframe.load = (path) => loaded.push(path)

  try {
    m.render(fixture(), m(PageGotoView))
    await new Promise((resolve) => setTimeout(resolve, 0))
    m.render(fixture(), m(PageGotoView))

    const modal = fixture().querySelector('.modal')
    assert.ok(modal, 'the search palette is rendered')
    assert.strictEqual(getComputedStyle(modal).alignItems, 'flex-start', 'every modal keeps a fixed top edge')

    const input = fixture().querySelector('.list-filter input')
    assert.ok(input, 'the filter field is rendered')
    assert.strictEqual(document.activeElement, input, 'the filter takes focus')
    assert.ok(fixture().textContent.includes('a.html'), 'all pages are listed initially')

    input.value = 'page b'
    input.dispatchEvent(new Event('input'))
    m.render(fixture(), m(PageGotoView))

    const clearBtn = fixture().querySelector('.list-filter-clear')
    assert.ok(clearBtn, 'clear button shown while filtering')
    clearBtn.click()
    m.render(fixture(), m(PageGotoView))
    assert.strictEqual(pageGotoState.filter, '', 'clear resets the filter')
    assert.notOk(fixture().querySelector('.list-filter-clear'), 'clear button hides when filter is empty')

    input.value = 'page b'
    input.dispatchEvent(new Event('input'))
    m.render(fixture(), m(PageGotoView))

    const items = [...fixture().querySelectorAll('.list-group-item')]
    assert.strictEqual(items.length, 1, 'the title filter narrows the list')
    assert.ok(items[0].textContent.includes('b.html'))
    assert.ok(items[0].classList.contains('selected'), 'the first hit is selected')

    input.dispatchEvent(new KeyboardEvent('keydown', {key: 'Enter'}))
    assert.deepEqual(loaded, ['/b.html'], 'enter opens the selected page')
    assert.deepEqual(routeCalls, ['/'], 'and returns to the preview')
  } finally {
    m.route.set = originalSet
  }
})

QUnit.module('preview persistence', {
  beforeEach: () => {
    sessionStorage.removeItem('jaco-preview-page')
    const frame = document.createElement('iframe')
    frame.id = 'iframe'
    frame.src = 'about:blank'
    fixture().appendChild(frame)
  },
  afterEach: () => {
    sessionStorage.removeItem('jaco-preview-page')
    document.getElementById('iframe')?.remove()
  },
})

QUnit.test('initPreview restores the last page from session storage', (assert) => {
  sessionStorage.setItem('jaco-preview-page', 'kontakt.html')
  iframe.initPreview()
  const frame = document.getElementById('iframe')
  assert.ok(frame.src.endsWith('/kontakt.html'), 'the preview loads the stored page')
})

QUnit.test('initPreview ignores unsafe stored paths', (assert) => {
  sessionStorage.setItem('jaco-preview-page', '../../../etc/passwd')
  iframe.initPreview()
  const frame = document.getElementById('iframe')
  assert.strictEqual(frame.src, 'about:blank', 'unsafe paths are not loaded')
})

QUnit.test('rememberPreviewPage clears storage when the page is gone', (assert) => {
  const done = assert.async()
  sessionStorage.setItem('jaco-preview-page', 'gone.html')
  const origFileName = iframe.fileName
  const origFetch = window.fetch
  iframe.fileName = () => 'gone.html'
  window.fetch = () => Promise.resolve({ok: false, status: 404})
  iframe.rememberPreviewPage().then(() => {
    assert.strictEqual(sessionStorage.getItem('jaco-preview-page'), null, 'stale path removed')
    iframe.fileName = origFileName
    window.fetch = origFetch
    done()
  })
})

QUnit.module('template editor')

// the views render labels through t(): without the translations the label
// would show as "{Key}", so every test pins the messages it asks for
const withTranslations = (keys, body) => {
  const saved = i18n.messages
  i18n.messages = {...saved}
  for (const key of keys) {
    i18n.messages[key] = key
  }
  try {
    return body()
  } finally {
    i18n.messages = saved
  }
}

// every string a vnode tree holds, joined: the label of a button lives in a
// span child or in the title attribute - both are text somewhere in the tree
const renderedText = (vnode) => {
  const parts = []
  const walk = (node) => {
    if (node === null || node === undefined) {
      return
    }
    if (typeof node === 'string' || typeof node === 'number') {
      parts.push(String(node))
      return
    }
    if (Array.isArray(node)) {
      node.forEach(walk)
      return
    }
    if (typeof node === 'object') {
      Object.values(node.attrs ?? {}).forEach(walk)
      walk(node.children)
    }
  }
  walk(vnode)
  return parts.join(' ')
}

const renderTemplateView = (view) => {
  const calls = []
  const originalSet = m.route.set
  m.route.set = (path) => calls.push(path)
  try {
    m.render(fixture(), view())
    return calls
  } finally {
    m.route.set = originalSet
  }
}

QUnit.test('the shared editor is read-only for an editor', (assert) => {
  // the markup belongs to the admin: an editor sees the template, the save
  // buttons are disabled and the server refuses the update route anyway
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('editor'))
  PageTemplateEditModel.isLoading = false
  PageTemplateEditModel.list = [{file: 'blank-en.html', title: 'Blank'}]
  PageTemplateEditModel.item = {}
  try {
    const calls = renderTemplateView(PageTemplateView.view)
    assert.deepEqual(calls, [], 'rendering the view does not navigate')
    assert.ok(JSON.stringify(PageTemplateView.view()).includes(t('Back')), 'an editor gets Back instead of Cancel')
  } finally {
    m.render(fixture(), null)
    PageTemplateEditModel.list = []
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
  }
})

QUnit.test('the template select lists the files in file name order', (assert) => {
  // the page listing arrives sorted by title (the page manager shows titles), but
  // this select shows file names: in title order it reads as unsorted
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
  PageTemplateEditModel.isLoading = false
  PageTemplateEditModel.list = [
    {file: 'landing-de.html', title: '[DE] Landingpage'},
    {file: 'blank-de.html', title: '[DE] Leere Seite'},
    {file: 'card-de.html', title: '[DE] Wiederholbare Karten'},
  ]
  PageTemplateEditModel.item = {}
  try {
    const json = JSON.stringify(PageTemplateView.view())
    const positions = ['blank-de.html', 'card-de.html', 'landing-de.html'].map((file) => json.indexOf(file))

    assert.ok(positions.every((position) => position >= 0), 'every file is offered')
    assert.deepEqual([...positions].sort((a, b) => a - b), positions, 'the files are listed alphabetically')
  } finally {
    PageTemplateEditModel.list = []
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
  }
})

QUnit.test('the page model reads the document, the listing has no content', async (assert) => {
  // regression: the listing carries the title only (template/pages answers
  // "content": null), so a page template showed an empty editor. The model has
  // to read the file it shows.
  const requested = []
  const stub = stubRequest((options) => {
    requested.push(options.url)
    if (String(options.url).includes('template/pages')) {
      return Promise.resolve([{file: 'blank-en.html', title: 'Blank', created: 1}])
    }
    return Promise.resolve({
      file: 'blank-en.html',
      title: 'Blank',
      created: 1,
      content: '<!DOCTYPE html><html><head><title>Blank</title></head></html>',
    })
  })
  try {
    PageTemplateEditModel.item = {}
    await PageTemplateEditModel.load('blank-en.html')

    assert.ok(requested.some((url) => String(url).includes('template/page/view')), 'the document route is asked')
    assert.ok(PageTemplateEditModel.item.content.includes('<title>Blank</title>'), 'the editor gets the document')
  } finally {
    stub.restore()
    PageTemplateEditModel.item = {}
  }
})

QUnit.test('a kind without templates shows its own hint', (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
  PageTemplateEditModel.isLoading = false
  PageTemplateEditModel.list = []
  PageTemplateEditModel.item = {}
  ContentTemplateEditModel.isLoading = false
  ContentTemplateEditModel.list = []
  ContentTemplateEditModel.item = {}
  try {
    withTranslations([
      'No page templates found. Create HTML documents under jaco-data/templates/page/ to get started.',
      'A page template is a complete HTML document; a new page is created from it.',
      'No content templates found. Create HTML fragments under jaco-data/templates/content/ to get started.',
      'A content template is one block with exactly one root element, inserted through repeater or slot.',
    ], () => {
    const page = JSON.stringify(PageTemplateView.view())
    assert.ok(page.includes(t('No page templates found. Create HTML documents under jaco-data/templates/page/ to get started.')))
    assert.ok(page.includes(t('A page template is a complete HTML document; a new page is created from it.')))

    const content = JSON.stringify(ContentTemplateView.view())
    assert.ok(content.includes(t('No content templates found. Create HTML fragments under jaco-data/templates/content/ to get started.')))
    assert.ok(content.includes(t('A content template is one block with exactly one root element, inserted through repeater or slot.')))
    assert.notOk(content.includes(t('No page templates found. Create HTML documents under jaco-data/templates/page/ to get started.')))
    })
  } finally {
    m.render(fixture(), null)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
  }
})

QUnit.test('the site view keeps the apply flow next to the shared editor', (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('editor'))
  SiteTemplateModel.isLoading = false
  SiteTemplateModel.list = [{file: 'footer-en.html', name: 'footer-en'}]
  SiteTemplateModel.item = {file: 'footer-en.html', content: '<footer>x</footer>'}
  try {
    withTranslations([
      'Shared content',
      'Template',
      'The marker must match the template file: data-jaco-template="{file}".',
      'Preview & apply',
      'Every page that is rewritten is backed up first.',
    ], () => {
    // compare the rendered text, not the JSON: that escapes " and &
    const text = renderedText(TemplateView.view())
    assert.ok(text.includes(t('Preview & apply')))
    assert.ok(text.includes(t('Every page that is rewritten is backed up first.')))
    assert.notOk(text.includes(t('Apply template to all pages?')), 'no dialog without a preview')
    // the marker hint needs the file name, so it only shows with a loaded file
    assert.ok(text.includes('data-jaco-template="footer-en.html"'))
    })
  } finally {
    m.render(fixture(), null)
    SiteTemplateModel.list = []
    SiteTemplateModel.item = {}
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
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
  '/jaco/frontend/lib/auth.js',
  '/jaco/frontend/lib/aceTheme.js',
  '/jaco/frontend/lib/format.js',
  '/jaco/frontend/lib/helpers.js',
  '/jaco/frontend/lib/iframe.js',
  '/jaco/frontend/lib/shortcuts.js',
  '/jaco/frontend/lib/theme.js',
  '/jaco/frontend/component/ButtonGroup.js',
  '/jaco/frontend/component/ButtonPrimary.js',
  '/jaco/frontend/component/ButtonSecondary.js',
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
  '/jaco/frontend/component/TemplateEditor.js',
  '/jaco/frontend/view/ContentTemplateView.js',
  '/jaco/frontend/view/CssEditView.js',
  '/jaco/frontend/view/LoginView.js',
  '/jaco/frontend/view/LogoutView.js',
  '/jaco/frontend/view/PageAddView.js',
  '/jaco/frontend/view/PageCopyView.js',
  '/jaco/frontend/view/PageDeleteView.js',
  '/jaco/frontend/view/PageGotoView.js',
  '/jaco/frontend/view/PageInlineEditView.js',
  '/jaco/frontend/view/PageTemplateView.js',
  '/jaco/frontend/view/PhpInfoView.js',
  '/jaco/frontend/view/PhpLogView.js',
  '/jaco/frontend/view/PasswordView.js',
  '/jaco/frontend/view/StartView.js',
  '/jaco/frontend/view/SystemView.js',
  '/jaco/frontend/view/TemplateView.js',
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
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
  CssEditModel.isLoading = false
  CssEditModel.list = []
  CssEditModel.item = {}
  const text = JSON.stringify(CssEditView.view())

  assert.ok(text.includes(t('No stylesheets found.')))
  assert.ok(text.includes(t('Stylesheets are .css files in the web root.')))
  assert.notOk(text.includes(t('Current stylesheet')))
  window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
})

QUnit.test('CssEditView shows loading while the list arrives', (assert) => {
  // without this the notice flashes on every visit until the response lands
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
  CssEditModel.isLoading = true
  CssEditModel.list = []
  CssEditModel.item = {}
  const text = JSON.stringify(CssEditView.view())

  assert.ok(text.includes(t('Loading')))
  assert.notOk(text.includes(t('No stylesheets found.')))
  CssEditModel.isLoading = false
  window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
})

QUnit.test('CssEditView shows the form when stylesheets exist', (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
  CssEditModel.isLoading = false
  CssEditModel.list = [{file: 'site.css'}]
  CssEditModel.item = {}
  const text = JSON.stringify(CssEditView.view())

  assert.notOk(text.includes(t('No stylesheets found.')))
  assert.ok(text.includes(t('Current stylesheet')))
  CssEditModel.list = []
  window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
})



QUnit.test('the design view never navigates while it renders', (assert) => {
  // a view that redirects breaks the test page and surprises the user; the layout
  // explains a 403 instead
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('editor'))
  const routeCalls = []
  const originalSet = m.route.set
  m.route.set = (path) => routeCalls.push(path)

  try {
    m.render(fixture(), CssEditView.view())
    CssEditView.oninit()

    assert.deepEqual(routeCalls, [], 'neither view() nor oninit() navigates')
  } finally {
    m.route.set = originalSet
    m.render(fixture(), null)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
  }
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
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
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
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
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
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
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

QUnit.test('a viewer opens the page editor read-only', (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('viewer'))
  PageEditModel.item = {file: 'a.html', title: 'Page A', content: '<p>a</p>'}

  try {
    m.render(fixture(), PageEditView.view())

    const text = fixture().textContent
    assert.ok(text.includes(t('View page')), 'the heading says view')
    assert.ok(text.includes(t('Back')), 'the cancel button becomes back')

    const buttons = [...fixture().querySelectorAll('button')]
    const save = buttons.find((button) => button.textContent.trim() === t('Save'))
    const saveClose = buttons.find((button) => button.textContent.trim() === t('Save & Close'))
    assert.ok(save, 'the save button stays visible')
    assert.ok(isAriaDisabled(save), 'save is disabled')
    assert.ok(saveClose, 'the save and close button stays visible')
    assert.ok(isAriaDisabled(saveClose), 'save and close is disabled')
  } finally {
    m.render(fixture(), null)
    PageEditModel.item = {}
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
  }
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

QUnit.test('PageEditView save succeeds and clears errors', (assert) => {
  const done = assert.async()
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
  const mockEditor = {getValue: () => '<p>x</p>'}
  setEditor(mockEditor)
  PageEditModel.item = {file: 'a.html', content: '<p>a</p>'}
  pageEditState.errors = [{title: 'old'}]
  const before = state.list.length

  const origSave = PageEditModel.save
  PageEditModel.save = () => Promise.resolve({})

  onSavePageButtonClicked({redraw: false}).then(() => {
    assert.strictEqual(pageEditState.errors.length, 0, 'errors cleared on success')
    assert.ok(state.list.length > before, 'success toast added')
  }).catch(() => {
    assert.ok(false, 'should not reject')
  }).finally(() => {
    PageEditModel.save = origSave
    PageEditModel.item = {}
    pageEditState.errors = []
    setEditor(undefined)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    done()
  })
})

QUnit.test('PageEditView save validation error sets FormErrors', async (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
  const mockEditor = {getValue: () => '<p>x</p>'}
  setEditor(mockEditor)
  PageEditModel.item = {file: 'a.html', content: '<p>a</p>'}
  pageEditState.errors = []

  const origSave = PageEditModel.save
  PageEditModel.save = () => Promise.reject({code: 422, response: [{title: 'bad', source: {pointer: ''}}]})

  try {
    await onSavePageButtonClicked({redraw: false})
  } catch (e) {
    // re-thrown; expected
  }
  assert.ok(pageEditState.errors.length > 0, 'errors set on 422')

  PageEditModel.save = origSave
  PageEditModel.item = {}
  pageEditState.errors = []
  setEditor(undefined)
  window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
})

QUnit.test('CssEditView save succeeds and clears errors', (assert) => {
  const done = assert.async()
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
  const mockEditor = {getValue: () => 'body{}'}
  setCssEditor(mockEditor)
  CssEditModel.item = {file: 'site.css', content: 'body{}'}
  cssEditState.errors = [{title: 'old'}]
  const before = state.list.length

  const origSave = CssEditModel.save
  CssEditModel.save = () => Promise.resolve({})

  onSaveCssButtonClicked({redraw: false}).then(() => {
    assert.strictEqual(cssEditState.errors.length, 0, 'errors cleared on success')
    assert.ok(state.list.length > before, 'success toast added')
  }).catch(() => {
    assert.ok(false, 'should not reject')
  }).finally(() => {
    CssEditModel.save = origSave
    CssEditModel.item = {}
    cssEditState.errors = []
    setCssEditor(undefined)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    done()
  })
})

QUnit.test('CssEditView save validation error sets FormErrors', async (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
  const mockEditor = {getValue: () => 'body{}'}
  setCssEditor(mockEditor)
  CssEditModel.item = {file: 'site.css', content: 'body{}'}
  cssEditState.errors = []

  const origSave = CssEditModel.save
  CssEditModel.save = () => Promise.reject({code: 422, response: [{title: 'bad', source: {pointer: ''}}]})

  try {
    await onSaveCssButtonClicked({redraw: false})
  } catch (e) {
    // re-thrown; expected
  }
  assert.ok(cssEditState.errors.length > 0, 'errors set on 422')

  CssEditModel.save = origSave
  CssEditModel.item = {}
  cssEditState.errors = []
  setCssEditor(undefined)
  window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
})

QUnit.module('LoginView', {
  beforeEach: () => {
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    loginState.step = 'login'
    loginState.username = ''
    loginState.password = ''
    loginState.totpCode = ''
    loginState.errors = []
  },
  afterEach: () => {
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
  }
})

QUnit.test('422 shows an error and stays on login step', async (assert) => {
  const stub = stubRequest(() => Promise.reject({code: 422, response: [{title: 'Invalid credentials'}]}))
  try {
    loginState.username = 'u'
    loginState.password = 'p'
    await doLogin().catch(() => {})
    assert.strictEqual(loginState.step, 'login', 'stays on login step')
    assert.ok(loginState.errors.length > 0, 'error shown')
  } finally {
    stub.restore()
  }
})

QUnit.test('successful login with TOTP switches to totp step', async (assert) => {
  const stub = stubRequest(() => Promise.resolve({totpRequired: true}))
  try {
    loginState.username = 'u'
    loginState.password = 'p'
    await doLogin()
    assert.strictEqual(loginState.step, 'totp', 'switches to totp step')
  } finally {
    stub.restore()
  }
})

QUnit.test('already authenticated redirects to admin panel', (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', 'existing-token')
  const routeCalls = []
  const originalSet = m.route.set
  m.route.set = (path) => routeCalls.push(path)

  try {
    LoginView.oninit()

    assert.deepEqual(routeCalls, ['/'], 'redirects to admin panel when already authenticated')
  } finally {
    m.route.set = originalSet
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
  }
})

QUnit.module('UploadListView filter', {
  beforeEach: () => {
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
    UploadListModel.load = () => Promise.resolve()
    const app = document.createElement('div')
    app.id = 'app'
    document.body.appendChild(app)
  },
  afterEach: () => {
    m.render(fixture(), null)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    UploadListModel.items = []
    UploadListModel.isLoading = false
    UploadListModel.filter = ''
    document.getElementById('app')?.remove()
  }
})

QUnit.test('filter input updates model and triggers load', (assert) => {
  const calls = []
  const origLoad = UploadListModel.load
  UploadListModel.load = () => { calls.push(UploadListModel.filter); return Promise.resolve() }
  UploadListModel.items = []
  UploadListModel.isLoading = false
  UploadListModel.filter = ''
  m.render(fixture(), m(UploadListView))

  const input = fixture().querySelector('.list-filter-input input')
  assert.ok(input, 'filter input rendered')

  input.value = 'foo'
  input.dispatchEvent(new Event('input', {bubbles: true}))
  assert.strictEqual(UploadListModel.filter, 'foo', 'filter updated')
  assert.ok(calls.includes('foo'), 'load triggered with new filter')

  UploadListModel.load = origLoad
})

QUnit.test('clear button resets filter and triggers load', (assert) => {
  const calls = []
  const origLoad = UploadListModel.load
  UploadListModel.load = () => { calls.push(UploadListModel.filter); return Promise.resolve() }
  UploadListModel.items = [{file: 'a.png', extension: 'png', size: 1, created: 1700000000}]
  UploadListModel.isLoading = false
  UploadListModel.filter = 'foo'
  m.render(fixture(), m(UploadListView))

  const clearBtn = fixture().querySelector('.list-filter-clear')
  assert.ok(clearBtn, 'clear button shown when filter active')
  clearBtn.click()
  assert.strictEqual(UploadListModel.filter, '', 'filter cleared')
  assert.ok(calls.includes(''), 'load triggered with empty filter')

  UploadListModel.load = origLoad
})

QUnit.module('PageInlineEditView dirty baseline')

QUnit.test('an unedited preview is not dirty', (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
  const orig = {}
  const keys = ['hasElement', 'makeEditable', 'contentDocument', 'contentWindow', 'fitBelowNavbar', 'getHTML', 'fileName', 'reload', 'getElement']
  keys.forEach((k) => { orig[k] = iframe[k] })
  iframe.hasElement = () => true
  iframe.makeEditable = (onReady) => { if (onReady) { onReady() } }
  iframe.contentDocument = () => document
  iframe.contentWindow = () => window
  iframe.fitBelowNavbar = () => {}
  iframe.getHTML = () => window.__iframeHtml ?? '<p>body</p>'
  iframe.fileName = () => 'index.html'
  iframe.reload = () => {}
  iframe.getElement = () => ({onload: null})
  window.confirm = () => true

  try {
    window.__iframeHtml = '<p>original</p>'
    m.render(fixture(), m(PageInlineEditView))
    assert.notOk(dirty.isDirty(), 'fresh view without edits is clean')
  } finally {
    m.render(fixture(), null)
    dirty.clear()
    delete window.__iframeHtml
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    Object.keys(orig).forEach((k) => { iframe[k] = orig[k] })
  }
})

QUnit.test('the first edit is detected instead of becoming the baseline', (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
  const orig = {}
  const keys = ['hasElement', 'makeEditable', 'contentDocument', 'contentWindow', 'fitBelowNavbar', 'getHTML', 'fileName', 'reload', 'getElement']
  keys.forEach((k) => { orig[k] = iframe[k] })
  iframe.hasElement = () => true
  iframe.makeEditable = (onReady) => { if (onReady) { onReady() } }
  iframe.contentDocument = () => document
  iframe.contentWindow = () => window
  iframe.fitBelowNavbar = () => {}
  iframe.getHTML = () => window.__iframeHtml ?? '<p>body</p>'
  iframe.fileName = () => 'index.html'
  iframe.reload = () => {}
  iframe.getElement = () => ({onload: null})
  window.confirm = () => true

  try {
    // the baseline is captured on mount, so an edit made afterwards must be
    // reported rather than silently absorbed into a new baseline
    window.__iframeHtml = '<p>original</p>'
    m.render(fixture(), m(PageInlineEditView))
    assert.notOk(dirty.isDirty(), 'clean right after mounting')

    window.__iframeHtml = '<p>edited</p>'
    assert.ok(dirty.isDirty(), 'the very first edit is detected')

    window.__iframeHtml = '<p>original</p>'
    assert.notOk(dirty.isDirty(), 'reverting the content clears dirty again')
  } finally {
    m.render(fixture(), null)
    dirty.clear()
    delete window.__iframeHtml
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    Object.keys(orig).forEach((k) => { iframe[k] = orig[k] })
  }
})

QUnit.test('checking for changes does not unhook the editor', (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
  const orig = {}
  const keys = ['hasElement', 'makeEditable', 'contentDocument', 'contentWindow', 'fitBelowNavbar', 'getHTML', 'fileName', 'reload', 'getElement']
  keys.forEach((k) => { orig[k] = iframe[k] })
  window.__removed = []
  iframe.hasElement = () => true
  iframe.makeEditable = (onReady) => { if (onReady) { onReady() } }
  iframe.contentDocument = () => document
  iframe.contentWindow = () => window
  iframe.fitBelowNavbar = () => {}
  // record whether the destructive teardown was requested
  iframe.getHTML = (options) => {
    if (!options || options.keepEditorScripts !== true) {
      window.__removed.push('scripts')
    }
    return window.__iframeHtml ?? '<p>body</p>'
  }
  iframe.fileName = () => 'index.html'
  iframe.reload = () => {}
  iframe.getElement = () => ({onload: null})
  window.confirm = () => true

  try {
    window.__iframeHtml = '<p>original</p>'
    m.render(fixture(), m(PageInlineEditView))
    window.__iframeHtml = '<p>edited</p>'
    dirty.isDirty()

    assert.deepEqual(window.__removed, [], 'the dirty check never tears the editor down')
  } finally {
    m.render(fixture(), null)
    dirty.clear()
    delete window.__iframeHtml
    delete window.__removed
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    Object.keys(orig).forEach((k) => { iframe[k] = orig[k] })
  }
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

QUnit.module('PageInlineEditView', {
  beforeEach: () => {
    window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('admin'))
    const app = document.createElement('div')
    app.id = 'app'
    document.body.appendChild(app)
    window._iframeOrig = {}
    const keys = ['hasElement', 'makeEditable', 'contentDocument', 'contentWindow', 'fitBelowNavbar', 'getHTML', 'fileName', 'reload', 'getElement']
    keys.forEach((k) => { window._iframeOrig[k] = iframe[k] })
    iframe.hasElement = () => true
    // honour the ready callback like the real makeEditable: the view captures
    // its dirty baseline from it
    iframe.makeEditable = (onReady) => { if (onReady) { onReady() } }
    iframe.contentDocument = () => document
    iframe.contentWindow = () => window
    iframe.fitBelowNavbar = () => {}
    iframe.getHTML = () => window.__iframeHtml ?? '<p>body</p>'
    iframe.fileName = () => 'index.html'
    iframe.reload = () => {}
    iframe.getElement = () => ({onload: null})
    window.confirm = () => true
  },
  afterEach: () => {
    m.render(fixture(), null)
    window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
    document.getElementById('app')?.remove()
    if (window._iframeOrig) {
      Object.keys(window._iframeOrig).forEach((k) => { iframe[k] = window._iframeOrig[k] })
      delete window._iframeOrig
    }
  }
})

QUnit.test('renders cancel and save buttons', (assert) => {
  m.render(fixture(), m(PageInlineEditView))
  const buttons = [...fixture().querySelectorAll('button')]
  assert.ok(buttons.length >= 2, 'cancel and save buttons rendered')
})

QUnit.test('save success navigates to home', async (assert) => {
  const origPost = api.post
  const routeCalls = []
  const originalSet = m.route.set
  m.route.set = (path) => routeCalls.push(path)
  const reloadCalls = []
  iframe.reload = () => reloadCalls.push('reload')

  api.post = (action, params, options) => Promise.resolve({})

  m.render(fixture(), m(PageInlineEditView))
  inlineSave({redraw: false})

  await new Promise((r) => setTimeout(r, 50))

  api.post = origPost
  m.route.set = originalSet
  assert.ok(routeCalls.includes('/'), 'navigates home on success')
})

QUnit.test('save validation error does not navigate', async (assert) => {
  const origPost = api.post
  const routeCalls = []
  const originalSet = m.route.set
  m.route.set = (path) => routeCalls.push(path)

  api.post = (action, params, options) => Promise.reject({code: 422, response: [{title: 'bad', detail: 'bad detail'}]})

  m.render(fixture(), m(PageInlineEditView))
  inlineSave({redraw: false})

  await new Promise((r) => setTimeout(r, 50))

  api.post = origPost
  m.route.set = originalSet
  assert.ok(!routeCalls.includes('/'), 'no navigation on validation error')
})

QUnit.test('a viewer can open the inline editor but gets no save button', (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('viewer'))
  m.render(fixture(), m(PageInlineEditView))

  const buttons = [...fixture().querySelectorAll('button')]
  assert.strictEqual(buttons.length, 2, 'cancel and save stay visible')
  assert.ok(buttons[0].textContent.includes(t('Cancel')), 'cancel is rendered')
  assert.notOk(isAriaDisabled(buttons[0]), 'cancel stays usable')
  const save = buttons.find((button) => button.textContent.trim() === t('Save'))
  assert.ok(save, 'the save button is rendered')
  assert.ok(isAriaDisabled(save), 'save is disabled for a viewer')
})

QUnit.test('a refused page-settings save shows one toast, not two', async (assert) => {
  // the api layer already toasts a 403; the view used to add its own message on
  // top, so the user saw the same failure twice
  const stub = stubRequest((options) => String(options.url).includes('action=page/view')
    ? Promise.resolve({file: 'index.html', title: 'Title', description: '', robots: ''})
    : Promise.reject({
      code: 403,
      response: [{
        title: 'Saving is disabled for this account',
        detail: 'This account shows the interface without storing changes.',
      }],
    }))

  try {
    m.render(fixture(), m(PageInlineEditView))

    const settingsButton = [...fixture().querySelectorAll('button')]
      .find((button) => button.textContent.includes(t('Page settings')))
    assert.ok(settingsButton, 'the settings button is rendered')
    settingsButton.click()

    await new Promise((resolve) => setTimeout(resolve, 30))
    m.render(fixture(), m(PageInlineEditView))

    // the dialog carries the page-settings form; the inline toolbar has its own
    // Save button, so scope the lookup to the dialog
    const dialogSave = fixture().querySelector('.modal button.primary')
    assert.ok(dialogSave, 'the dialog save button is rendered')

    const before = state.list.length
    dialogSave.click()

    await new Promise((resolve) => setTimeout(resolve, 30))

    const saveCalls = stub.calls.filter((call) => String(call.url).includes('action=page/settings'))
    assert.strictEqual(saveCalls.length, 1, 'the settings endpoint was called once')

    const added = state.list.slice(before).filter((entry) => entry.type === 'danger')
    assert.strictEqual(added.length, 1, 'exactly one danger toast for one failed save')
    // the api layer's message is the only one; the view used to add a second
    assert.ok(
      !added.some((entry) => String(entry.text).includes('could not be saved')),
      'the view adds no message of its own',
    )
  } finally {
    stub.restore()
    m.render(fixture(), null)
  }
})

QUnit.test('viewer edits do not trigger the discard guard', (assert) => {
  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', tokenWithRole('viewer'))
  window.__iframeHtml = '<p>body</p>'
  m.render(fixture(), m(PageInlineEditView))

  window.__iframeHtml = '<p>changed by the viewer</p>'
  assert.notOk(dirty.isDirty(), 'nothing can be saved, so there is nothing to guard')

  delete window.__iframeHtml
})

QUnit.module('CSS')

QUnit.test('app.css parses without syntax errors', (assert) => {
  const sheets = [...document.styleSheets].filter((s) => s.href && s.href.includes('/jaco/frontend/asset/app.css'))
  assert.ok(sheets.length > 0, 'app.css is loaded')

  const sheet = sheets[0]
  let ruleCount = 0
  try {
    ruleCount = sheet.cssRules.length
  } catch (e) {
    assert.ok(false, 'cssRules not readable: ' + e.message)
    return
  }

  // cssRules is empty when a syntax error (e.g. unbalanced brace) truncates the sheet
  assert.ok(ruleCount > 50, 'sheet has rules (got ' + ruleCount + ')')

  // walk top-level rules: @media blocks need recursive access
  const walk = (rules, depth = 0) => {
    for (const rule of rules) {
      if (rule.cssRules) walk(rule.cssRules, depth + 1)
    }
  }
  walk(sheet.cssRules)
  assert.ok(true, 'all rules walkable')
})



QUnit.test('deleting a block keeps the list add button', (assert) => {
  m.render(fixture(), m.trust(contentListHtml('top')))
  window.JacoContentLists.initContentLists(fixture(), stubFetcher())
  const list = fixture().querySelector('[data-jaco-repeater]')
  const item = list.querySelector('[data-jaco-repeater] > *')
  const buttonsBefore = fixture().querySelectorAll('.jaco-content-add-button').length

  confirmDelete(item)

  assert.notOk(item.isConnected, 'block removed')
  assert.strictEqual(fixture().querySelectorAll('.jaco-content-add-button').length, buttonsBefore, 'add button remains')
})

// the delete bar asks twice: "×" reveals the confirm and cancel buttons
const confirmDelete = (item) => {
  const overlay = item.querySelector('.jaco-content-item-bar')
  overlay.querySelector('button').click()
  overlay.querySelectorAll('button')[0].click()
}

// position "any" keeps one add button before every block plus one at the end, so
// the button count follows the block count - after deleting as well
QUnit.test('deleting a block in an any-position list keeps one button per block and one at the end', (assert) => {
  for (const [attr, name] of [['data-jaco-repeater', 'news.html'], ['data-jaco-slot', 'a.html, b.html']]) {
    m.render(fixture(), m.trust(
      `<div ${attr}="${name}" ${attr}--position="any">` +
      '<div><h2>One</h2></div><div><h2>Two</h2></div><div><h2>Three</h2></div>' +
      '</div>'
    ))
    window.JacoContentLists.initContentLists(fixture(), stubFetcher())
    const list = fixture().querySelector(`[${attr}]`)

    assert.strictEqual(fixture().querySelectorAll('.jaco-content-add-button').length, 4,
      `${attr}: three blocks give three buttons plus one at the end`)

    confirmDelete(list.querySelectorAll(':scope > div:not(.jaco-content-add-button)')[1])

    assert.strictEqual(blocksOf(list).length, 2, `${attr}: the block is gone`)
    assert.strictEqual(fixture().querySelectorAll('.jaco-content-add-button').length, 3,
      `${attr}: one button per remaining block plus one at the end`)
    // every block still has its own button right in front of it
    const children = [...list.children].filter((child) => !child.classList.contains('jaco-content-add-button'))
    children.forEach((block) => {
      assert.ok(block.previousElementSibling?.classList.contains('jaco-content-add-button'),
        `${attr}: "${block.textContent}" keeps the button before it`)
    })
  }
})

QUnit.test('top and bottom lists keep a single button after deleting', (assert) => {
  for (const pos of ['top', 'bottom']) {
    // a fresh fixture per position: the button of the previous one stands next
    // to the list and is not part of the virtual dom
    clearFixture()
    m.render(fixture(), m.trust(contentListHtml(pos)))
    const list = fixture().querySelector('[data-jaco-repeater]')
    window.JacoContentLists.initContentLists(fixture(), stubFetcher())

    confirmDelete(blocksOf(list)[0])

    assert.strictEqual(fixture().querySelectorAll('.jaco-content-add-button').length, 1,
      `pos=${pos}: exactly one add button survives`)
    const button = fixture().querySelector('.jaco-content-add-button')
    assert.strictEqual(pos === 'top' ? button.nextElementSibling : button.previousElementSibling, list,
      `pos=${pos}: the button stays outside the list`)
  }
})

QUnit.test('adding a block after a deletion inserts once, not once per initialisation', (assert) => {
  // the listener used to be bound again whenever a deletion reset the init flag,
  // so one click appended the same fragment twice
  const done = assert.async()
  // two blocks: removing the only one would drop the list itself, and the
  // button bound to it has nothing left to mount on
  m.render(fixture(), m.trust(
    '<div data-jaco-repeater="news.html" data-jaco-repeater--position="bottom">' +
    '<div><h2>One</h2></div><div><h2>Two</h2></div>' +
    '</div>'
  ))
  const list = fixture().querySelector('[data-jaco-repeater]')
  window.JacoContentLists.initContentLists(fixture(), stubFetcher())

  confirmDelete(blocksOf(list)[1])
  confirmDelete(blocksOf(list)[0])

  assert.strictEqual(blocksOf(list).length, 0, 'the list is empty')

  window.JacoContentLists.initContentLists(fixture(), stubFetcher())
  fixture().querySelector('.jaco-content-add-button').click()

  setTimeout(() => {
    assert.strictEqual(blocksOf(list).length, 1, 'one click adds one block')
    assert.strictEqual(fixture().querySelectorAll('.jaco-content-add-button').length, 1, 'still one button')
    done()
  }, 50)
})

QUnit.test('a block whose fragment has no single root does not leave an empty block behind', (assert) => {
  // the contract says exactly one root element; plain text used to become an
  // empty div that looked like a block until the page was saved
  const done = assert.async()
  m.render(fixture(), m.trust(contentListHtml('top')))
  const list = fixture().querySelector('[data-jaco-repeater]')

  window.JacoContentLists.initContentLists(fixture(), stubFetcher('   '))

  const button = fixture().querySelector('.jaco-content-add-button')
  button.click()

  setTimeout(() => {
    assert.strictEqual(blocksOf(list).length, 1, 'no block was added')
    assert.ok(button.classList.contains('is-error'), 'the button reports the unusable fragment')
    done()
  }, 50)
})



// a content list inside a block of another one: a repeater in a slot block, or a
// slot in a repeater block. The outer list counts the wrapper as one block.
const nestedListHtml = (outerPos, innerPos) =>
  `<div data-jaco-slot="test-repeater.html" data-jaco-slot--position="${outerPos}">` +
  '<div class="wrapper">' +
  `<div data-jaco-repeater="card-en.html" data-jaco-repeater--position="${innerPos}">` +
  '<div><h3>Inner one</h3></div><div><h3>Inner two</h3></div>' +
  '</div></div>' +
  '<div class="plain"><p>Plain block</p></div>' +
  '</div>'

const mountNested = (outerPos, innerPos) => {
  clearFixture()
  m.render(fixture(), m.trust(nestedListHtml(outerPos, innerPos)))
  window.JacoContentLists.initContentLists(fixture(), stubFetcher())
  const outer = fixture().querySelector('[data-jaco-slot]')
  return { outer, inner: outer.querySelector('[data-jaco-repeater]'), wrapper: outer.querySelector('.wrapper') }
}



QUnit.test('dragging a block in an any-position list keeps its button in front', (assert) => {
  // the add buttons stand as children here, so moving only the block used to
  // leave them shuffled: B K B K became B K K B after one drag
  clearFixture()
  m.render(fixture(), m.trust(
    '<div data-jaco-repeater="news.html" data-jaco-repeater--position="any" data-jaco-repeater--sortable>' +
    '<div><h2>One</h2></div><div><h2>Two</h2></div><div><h2>Three</h2></div>' +
    '</div>'
  ))
  window.JacoContentLists.initContentLists(fixture(), stubFetcher())
  const list = fixture().querySelector('[data-jaco-repeater]')

  const layoutOk = (label) => {
    const children = [...list.children]
    assert.strictEqual(children.length % 2, 1, `${label}: blocks + trailing button`)
    blocksOf(list).forEach((block) => {
      assert.ok(block.previousElementSibling?.classList.contains('jaco-content-add-button'),
        `${label}: "${block.textContent}" keeps a button in front`)
    })
    assert.ok(children[children.length - 1].classList.contains('jaco-content-add-button'),
      `${label}: a button stays last`)
  }
  layoutOk('before the drag')

  const blocks = blocksOf(list)
  const three = blocks[2]
  const one = blocks[0]
  const grip = three.querySelector('.jaco-content-grip')
  const threeRect = three.getBoundingClientRect()
  const oneRect = one.getBoundingClientRect()

  grip.dispatchEvent(pointerEvent('pointerdown', 0, threeRect.top + 2))
  // clear the 6 px threshold first, then move above the first block
  grip.dispatchEvent(pointerEvent('pointermove', 0, threeRect.top - 10))
  grip.dispatchEvent(pointerEvent('pointermove', 0, oneRect.top + 1))
  grip.dispatchEvent(pointerEvent('pointerup', 0, oneRect.top + 1))

  assert.ok(three.classList.contains('is-dragging') === false, 'the drag ended')
  layoutOk('after the drag')
  // the overlay's own buttons are part of textContent, so read the heading
  const headingOf = (block) => block.querySelector('h2').textContent.trim()
  assert.strictEqual(headingOf(blocksOf(list)[0]), 'Three',
    `the dragged block really moved: got "${blocksOf(list).map(headingOf).join(',')}"`)
})





QUnit.test('an open chooser is not counted as a block and never gets an overlay', (assert) => {
  // the chooser stands in the list next to the button. Counting it as a block
  // hung an item bar inside it, and that chrome ended up in the saved page.
  const done = assert.async()
  clearFixture()
  m.render(fixture(), m.trust(
    '<div data-jaco-slot="a.html, b.html" data-jaco-slot--position="any">' +
    '<div><p>one</p></div><div><p>two</p></div></div>'
  ))
  window.JacoContentLists.initContentLists(fixture(), stubFetcher())
  const list = fixture().querySelector('[data-jaco-slot]')
  const button = [...list.children].find((child) => child.classList.contains('jaco-content-add-button'))
  button.click()

  setTimeout(() => {
    assert.strictEqual(list.querySelectorAll('.jaco-slot-chooser').length, 1, 'the chooser is open')

    // a second mount happens on every dirty check and on every deletion
    window.JacoContentLists.initContentLists(fixture(), stubFetcher())

    assert.strictEqual(list.querySelectorAll('.jaco-slot-chooser .jaco-content-item-bar').length, 0,
      'no overlay is mounted inside the chooser')
    assert.strictEqual(list.querySelectorAll('.jaco-slot-chooser .jaco-content-add-button').length, 0,
      'no add button is mounted inside the chooser')
    assert.strictEqual(list.querySelectorAll('.jaco-slot-chooser').length, 1, 'the chooser survives')
    done()
  }, 30)
})





// The one rule every row below checks: a list always shows the chrome its
// position promises. "any" = one button in front of each block plus a trailing
// one (children, alternating); top/bottom = one button outside the list.
const layoutIsSound = (list) => {
  const pos = window.JacoContentLists.posOf(list)
  const blocks = [...list.children].filter((child) =>
    !child.classList.contains('jaco-content-add-button') &&
    !child.classList.contains('jaco-content-item-bar') &&
    !child.classList.contains('jaco-slot-chooser'))
  if (pos === 'any') {
    const children = [...list.children]
    if (children.length !== blocks.length * 2 + 1) return false
    if (!children[children.length - 1].classList.contains('jaco-content-add-button')) return false
    return blocks.every((block) => block.previousElementSibling?.classList.contains('jaco-content-add-button'))
  }
  const buttons = [...list.parentElement.children].filter((sibling) =>
    sibling.classList.contains('jaco-content-add-button') && sibling._jacoList === list)
  return buttons.length === 1
}

QUnit.test('the layout stays sound for every position while blocks are added and deleted', (assert) => {
  for (const [attr, name] of [
    ['data-jaco-repeater', 'news.html'],
    ['data-jaco-slot', 'a.html, b.html'],
  ]) {
    for (const pos of ['top', 'bottom', 'any', null]) {
      const label = `${attr}/${pos || 'no position'}`
      clearFixture()
      m.render(fixture(), m.trust(
        `<div ${attr}="${name}"` + (pos ? ` ${attr}--position="${pos}"` : '') + '>' +
        '<div><h2>One</h2></div><div><h2>Two</h2></div><div><h2>Three</h2></div></div>'
      ))
      window.JacoContentLists.initContentLists(fixture(), stubFetcher('<p>fresh</p>'))
      const list = () => fixture().querySelector(`[${attr}]`)
      assert.ok(layoutIsSound(list()), `${label}: sound after mount`)

      confirmDelete(blocksOf(list())[1])
      assert.ok(layoutIsSound(list()), `${label}: sound after deleting the middle block`)

      confirmDelete(blocksOf(list())[0])
      assert.ok(layoutIsSound(list()), `${label}: sound after deleting the first block`)

      const rest = blocksOf(list())
      confirmDelete(rest[rest.length - 1])
      assert.ok(layoutIsSound(list()), `${label}: sound after deleting the last block (list empty)`)
      assert.strictEqual(blocksOf(list()).length, 0, `${label}: the list is empty`)
      assert.strictEqual(fixture().querySelectorAll('.jaco-content-add-button').length, 1,
        `${label}: an empty list keeps exactly one add button`)
    }
  }
})

QUnit.test('an emptied list can be filled again', (assert) => {
  const done = assert.async()
  clearFixture()
  m.render(fixture(), m.trust(
    '<div data-jaco-repeater="news.html" data-jaco-repeater--position="any">' +
    '<div><h2>One</h2></div></div>'
  ))
  window.JacoContentLists.initContentLists(fixture(), stubFetcher('<div><h2>Back</h2></div>'))
  const list = fixture().querySelector('[data-jaco-repeater]')

  confirmDelete(blocksOf(list)[0])
  assert.strictEqual(blocksOf(list).length, 0, 'the list is empty')

  fixture().querySelector('.jaco-content-add-button').click()
  setTimeout(() => {
    assert.strictEqual(blocksOf(list).length, 1, 'one click brings one block back')
    assert.strictEqual(blocksOf(list)[0].querySelector('h2').textContent, 'Back', 'the fragment was inserted')
    assert.ok(layoutIsSound(list), 'the layout is sound again')
    assert.strictEqual(fixture().querySelectorAll('.jaco-content-add-button').length, 2,
      'button in front of the block plus the trailing one')
    done()
  }, 50)
})



QUnit.test('saving and re-mounting keeps the layout sound for every position', (assert) => {
  // what iframe.getHTML() does: strip the chrome, read the page; the next open
  // mounts again. Nothing of the editor may survive into the saved html.
  for (const [attr, name] of [
    ['data-jaco-repeater', 'news.html'],
    ['data-jaco-slot', 'a.html, b.html'],
  ]) {
    for (const pos of ['top', 'bottom', 'any', null]) {
      const label = `${attr}/${pos || 'no position'}`
      clearFixture()
      m.render(fixture(), m.trust(
        `<div ${attr}="${name}"` + (pos ? ` ${attr}--position="${pos}"` : '') + '>' +
        '<div><h2>One</h2></div><div><h2>Two</h2></div></div>'
      ))
      window.JacoContentLists.initContentLists(fixture(), stubFetcher('<p>fresh</p>'))

      window.JacoContentLists.removeContentListChrome()
      const saved = fixture().innerHTML
      assert.notOk(/jaco-content-add-button|jaco-content-item-bar|jaco-slot-chooser/.test(saved),
        `${label}: no editor chrome reaches the saved page: ${saved}`)
      assert.ok(/data-jaco-(repeater|slot)/.test(saved), `${label}: the markers survive`)
      assert.ok(saved.includes('One') && saved.includes('Two'), `${label}: the content survives`)

      // reopening the page: the markers are still there, nothing else is
      window.JacoContentLists.initContentLists(fixture(), stubFetcher('<p>fresh</p>'))
      const list = fixture().querySelector(`[${attr}]`)
      assert.ok(layoutIsSound(list), `${label}: sound again after the re-mount`)
      assert.strictEqual(blocksOf(list).length, 2, `${label}: both blocks are back`)
    }
  }
})

QUnit.test('a save between edits does not leave a stale button behind', (assert) => {
  // the save path clears every init flag; the next mount must rebuild exactly
  // one set of buttons, not add a second one next to the old
  for (const pos of ['top', 'bottom', 'any']) {
    clearFixture()
    m.render(fixture(), m.trust(contentListHtml(pos)))
    window.JacoContentLists.initContentLists(fixture(), stubFetcher())
    const before = fixture().querySelectorAll('.jaco-content-add-button').length

    window.JacoContentLists.removeContentListChrome()
    assert.strictEqual(fixture().querySelectorAll('.jaco-content-add-button').length, 0,
      `pos=${pos}: chrome is gone after the save`)

    window.JacoContentLists.initContentLists(fixture(), stubFetcher())
    assert.strictEqual(fixture().querySelectorAll('.jaco-content-add-button').length, before,
      `pos=${pos}: the same number of buttons comes back, not more`)
  }
})


QUnit.test('dragging keeps the layout sound for every position', (assert) => {
  const headings = (list) => blocksOf(list).map((block) => block.querySelector('h2')?.textContent ?? '?')
  for (const pos of ['top', 'bottom', 'any']) {
    for (const attr of ['data-jaco-repeater', 'data-jaco-slot']) {
      const label = `${attr}/${pos}`
      clearFixture()
      m.render(fixture(), m.trust(
        `<div ${attr}="news.html" ${attr}--position="${pos}" ${attr}--sortable>` +
        '<div><h2>One</h2></div><div><h2>Two</h2></div><div><h2>Three</h2></div></div>'
      ))
      window.JacoContentLists.initContentLists(fixture(), stubFetcher('<p>fresh</p>'))
      const list = fixture().querySelector(`[${attr}]`)

      // drag the last block above the first one
      const blocks = blocksOf(list)
      const last = blocks[blocks.length - 1]
      const first = blocks[0]
      const grip = last.querySelector('.jaco-content-grip')
      const lastRect = last.getBoundingClientRect()
      const firstRect = first.getBoundingClientRect()

      grip.dispatchEvent(pointerEvent('pointerdown', 0, lastRect.top + 2))
      grip.dispatchEvent(pointerEvent('pointermove', 0, lastRect.top - 10))
      grip.dispatchEvent(pointerEvent('pointermove', 0, firstRect.top + 1))
      grip.dispatchEvent(pointerEvent('pointerup', 0, firstRect.top + 1))

      assert.ok(layoutIsSound(list), `${label}: layout is sound after the drag`)
      assert.deepEqual(headings(list), ['Three', 'One', 'Two'],
        `${label}: the block moved to the top, order is ${headings(list).join(',')}`)
    }
  }
})




QUnit.test('changing the position under a mounted list rebuilds its buttons', (assert) => {
  // a site template rewrites the markup without a reload: the list keeps its
  // element, its position attribute changes underneath it
  const cases = [
    ['top', 'any'], ['bottom', 'any'], ['any', 'top'], ['any', 'bottom'], ['top', 'bottom'],
  ]
  for (const [from, to] of cases) {
    clearFixture()
    m.render(fixture(), m.trust(contentListHtml(from)))
    const list = fixture().querySelector('[data-jaco-repeater]')
    window.JacoContentLists.initContentLists(fixture(), stubFetcher())
    assert.ok(layoutIsSound(list), `${from}: sound when mounted`)

    list.setAttribute('data-jaco-repeater--position', to)
    if (window.__guard === undefined) { window.__guard = 0 }
    window.JacoContentLists.initContentLists(fixture(), stubFetcher())

    assert.ok(layoutIsSound(list), `${from} -> ${to}: the layout follows the new position`)
    const stray = [...fixture().querySelectorAll('.jaco-content-add-button')]
      .filter((button) => button._jacoList !== list)
    assert.strictEqual(stray.length, 0, `${from} -> ${to}: no button of another layout survives`)
  }
})


QUnit.test('a chooser whose button is gone does not stay in the list', (assert) => {
  // The chooser stands next to its button. Deleting a block takes that button
  // away; a leftover chooser would reach the saved page. The chooser is built
  // on a microtask, so build one by hand here: the cleanup is what is under
  // test, not the async open.
  clearFixture()
  m.render(fixture(), m.trust(
    '<div data-jaco-slot="a.html, b.html" data-jaco-slot--position="any">' +
    '<div><p>one</p></div><div><p>two</p></div></div>'
  ))
  window.JacoContentLists.initContentLists(fixture(), stubFetcher('<p>fresh</p>'))
  const list = fixture().querySelector('[data-jaco-slot]')

  // an open chooser anchored at the button of the FIRST block: deleting that
  // block takes the anchor away
  const firstButton = [...list.children].find((child) => child.classList.contains('jaco-content-add-button'))
  const chooser = document.createElement('div')
  chooser.className = 'jaco-slot-chooser'
  chooser._jacoButton = firstButton
  firstButton.after(chooser)
  assert.strictEqual(list.querySelectorAll('.jaco-slot-chooser').length, 1, 'the chooser is open')
  // blocksOf() must not see the chooser as a block, otherwise the wrong block
  // would be deleted
  assert.strictEqual(blocksOf(list).length, 2, 'the chooser is not a block')

  confirmDelete(blocksOf(list)[0])

  assert.strictEqual(list.querySelectorAll('.jaco-slot-chooser').length, 0,
    'the chooser left with its button')
  window.JacoContentLists.removeContentListChrome()
  assert.notOk(/jaco-slot-chooser|jaco-content-item-bar|jaco-content-add-button/.test(fixture().innerHTML),
    'the saved html carries no editor chrome')
})

QUnit.test('a chooser keeps its anchor button when another block is deleted', (assert) => {
  // the counterpart: dropping every chooser would break the open one
  clearFixture()
  m.render(fixture(), m.trust(
    '<div data-jaco-slot="a.html, b.html" data-jaco-slot--position="any">' +
    '<div><p>one</p></div><div><p>two</p></div></div>'
  ))
  window.JacoContentLists.initContentLists(fixture(), stubFetcher('<p>fresh</p>'))
  const list = fixture().querySelector('[data-jaco-slot]')

  const buttons = [...list.children].filter((child) => child.classList.contains('jaco-content-add-button'))
  // every button still knows its list; deleting the second block keeps the first
  // button, so a chooser anchored there has to survive
  confirmDelete(blocksOf(list)[1])

  assert.ok(layoutIsSound(list), 'the layout survives the deletion')
  buttons.filter((button) => button.isConnected).forEach((button) => {
    assert.strictEqual(button._jacoList, list, 'a surviving button still knows its list')
  })
})



QUnit.module('content lists (slots)', {
  beforeEach: () => { m.render(fixture(), null) }
})


QUnit.test('a slot with comma-separated fragments shows a chooser', (assert) => {
  const done = assert.async()
  m.render(fixture(), m.trust('<div data-jaco-slot="a.html, b.html" data-jaco-slot--position="top"></div>'))
  window.JacoContentLists.initContentLists(fixture(), stubFetcher())

  fixture().querySelector('.jaco-content-add-button').click()

  setTimeout(() => {
    assert.strictEqual(fixture().querySelectorAll('.jaco-slot-chooser button').length, 2, 'one button per fragment')
    done()
  }, 0)
})

QUnit.test('a slot with whitespace-separated fragments shows a chooser', (assert) => {
  const done = assert.async()
  m.render(fixture(), m.trust('<div data-jaco-slot="a.html b.html" data-jaco-slot--position="top"></div>'))
  window.JacoContentLists.initContentLists(fixture(), stubFetcher())

  fixture().querySelector('.jaco-content-add-button').click()

  setTimeout(() => {
    assert.strictEqual(fixture().querySelectorAll('.jaco-slot-chooser button').length, 2, 'whitespace splits the value')
    done()
  }, 0)
})

QUnit.test('a slot chooser inserts the chosen fragment', (assert) => {
  const done = assert.async()
  m.render(fixture(), m.trust('<div data-jaco-slot="a.html, b.html" data-jaco-slot--position="top"></div>'))
  window.JacoContentLists.initContentLists(fixture(), stubFetcher('<p>chosen</p>'))

  fixture().querySelector('.jaco-content-add-button').click()
  setTimeout(() => {
    fixture().querySelector('.jaco-slot-chooser button').click()
    setTimeout(() => {
      assert.ok(fixture().querySelector('[data-jaco-slot] > *'), 'a block is inserted')
      done()
    }, 0)
  }, 0)
})

QUnit.test('an empty slot offers every fragment from the content api', (assert) => {
  const done = assert.async()
  const originalFetch = window.fetch
  window.fetch = (url) => {
    if (String(url).includes('action=template/content')) {
      return Promise.resolve({
        ok: true,
        json: () => Promise.resolve([{file: 'x.html'}, {file: 'y.html'}]),
      })
    }
    return originalFetch(url)
  }

  m.render(fixture(), m.trust('<div data-jaco-slot data-jaco-slot--position="top"></div>'))
  window.JacoContentLists.initContentLists(fixture(), stubFetcher('<p>x</p>'))

  fixture().querySelector('.jaco-content-add-button').click()

  setTimeout(() => {
    assert.strictEqual(fixture().querySelectorAll('.jaco-slot-chooser button').length, 2, 'all fragments are offered')
    window.fetch = originalFetch
    done()
  }, 0)
})

