import { api } from '../services/api.js'
import { t } from '../models/i18n.js'
import { ModalSmall } from '../components/ModalSmall.js'
import { InputText } from '../components/InputText.js'
import { InputPassword } from '../components/InputPassword.js'
import { ButtonGroup } from '../components/ButtonGroup.js'
import { ButtonPrimary } from '../components/ButtonPrimary.js'

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
  view: () => m(ModalSmall, { title: t('Login') },
    m(InputText, {
      value: state.username,
      placeholder: t('Username'),
      oninput: (value) => {
        state.username = value
        state.error = ''
      }
    }),
    m(InputPassword, {
      value: state.password,
      placeholder: t('Password'),
      oninput: (value) => {
        state.password = value
        state.error = ''
      }
    }),
    m(ButtonGroup, { wrap: 'div.row.text-right' },
      m(ButtonPrimary, { onclick: login }, t('Login'))
    ),
    state.error ?? state.error
  )
}
