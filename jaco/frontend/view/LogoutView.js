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
    m('ul.unstyled',
      m('li', m('a', {
        href: '..'
      }, t('Go to homepage'))),
      m('li', m(m.route.Link, {
        class: 'login',
        href: '/login'
      }, t('Login again')))
    )
  )
}
