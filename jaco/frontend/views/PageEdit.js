import { api } from '../services/api.js'
import { iframe } from '../lib/iframe.js'
import { addSuccess } from '../state/actions.js'
import { addDanger } from '../state/actions.js'
import { t } from '../models/i18n.js'
import { ButtonGroup } from '../components/ButtonGroup.js'
import { ButtonPrimary } from '../components/ButtonPrimary.js'
import { ButtonSecondary } from '../components/ButtonSecondary.js'

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
    .catch(({ code, response }) => {
      if (code === 422) {
        response.errors.forEach(function(error) {
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

export const PageEdit = {
  oninit: () => {
    if (!window.sessionStorage.getItem('AUTHORIZATION_TOKEN')) {
      m.route.set('/logout')
    }
    // i.e. if page was reloaded with given route
    if (!document.getElementById('iframe')) {
      m.route.set('/')
    }
    iframe.makeEditable()
  },
  oncreate: () => {
    document.documentElement.style.visibility = 'visible'
  },
  view: () => m(ButtonGroup, { wrap: 'div.dialog' },
    m(ButtonPrimary, { onclick: save }, t('Save')),
    m(ButtonSecondary, { onclick: cancel }, t('Cancel')),
  )
}
