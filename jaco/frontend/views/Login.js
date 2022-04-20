import { t } from '../models/i18n.js'

export const Login = {
    username: '',
    password: '',
    view: function () {
        return m('.modal',
            m('.modal-inner',
                m('h1', t('Login')),
                m('div',
                    m('input[type=text]', {
                        value: Login.username,
                        placeholder: t('Username'),
                        oninput: function (e) {
                            Login.username = e.target.value
                        }
                    })
                ),
                m('div',
                    m('input[type=password]', {
                        value: Login.password,
                        placeholder: t('Password'),
                        oninput: function (e) {
                            Login.password = e.target.value
                        }
                    })
                ),
                m('button', {
                    onclick: function (e) {
                        window.sessionStorage.setItem('authenticated', true)
                        m.route.set('/')
                    }
                }, t('Login'))
            )
        )
    }
}