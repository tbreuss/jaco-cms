import {t} from '../model/i18n.js'
import {ButtonGroup} from '../component/ButtonGroup.js'
import {ButtonPrimary} from '../component/ButtonPrimary.js'
import {ButtonSecondary} from '../component/ButtonSecondary.js'
import {Fieldset} from '../component/Fieldset.js'
import {FormErrors, errorFor} from '../component/FormErrors.js'
import {Icon} from '../component/Icon.js'
import {InputPassword} from '../component/InputPassword.js'
import {api} from '../service/api.js'
import {addSuccess} from '../state/actions.js'
import {app} from '../lib/app.js'
import {auth} from '../lib/auth.js'

// Every role may change its own password, so this form lives outside the admin-only
// system view. The current password is the proof; the endpoint counts a wrong one
// against the same throttle as a login.
export const state = {
  current: '',
  next: '',
  sending: false,
  errors: [],
}

const submit = (event) => {
  event.preventDefault()
  // the button is aria-disabled while sending, so a second submit can still be
  // triggered with RETURN; the guard keeps it from posting twice
  if (state.sending) {
    return
  }
  state.sending = true
  state.errors = []

  // the passwords belong in the body, not in the query string: a url ends up in
  // server logs and browser history
  api.post('system/change-password', {}, {
    body: {
      withPassword: state.current,
      withNewPassword: state.next,
    },
  })
    .then(() => {
      state.current = ''
      state.next = ''
      addSuccess(t('The password was changed.'))
    })
    .catch(({code, response}) => {
      // validation errors belong under the fields; everything else is toasted by the
      // api layer already
      if (code === 422) {
        state.errors = Array.isArray(response) ? response : []
      }
    })
    .finally(() => {
      state.sending = false
      m.redraw()
    })
}

export const PasswordView = {
  oninit: () => {
    if (!app.hasAuthToken()) {
      m.route.set('/logout')
      return
    }
    // a demo account may be forbidden to change its public password; the link is
    // hidden, this keeps a stale direct link from opening the form
    if (!auth.canChangePassword()) {
      m.route.set('/')
      return
    }
    // never keep a password from a previous visit in memory or in the form
    state.current = ''
    state.next = ''
    state.sending = false
    state.errors = []
  },
  oncreate: () => {
    app.makeAppScrollable()
  },
  onremove: () => {
    app.makeIframeScrollable()
  },
  view: () => m('div.main',
    m('.content',
      m('h1', t('Change password')),
      m('form.form-narrow', {onsubmit: submit},
        m(Fieldset, {},
          m(InputPassword, {
            id: 'currentPassword',
            wrap: 'div.row',
            label: t('Current password'),
            value: state.current,
            error: errorFor(state.errors, 'withPassword'),
            oninput: (value) => {
              state.current = value
              state.errors = []
            },
          }),
          m(InputPassword, {
            id: 'newPassword',
            wrap: 'div.row',
            label: t('New password'),
            value: state.next,
            error: errorFor(state.errors, 'withNewPassword'),
            oninput: (value) => {
              state.next = value
              state.errors = []
            },
          }),
          m(ButtonGroup, {wrap: 'div.row.text-right'},
            m(ButtonSecondary, {
              onclick: () => {
                m.route.set('/')
              }
            }, m(Icon, {name: 'clear'}), m('span', t('Cancel'))),
            m(ButtonPrimary, {disabled: state.sending},
              m(Icon, {name: 'check'}),
              m('span', t('Change password')),
            ),
          ),
          m(FormErrors, {
            errors: state.errors.filter((error) => !['withPassword', 'withNewPassword'].includes(error.source.pointer)),
          }),
        ),
      ),
    )
  )
}
