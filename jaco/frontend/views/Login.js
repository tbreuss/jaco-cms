import { api } from '../services/api.js'
import { t } from '../models/i18n.js'

export const Login = {
  username: '',
  password: '',
  error: '',
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
              Login.error = ''
            }
          })
        ),
        m('.row',
          m('input[type=password]', {
            value: Login.password,
            placeholder: t('Password'),
            oninput: function (e) {
              Login.password = e.target.value
              Login.error = ''
            }
          })
        ),
        m('.row',
          m('button.submit', {
            onclick: function (e) {
              const credentials = {
                username: Login.username,
                password: Login.password
              }
              api.post('action=login', {
                body: credentials,
                withCredentials: false
              })
                .then((result) => {
                  window.sessionStorage.setItem('AUTHORIZATION_TOKEN', result.token)
                  m.route.set('/')
                })
                .catch((error) => {
                  Login.error = error.response.error + ' (' + error.code + ')'
                })
            }
          }, t('Login'))
        ),
        Login.error !== '' ? Login.error : ''
      )
    )
  }
}
