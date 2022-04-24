import { api } from '../services/api.js'
import { addSuccess } from '../state/actions.js'
import { iframe } from '../lib/iframe.js'
import { t } from '../models/i18n.js'
import { ModalMedium } from '../components/ModalMedium.js'
import { ButtonGroup } from '../components/ButtonGroup.js'
import { ButtonPrimary } from '../components/ButtonPrimary.js'
import { ButtonSecondary } from '../components/ButtonSecondary.js'
import { Fieldset } from '../components/Fieldset.js'

const state = {
  fileName: ''
}

const save = () => {
  api.post('action=page/delete', {
    body: { fileName: state.fileName },
    withCredentials: false
  })
    .then((result) => {
      console.debug(result)
      addSuccess(t('Page was successfully deleted.'))
      m.route.set('/')
    })
    .catch((error) => {
      state.error = error.response.error + ' (' + error.code + ')'
    })
}

const cancel = () => {
  m.route.set('/')
}

export const PageDelete = {
  oninit: () => {
    state.fileName = iframe.fileName()
  },
  view: () => m(ModalMedium, { title: t('Delete page') },
    m(Fieldset, { legend: t('Confirm') },
      m('p', t('Do you really want to delete the page', {
        name: state.fileName
      })),
      m(ButtonGroup, { wrap: 'div.row.text-right' },
        m(ButtonSecondary, { onclick: cancel }, t('No')),
        m(ButtonPrimary, { onclick: save }, t('Yes'))
      )
    )
  )
}
