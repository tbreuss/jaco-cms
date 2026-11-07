import {ButtonGroup} from '../component/ButtonGroup.js'
import {ButtonPrimary} from '../component/ButtonPrimary.js'
import {ButtonSecondary} from '../component/ButtonSecondary.js'
import {i18n, t} from '../model/i18n.js'
import {ModalSmall} from '../component/ModalSmall.js'
import {app} from '../lib/app.js'

export const LogoutView = {
  oninit: () => {
    if (app.hasAuthToken()) {
      app.removeAuthToken()
      // without a token there is no user language: back to the default
      i18n.loadAndSetLocale()
      m.route.set('/logout')
    }
  },
  view: () => m(ModalSmall, {title: t('Logged out')},
    m('div.logout-actions',
      m(ButtonSecondary, {type: 'button', onclick: () => m.route.set('/')}, t('Go to homepage')),
      m(ButtonPrimary, {type: 'button', onclick: () => m.route.set('/login')}, t('Login again'))
    )
  )
}
