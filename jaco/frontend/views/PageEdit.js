import { iframe } from '../lib/iframe.js'
import { addSuccess } from '../state/actions.js'
import { t } from '../models/i18n.js'
import { ButtonGroup } from '../components/ButtonGroup.js'
import { ButtonPrimary } from '../components/ButtonPrimary.js'
import { ButtonSecondary } from '../components/ButtonSecondary.js'

const save = () => {
  const fileName = iframe.fileName()
  const html = iframe.getHTML()
  iframe.reload()
  console.debug(fileName, html)
  addSuccess(t('Page was successfully saved.'))
  m.route.set('/')
}

const cancel = () => {
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
  view: () => m(ButtonGroup, { wrap: 'div.dialog' },
    m(ButtonPrimary, { onclick: save }, t('Save')),
    m(ButtonSecondary, { onclick: cancel }, t('Cancel'))
  )
}
