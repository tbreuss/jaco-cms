import {api} from '../service/api.js'
import {iframe} from '../lib/iframe.js'
import {addDanger, addSuccess} from '../state/actions.js'
import {t} from '../model/i18n.js'
import {ButtonGroup} from '../component/ButtonGroup.js'
import {ButtonPrimary} from '../component/ButtonPrimary.js'
import {ButtonSecondary} from '../component/ButtonSecondary.js'
import {app} from '../lib/app.js'

const save = (e) => {
  e.redraw = false
  api.post('action=page/update', {
    background: true, // no redraw
    body: {
      fileName: iframe.fileName(),
      html: iframe.getHTML()
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
          addDanger(error.title) // TODO better error handling (we have no place to render errors atm)
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
  },
  view: () => m(ButtonGroup, {wrap: 'div.dialog'},
    m(ButtonPrimary, {onclick: save}, t('Save')),
    m(ButtonSecondary, {onclick: cancel}, t('Cancel')),
  )
}
