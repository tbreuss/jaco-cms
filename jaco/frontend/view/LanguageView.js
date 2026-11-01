import {t, i18n} from '../model/i18n.js'
import {ButtonGroup} from '../component/ButtonGroup.js'
import {ButtonPrimary} from '../component/ButtonPrimary.js'
import {ButtonSecondary} from '../component/ButtonSecondary.js'
import {Fieldset} from '../component/Fieldset.js'
import {FormErrors, errorFor} from '../component/FormErrors.js'
import {Icon} from '../component/Icon.js'
import {Select} from '../component/Select.js'
import {api} from '../service/api.js'
import {addSuccess} from '../state/actions.js'
import {app} from '../lib/app.js'
import {auth} from '../lib/auth.js'

// Every account carries its own interface language in the token, so this form
// lives outside the admin-only system view. Saving stores the preference and
// swaps the current session over without asking for the password again.
export const state = {
  languages: [],
  current: '',
  loading: true,
  sending: false,
  errors: [],
}

const submit = (event) => {
  event.preventDefault()
  if (state.sending || state.loading) {
    return
  }
  state.sending = true
  state.errors = []

  api.post('system/change-language', {}, {
    body: {
      language: state.current,
    },
  })
    .then((data) => {
      app.setAuthToken(data.token)
      state.current = auth.language() || state.current

      // no loading placeholder this time: the page is already rendered, only
      // the labels change once the new messages arrive
      return i18n.loadAndSetLocale(false).then(() => {
        addSuccess(t('The language was changed.'))
      })
    })
    .catch(({code, response}) => {
      // validation errors belong under the field; everything else is toasted by
      // the api layer already
      if (code === 422) {
        state.errors = Array.isArray(response) ? response : []
      }
    })
    .finally(() => {
      state.sending = false
      m.redraw()
    })
}

export const LanguageView = {
  oninit: () => {
    if (!app.hasAuthToken()) {
      m.route.set('/logout')
      return
    }
    state.loading = true
    state.sending = false
    state.errors = []

    api.get('system/languages')
      .then((data) => {
        state.languages = Array.isArray(data.languages) ? data.languages : []
        state.current = auth.language() || data.defaultLanguage || ''
      })
      .catch(() => {
        // the api layer has already shown the failure; the form stays empty
      })
      .finally(() => {
        state.loading = false
        m.redraw()
      })
  },
  oncreate: () => {
    app.makeAppScrollable()
  },
  onremove: () => {
    app.makeIframeScrollable()
  },
  view: () => m('div.main',
    m('.content',
      m('h1', t('Change language')),
      m('form.form-narrow', {onsubmit: submit},
        m(Fieldset, {},
          m(Select, {
            id: 'language',
            wrap: 'div.row',
            label: t('Language'),
            value: state.current,
            disabled: state.loading || state.sending,
            options: state.languages.map((language) => {
              return {value: language.code, label: language.name}
            }),
            error: errorFor(state.errors, 'language'),
            onchange: (value) => {
              state.current = value
              state.errors = []
            },
          }),
        ),
        m(FormErrors, {
          errors: state.errors.filter((error) => error.source.pointer !== 'language'),
        }),
        m(ButtonGroup, {wrap: 'div.row.text-right'},
          m(ButtonSecondary, {
            // without an explicit type inside a form, Cancel would submit it
            type: 'button',
            onclick: () => {
              m.route.set('/')
            }
          }, m(Icon, {name: 'clear'}), m('span', t('Cancel'))),
          m(ButtonPrimary, {disabled: state.loading || state.sending, type: 'submit'},
            m(Icon, {name: 'check'}),
            m('span', t('Change language')),
          ),
        ),
      ),
    )
  )
}
