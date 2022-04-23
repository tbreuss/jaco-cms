import { addSuccess } from '../state/actions.js'
import { iframe } from '../lib/iframe.js'
import { t } from '../models/i18n.js'
import { ModalMedium } from '../components/ModalMedium.js'
import { Fieldset } from '../components/Fieldset.js'
import { ButtonGroup } from '../components/ButtonGroup.js'
import { ButtonPrimary } from '../components/ButtonPrimary.js'
import { ButtonSecondary } from '../components/ButtonSecondary.js'
import { InputText } from '../components/InputText.js'

const state = {
  fileName: '',
  pageTitle: ''
}

const save = () => {
  addSuccess(t('Page was successfully added.'))
  m.route.set('/')
}

const cancel = () => {
  m.route.set('/')
}

export const PageAdd = {
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
  },
  view: () => m(ModalMedium, { title: t('Add page') },
    m(Fieldset, { legend: t('New page') },
      m(InputText, {
        id: 'dst-file-name',
        wrap: 'div.row',
        label: t('Filename'),
        value: state.dstFileName,
        oninput: (value) => {
          state.dstFileName = value
        }
      }),
      m(InputText, {
        id: 'dst-page-title',
        wrap: 'div.row',
        label: t('Page title'),
        value: state.dstPageTitle,
        oninput: (value) => {
          state.dstPageTitle = value
        }
      }),
      m(ButtonGroup, { wrap: 'div.row.text-right' },
        m(ButtonSecondary, { onclick: cancel }, t('Cancel')),
        m(ButtonPrimary, { onclick: save }, t('OK'))
      )
    )
  )
}
