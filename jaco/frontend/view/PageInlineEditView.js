import {api} from '../service/api.js'
import {iframe} from '../lib/iframe.js'
import {addDanger, addSuccess} from '../state/actions.js'
import {t, tError} from '../model/i18n.js'
import {ButtonGroup} from '../component/ButtonGroup.js'
import {ButtonPrimary} from '../component/ButtonPrimary.js'
import {ButtonSecondary} from '../component/ButtonSecondary.js'
import {app} from '../lib/app.js'
import {bindSaveShortcut, saveShortcutLabel} from '../lib/shortcuts.js'

const save = (e) => {
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
  iframe.reload()
  m.route.set('/')
}

let unbindSaveShortcuts = []

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
    if (!app.hasAuthToken()) {
      m.route.set('/logout')
    }
    // i.e. if page was reloaded with given route
    if (!iframe.hasElement()) {
      m.route.set('/')
    }
    iframe.makeEditable()
    bindShortcut()
    // the listener lives on the iframe document and dies with it on navigation
    iframe.getElement().onload = bindShortcut
  },
  onremove: () => {
    unbindSaveShortcuts.forEach((unbind) => unbind())
    unbindSaveShortcuts = []
    iframe.getElement().onload = null
  },
  view: () => m(ButtonGroup, {wrap: 'div.dialog'},
    m(ButtonPrimary, {onclick: save, title: t('Save') + ' (' + saveShortcutLabel() + ')'}, t('Save')),
    m(ButtonSecondary, {onclick: cancel}, t('Cancel')),
  )
}
