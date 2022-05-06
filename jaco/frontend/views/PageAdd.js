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
import { Select } from '../components/Select.js'
import { FormErrors } from '../components/FormErrors.js'
import { PageTemplate } from '../models/PageTemplate.js'

const state = {
  fileName: '',
  pageTitle: '',
  pageTemplate: '',
  errors: []
}

const save = () => {
  api.post('action=page/create', {
    body: state,
    withCredentials: false
  })
    .then(() => {
      addSuccess(t('Page was successfully added.'))
      m.route.set('/')
    })
    .catch(({ code, response }) => {
      if (code === 422) {
        state.errors = response.errors
      }
    })
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
    state.fileName = iframe.fileName()
    state.pageTitle = iframe.pageTitle()
    state.pageTemplate = ''
    state.errors = []

    PageTemplate.loadList()
  },
  view: () => m(ModalMedium, { title: t('Add page') },
    m(Fieldset, { legend: t('New page') },
      m(Select, {
        id: 'pageTemplate',
        wrap: 'div.row',
        label: t('Page template'),
        options: PageTemplate.list.map((item) => {
          return { value: item.file, label: item.title }
        }),
        prompt: t('Select an option'),
        onchange: (selectedItem) => {
          state.pageTemplate = selectedItem
        }
      }),
      m(InputText, {
        id: 'fileName',
        wrap: 'div.row',
        label: t('Filename'),
        value: state.fileName,
        oninput: (value) => {
          state.fileName = value
        }
      }),
      m(InputText, {
        id: 'pageTitle',
        wrap: 'div.row',
        label: t('Page title'),
        value: state.pageTitle,
        oninput: (value) => {
          state.pageTitle = value
        }
      }),
      m(ButtonGroup, { wrap: 'div.row.text-right' },
        m(ButtonSecondary, { onclick: cancel }, t('Cancel')),
        m(ButtonPrimary, { onclick: save }, t('OK'))
      ),
    ),
    m(FormErrors, { errors: state.errors })
  )
}
