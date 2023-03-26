import {t} from '../model/i18n.js'
import {ModalSmall} from '../component/ModalSmall.js'
import {app} from '../lib/app.js'

export const LogoutView = {
  oninit: () => {
    if (app.hasAuthToken()) {
      app.removeAuthToken()
      m.route.set('/logout')
    }
  },
  view: () => m(ModalSmall, {title: t('Logged out')},
    m('ul.unstyled',
      m('li', m('a', {
        href: '..'
      }, t('Goto homepage'))),
      m('li', m(m.route.Link, {
        class: 'login',
        href: '/login'
      }, t('Login again')))
    )
  )
}
