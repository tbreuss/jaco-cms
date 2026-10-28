import {api} from '../service/api.js'
import {iframe} from '../lib/iframe.js'
import {addDanger, addSuccess} from '../state/actions.js'
import {t, tError} from '../model/i18n.js'
import {ButtonGroup} from '../component/ButtonGroup.js'
import {ButtonPrimary} from '../component/ButtonPrimary.js'
import {ButtonSecondary} from '../component/ButtonSecondary.js'
import {PageSettingsDialog} from '../component/PageSettingsDialog.js'
import {Icon} from '../component/Icon.js'
import {app} from '../lib/app.js'
import {auth} from '../lib/auth.js'
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
  // a read-only account may try the editor, but nothing can be saved: do not trap them in
  // a discard dialog for changes that would be thrown away anyway
  if (!auth.can('editor') || savedBody === null) {
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
  if (!auth.can('editor')) {
    return
  }
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

const pageSettings = {
  open: false,
  page: null,
  errors: [],
  saving: false,
}

const openPageSettings = (e) => {
  e.redraw = false
  pageSettings.open = true
  pageSettings.page = null
  pageSettings.errors = []
  api.get('page/view', {file: iframe.fileName()})
    .then((data) => {
      pageSettings.page = data
      m.redraw()
    })
    .catch(({code, response}) => {
      pageSettings.open = false
      if (code === 422) {
        response.forEach(function (error) {
          addDanger(error.detail ? tError(error.detail) : t(error.title, null, error.title))
        })
      }
      m.redraw()
    })
}

const closePageSettings = () => {
  pageSettings.open = false
  pageSettings.page = null
  pageSettings.errors = []
  m.redraw()
}

const changePageSetting = (field, value) => {
  if (!pageSettings.page) {
    return
  }
  pageSettings.page[field] = value
  pageSettings.errors = []
  m.redraw()
}

const savePageSettings = (e) => {
  e.redraw = false
  if (!pageSettings.page) {
    return
  }
  pageSettings.saving = true
  pageSettings.errors = []
  api.post('page/settings', {}, {
    background: true,
    body: {
      fileName: iframe.fileName(),
      title: pageSettings.page.title ?? '',
      description: pageSettings.page.description ?? '',
      robots: pageSettings.page.robots ?? '',
    },
    withCredentials: false,
  })
    .then(() => {
      pageSettings.saving = false
      addSuccess(t('Page settings were successfully saved.'))
      closePageSettings()
    })
    .catch(({code, response}) => {
      pageSettings.saving = false
      // validation errors belong under the fields; everything else is toasted by the
      // api layer already - a second toast would repeat the same message
      // validation errors belong under the fields; everything else is toasted by the
      // api layer already - a second toast would repeat the same message
      if (code === 422) {
        pageSettings.errors = Array.isArray(response) ? response : []
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
  unbindSaveShortcuts = []
  // a read-only account has no save: binding Ctrl+S would only offer a shortcut that
  // silently does nothing
  if (!auth.can('editor')) {
    return
  }
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
    pageSettings.open = false
    pageSettings.page = null
    pageSettings.errors = []
    pageSettings.saving = false
    window.addEventListener('beforeunload', beforeUnloadHandler)
    // drop a baseline left over from a previous visit, so an unchanged preview
    // is not compared against it
    savedBody = null
    dirty.set(isDirty)
    if (!app.hasAuthToken()) {
      m.route.set('/logout')
      return
    }
    // every role may open the editor; without editor rights the save button is
    // disabled and the shortcuts are not bound, and the global read-only banner
    // says why
    // i.e. if page was reloaded with given route
    if (!iframe.hasElement()) {
      m.route.set('/')
      return
    }
    iframe.makeEditable(captureSavedBody)
    bindShortcut()
    setEditLayout(true)
    iframe.fitBelowNavbar()
    // the listener lives on the iframe document and dies with it on navigation
    iframe.getElement().onload = bindShortcut
  },
  onremove: () => {
    pageSettings.open = false
    pageSettings.page = null
    window.removeEventListener('beforeunload', beforeUnloadHandler)
    dirty.clear()
    unbindSaveShortcuts.forEach((unbind) => unbind())
    unbindSaveShortcuts = []
    setEditLayout(false)
    iframe.fitBelowNavbar()
    iframe.getElement().onload = null
  },
  view: () => m('div', [
    m(ButtonGroup, {wrap: 'div.dialog'},
      auth.can('editor') && m(ButtonSecondary, {onclick: openPageSettings},
        m(Icon, {name: 'edit'}), m('span', t('Page settings'))),
      m(ButtonSecondary, {onclick: cancel}, m(Icon, {name: 'clear'}), m('span', t('Cancel'))),
      m(ButtonPrimary, {
        onclick: save,
        disabled: !auth.can('editor'),
        title: auth.can('editor') ? t('Save') + ' (' + saveShortcutLabel() + ')' : t('Save') + ' – ' + t('Your role does not allow this.'),
      }, m(Icon, {name: 'save'}), m('span', t('Save'))),
    ),
    pageSettings.open && m(PageSettingsDialog, {
      page: pageSettings.page,
      errors: pageSettings.errors,
      saving: pageSettings.saving,
      onchange: changePageSetting,
      onsave: savePageSettings,
      onclose: closePageSettings,
    })
  ])
}
