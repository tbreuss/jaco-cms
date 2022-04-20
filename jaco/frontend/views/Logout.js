import { t } from '../models/i18n.js'

export const Logout = {
    view: function () {
        return m('.modal',
            m('.modal-inner',
                m('h1', t('Logged out')),
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
