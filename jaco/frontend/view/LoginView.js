import {ButtonGroup} from '../component/ButtonGroup.js'
import {ButtonPrimary} from '../component/ButtonPrimary.js'
import {FormErrors} from '../component/FormErrors.js'
import {InputPassword} from '../component/InputPassword.js'
import {InputText} from '../component/InputText.js'
import {ModalSmall} from '../component/ModalSmall.js'
import {api} from '../service/api.js'
import {app} from '../lib/app.js'
import {t} from '../model/i18n.js'

const state = {
  username: '',
  password: '',
  errors: [],
}

const login = () => {
  const credentials = {
    username: state.username,
    password: state.password
  }

  api.post('action=login', {
    background: true, // no redraw
    body: credentials,
    withCredentials: false
  })
    .then((data) => {
      app.setAuthToken(data.token)
      m.route.set('/')
    })
    .catch(({code, response}) => {
      if (code === 422) {
        state.errors = response
      }
      m.redraw()
    })
}

const onLoginSubmitted = (e) => {
  e.preventDefault() // no page reload, the request is sent by the api service
  e.redraw = false

  login()
}

export const LoginView = {
  oninit: () => {
    state.username = ''
    state.password = ''
    state.errors = []
  },
  view: () => m(ModalSmall, {
      title: t('Login'),
      cssClass: 'modal-sm-login'
    },
    // the form submits on RETURN, its submit button triggers the same handler
    m('form', {onsubmit: onLoginSubmitted},
      m(InputText, {
        id: 'username',
        autofocus: true,
        wrap: 'div.row',
        label: t('Username'),
        value: state.username,
        placeholder: t('Username'),
        oninput: (value) => {
          state.username = value
          state.errors = []
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
          state.errors = []
        }
      }),
      m(ButtonGroup, {wrap: 'div.row.text-right'},
        m(ButtonPrimary, {}, t('Login'))
      ),
      m(FormErrors, {errors: state.errors})
    )
  )
}
