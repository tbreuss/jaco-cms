import { api } from '../services/api.js'
import { addSuccess } from '../state/actions.js'
import { iframe } from '../lib/iframe.js'
import { t } from '../models/i18n.js'
import { ModalMedium } from '../components/ModalMedium.js'
import { Fieldset } from '../components/Fieldset.js'
import { ButtonGroup } from '../components/ButtonGroup.js'
import { ButtonPrimary } from '../components/ButtonPrimary.js'
import { ButtonSecondary } from '../components/ButtonSecondary.js'
import { InputText } from '../components/InputText.js'
import { FormErrors } from '../components/FormErrors.js'

const state = {
  srcFileName: '',
  srcPageTitle: '',
  dstFileName: '',
  dstPageTitle: '',
  errors: []
}

const save = (e) => {
  e.redraw = false
  api.post('action=page/copy', {
    background: true, // no redraw
    body: state,
    withCredentials: false
  })
    .then(() => {
      addSuccess(t('Page was successfully copied.'))
      m.route.set('/')
    })
    .catch(({ code, response }) => {
      if (code === 422) {
        state.errors = response.errors
      }
      m.redraw()
    })
}

const cancel = (e) => {
  e.redraw = false
  m.route.set('/')
}

export const PageCopy = {
  oninit: () => {
    if (!window.sessionStorage.getItem('AUTHORIZATION_TOKEN')) {
      m.route.set('/logout')
    }
    // i.e. if page was reloaded with given route
    if (!document.getElementById('iframe')) {
      m.route.set('/')
    }
    state.srcFileName = state.dstFileName = iframe.fileName()
    state.srcPageTitle = state.dstPageTitle = iframe.pageTitle()
    state.errors = []
  },
  oncreate: () => {
    //     visibility: visible;
    //     opacity: 1;
    console.log('views/PageCopy.js oncreate')
  },
  view: () => {
    console.log('views/PageCopy.js view')
    return m(ModalMedium, {title: t('Copy page')},
      m(Fieldset, {legend: t('Current page')},
        m(InputText, {
          wrap: 'div.row',
          label: t('Filename'),
          value: state.srcFileName,
          disabled: true
        }),
        m(InputText, {
          wrap: 'div.row',
          label: t('Page title'),
          value: state.srcPageTitle,
          disabled: true
        })
      ),
      m(Fieldset, {legend: t('New page')},
        m(InputText, {
          id: 'dstFileName',
          wrap: 'div.row',
          label: t('Filename'),
          value: state.dstFileName,
          oninput: (value) => {
            state.dstFileName = value
          }
        }),
        m(InputText, {
          id: 'dstPageTitle',
          wrap: 'div.row',
          label: t('Page title'),
          value: state.dstPageTitle,
          oninput: (value) => {
            state.dstPageTitle = value
          }
        }),
        m(ButtonGroup, {wrap: 'div.row.text-right'},
          m(ButtonSecondary, {onclick: cancel}, t('Cancel')),
          m(ButtonPrimary, {onclick: save}, t('OK'))
        )
      ),
      m(FormErrors, {errors: state.errors})
    )
  }
}
