import {ButtonGroup} from '../component/ButtonGroup.js'
import {ButtonPrimary} from '../component/ButtonPrimary.js'
import {ButtonSecondary} from '../component/ButtonSecondary.js'
import {Icon} from '../component/Icon.js'
import {FormErrors, errorFor} from '../component/FormErrors.js'
import {InputPassword} from '../component/InputPassword.js'
import {InputText} from '../component/InputText.js'
import {ModalSmall} from '../component/ModalSmall.js'
import {api} from '../service/api.js'
import {app} from '../lib/app.js'
import {i18n, t} from '../model/i18n.js'

export const state = {
  step: 'login',
  username: '',
  password: '',
  totpCode: '',
  errors: [],
}

const fieldPointers = ['username', 'password', 'totpCode']

export const login = () => {
  return api.post('login', {}, {
    background: true, // no redraw
    body: {
      username: state.username,
      password: state.password,
    },
    withCredentials: false
  })
    .then((data) => {
      if (data.totpRequired === true) {
        state.step = 'totp'
        state.errors = []
        m.redraw()
        return
      }
      state.username = ''
      state.password = ''
      app.setAuthToken(data.token)
      // the token now carries the user's language: reload the labels before the
      // next view paints them
      i18n.loadAndSetLocale()
      m.route.set('/')
    })
    .catch(({code, response}) => {
      state.username = ''
      state.password = ''
      if (code === 422) {
        state.errors = response
      }
      m.redraw()
      document.getElementById('username')?.focus()
    })
}

export const verify = () => {
  return api.post('login', {}, {
    background: true, // no redraw
    body: {
      username: state.username,
      password: state.password,
      totpCode: state.totpCode,
    },
    withCredentials: false
  })
    .then((data) => {
      if (data.totpRequired === true) {
        state.errors = []
        m.redraw()
        document.getElementById('totpCode')?.focus()
        return
      }
      state.username = ''
      state.password = ''
      state.totpCode = ''
      app.setAuthToken(data.token)
      // the token now carries the user's language: reload the labels before the
      // next view paints them
      i18n.loadAndSetLocale()
      m.route.set('/')
    })
    .catch(({code, response}) => {
      if (code === 422) {
        state.errors = response
      }
      m.redraw()
      document.getElementById('totpCode')?.focus()
    })
}

const backToLogin = (e) => {
  e.redraw = false
  state.step = 'login'
  state.totpCode = ''
  state.errors = []
  m.redraw()
}

const onLoginSubmitted = (e) => {
  e.preventDefault() // no page reload, the request is sent by the api service
  e.redraw = false

  login()
}

const onTotpSubmitted = (e) => {
  e.preventDefault() // no page reload, the request is sent by the api service
  e.redraw = false

  verify()
}

export const LoginView = {
  oninit: () => {
    // already authenticated: go straight to the admin panel instead of
    // showing the login form again
    if (app.hasAuthToken()) {
      m.route.set('/')
      return
    }
    state.step = 'login'
    state.username = ''
    state.password = ''
    state.totpCode = ''
    state.errors = []
    // The public demo link opts in to pre-filled credentials. Do not put the
    // password itself in the URL; the viewer account is intentionally public.
    if (m.route.param('demo') === '1') {
      state.username = 'demo'
      state.password = 'demo1234'
    }
  },
  view: () => m(ModalSmall, {
      title: t('Login'),
      cssClass: 'modal-sm-login'
    },
    // the form submits on RETURN, its submit button triggers the same handler
    state.step === 'totp'
      ? m('form', {key: state.step, onsubmit: onTotpSubmitted},
        m(InputText, {
          id: 'totpCode',
          autofocus: true,
          wrap: 'div.row',
          label: t('Authentication code'),
          value: state.totpCode,
          placeholder: t('Authentication code'),
          error: errorFor(state.errors, 'totpCode'),
          oninput: (value) => {
            state.totpCode = value
            state.errors = []
          }
        }),
        m(ButtonGroup, {wrap: 'div.row.text-right'},
          m(ButtonSecondary, {onclick: backToLogin, type: 'button'}, m(Icon, {name: 'back'}), m('span', t('Back'))),
          m(ButtonPrimary, {}, m(Icon, {name: 'check'}), m('span', t('Verify')))
        ),
        m(FormErrors, {errors: state.errors.filter((error) => !fieldPointers.includes(error.source.pointer))})
      )
      : m('form', {key: state.step, onsubmit: onLoginSubmitted},
        m(InputText, {
          id: 'username',
          autofocus: true,
          wrap: 'div.row',
          label: t('Username'),
          value: state.username,
          placeholder: t('Username'),
          error: errorFor(state.errors, 'username'),
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
          error: errorFor(state.errors, 'password'),
          oninput: (value) => {
            state.password = value
            state.errors = []
          }
        }),
        m(ButtonGroup, {wrap: 'div.row.text-right'},
          m(ButtonPrimary, {}, m(Icon, {name: 'login'}), m('span', t('Login')))
        ),
        m(FormErrors, {errors: state.errors.filter((error) => !fieldPointers.includes(error.source.pointer))})
      )
  )
}
