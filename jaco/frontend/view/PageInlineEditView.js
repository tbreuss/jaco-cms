import {api} from '../service/api.js'
import {iframe} from '../lib/iframe.js'
import {addDanger, addSuccess} from '../state/actions.js'
import {t, tError} from '../model/i18n.js'
import {ButtonGroup} from '../component/ButtonGroup.js'
import {ButtonPrimary} from '../component/ButtonPrimary.js'
import {ButtonSecondary} from '../component/ButtonSecondary.js'
import {Icon} from '../component/Icon.js'
import {app} from '../lib/app.js'
import {bindSaveShortcut, saveShortcutLabel} from '../lib/shortcuts.js'
import {dirty} from '../lib/dirty.js'

// Content as it was when the view opened. Captured from makeEditable's ready
// hook, never lazily: a lazy capture runs on the first navigation attempt, by
// which time the user has already typed, and that edit would pass for the clean
// baseline and be lost. null means "not captured yet" (editors still loading),
// which counts as clean rather than blocking the user on a half-built editor.
let savedBody = null

// keepEditorScripts: reading must not unhook the editor. Plain getHTML() removes
// the editor scripts and chrome, which would disable editing mid-session.
const readBody = () => iframe.getHTML({keepEditorScripts: true})

const captureSavedBody = () => {
  try { savedBody = readBody() } catch (e) { savedBody = null }
}

const isDirty = () => {
  if (savedBody === null) {
    return false
  }
  try { return readBody() !== savedBody } catch (e) { return false }
}

const beforeUnloadHandler = (e) => {
  if (isDirty()) {
    e.preventDefault()
    e.returnValue = ''
  }
}

export const save = (e) => {
  e.redraw = false
  api.post('page/update-body', {}, {
    background: true, // no redraw
    body: {
      fileName: iframe.fileName(),
      body: iframe.getHTML()
    },
    withCredentials: false
  })
    .then(() => {
      savedBody = iframe.getHTML()
      iframe.reload()
      addSuccess(t('Page was successfully saved.'))
      m.route.set('/')
    })
    .catch(({code, response}) => {
      if (code === 422) {
        response.forEach(function (error) {
          addDanger(error.detail ? tError(error.detail) : t(error.title, null, error.title)) // TODO better error handling (we have no place to render errors atm)
        })
      }
      m.redraw()
    })
}

const cancel = (e) => {
  e.redraw = false
  if (isDirty() && !confirm(t('Discard unsaved changes?', null, 'Discard unsaved changes?'))) {
    return
  }
  dirty.clear()
  iframe.reload()
  m.route.set('/')
}

let unbindSaveShortcuts = []

// In edit mode the admin chrome and the preview share one column: the chrome
// keeps its natural height on top, the iframe fills exactly the rest, without
// a page scrollbar. Pure CSS (see body.page-edit in asset/app.css), this only
// switches the class.
const setEditLayout = (on) => {
  document.body.classList.toggle('page-edit', on)
}

const bindShortcut = () => {
  unbindSaveShortcuts.forEach((unbind) => unbind())
  // keydown does not cross document boundaries: listen in the parent
  // document (toolbar, buttons) as well as inside the iframe (content)
  unbindSaveShortcuts = [
    bindSaveShortcut(document, {
      onSave: () => save({redraw: false}),
    }),
    bindSaveShortcut(iframe.contentDocument(), {
      onSave: () => save({redraw: false}),
    }),
  ]
}

export const PageInlineEditView = {
  oninit: () => {
    window.addEventListener('beforeunload', beforeUnloadHandler)
    // drop a baseline left over from a previous visit, so an unchanged preview
    // is not compared against it
    savedBody = null
    dirty.set(isDirty)
    if (!app.hasAuthToken()) {
      m.route.set('/logout')
    }
    // i.e. if page was reloaded with given route
    if (!iframe.hasElement()) {
      m.route.set('/')
    }
    iframe.makeEditable(captureSavedBody)
    bindShortcut()
    setEditLayout(true)
    iframe.fitBelowNavbar()
    // the listener lives on the iframe document and dies with it on navigation
    iframe.getElement().onload = bindShortcut
  },
  onremove: () => {
    window.removeEventListener('beforeunload', beforeUnloadHandler)
    dirty.clear()
    unbindSaveShortcuts.forEach((unbind) => unbind())
    unbindSaveShortcuts = []
    setEditLayout(false)
    iframe.fitBelowNavbar()
    iframe.getElement().onload = null
  },
  view: () => m(ButtonGroup, {wrap: 'div.dialog'},
    m(ButtonSecondary, {onclick: cancel}, m(Icon, {name: 'clear'}), m('span', t('Cancel'))),
    m(ButtonPrimary, {onclick: save, title: t('Save') + ' (' + saveShortcutLabel() + ')'}, m(Icon, {name: 'save'}), m('span', t('Save'))),
  )
}
