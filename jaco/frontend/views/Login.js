import { api } from '../services/api.js'
import { t } from '../models/i18n.js'

const state = {
  username: '',
  password: '',
  error: '',
}

const login = () => {
  const credentials = {
    username: state.username,
    password: state.password
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
      state.error = error.response.error + ' (' + error.code + ')'
    })
}

export const Login = {
  view: () => m('.modal',
    m('.modal-sm',
      m('h2', t('Login')),
      m('.row',
        m('input[type=text]', {
          value: state.username,
          placeholder: t('Username'),
          oninput: (e) => {
            state.username = e.target.value
            state.error = ''
          }
        })
      ),
      m('.row',
        m('input[type=password]', {
          value: state.password,
          placeholder: t('Password'),
          oninput: (e) => {
            state.password = e.target.value
            state.error = ''
          }
        })
      ),
      m('.row',
        m('button.submit', {
          onclick: login
        }, t('Login'))
      ),
      state.error !== '' ? state.error : ''
    )
  )
}
