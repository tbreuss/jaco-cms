import { api } from '../service/api.js'
import { t } from '../model/i18n.js'
import { ModalSmall } from '../component/ModalSmall.js'
import { InputText } from '../component/InputText.js'
import { InputPassword } from '../component/InputPassword.js'
import { ButtonGroup } from '../component/ButtonGroup.js'
import { ButtonPrimary } from '../component/ButtonPrimary.js'
import { FormErrors } from '../component/FormErrors.js'

const state = {
  username: '',
  password: '',
  errors: [],
}

const login = (e) => {
  e.redraw = false

  const credentials = {
    username: state.username,
    password: state.password
  }

  api.post('action=login', {
    background: true, // no redraw
    body: credentials,
    withCredentials: false
  })
    .then(({ data }) => {
      window.sessionStorage.setItem('AUTHORIZATION_TOKEN', data.token)
      m.route.set('/')
    })
    .catch(({ code, response }) => {
      if (code === 422) {
        state.errors = response.errors
      }
      m.redraw()
    })
}

export const Login = {
  oninit: () => {
    state.username = ''
    state.password = ''
    state.errors = []
  },
  oncreate: () => {
    document.documentElement.style.visibility = 'visible'
  },
  view: () => m(ModalSmall, { title: t('Login'), cssClass: 'modal-sm-login' },
    m(InputText, {
      id: 'username',
      wrap: 'div.row',
      label: t('Username'),
      value: state.username,
      placeholder: t('Username'),
      oninput: (value) => {
        state.username = value
        state.error = ''
      }
    }),
    m(InputPassword, {
      id: 'password',
      wrap: 'div.row',
      label: t('Password'),
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
    m(FormErrors, { errors: state.errors })
  )
}
