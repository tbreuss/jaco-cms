import { t } from '../model/i18n.js'
import { ModalSmall } from '../component/ModalSmall.js'

export const Logout = {
  oninit: () => {
    if (window.sessionStorage.getItem('AUTHORIZATION_TOKEN')) {
      window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
      m.route.set('/logout')
    }
  },
  oncreate: () => {
    document.documentElement.style.visibility = 'visible'
  },
  view: () => m(ModalSmall, { title: t('Logged out') },
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
