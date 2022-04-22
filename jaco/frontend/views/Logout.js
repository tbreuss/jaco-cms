import { t } from '../models/i18n.js'

export const Logout = {
  oninit: () => {
    if (window.sessionStorage.getItem('AUTHORIZATION_TOKEN')) {
      window.sessionStorage.removeItem('AUTHORIZATION_TOKEN')
      m.route.set('/logout')
    }
  },
  view: function () {
    return m('.modal',
      m('.modal-sm',
        m('h2', t('Logged out')),
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
    )
  }
}
