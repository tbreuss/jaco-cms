import { t } from '../models/i18n.js'

export const Login = {
  username: '',
  password: '',
  view: function () {
    return m('.modal',
      m('.modal-sm',
        m('h2', t('Login')),
        m('.row',
          m('input[type=text]', {
            value: Login.username,
            placeholder: t('Username'),
            oninput: function (e) {
              Login.username = e.target.value
            }
          })
        ),
        m('.row',
          m('input[type=password]', {
            value: Login.password,
            placeholder: t('Password'),
            oninput: function (e) {
              Login.password = e.target.value
            }
          })
        ),
        m('.row',
          m('button.submit', {
            onclick: function (e) {
              window.sessionStorage.setItem('authenticated', true)
              m.route.set('/')
            }
          }, t('Login'))
        )
      )
    )
  }
}
