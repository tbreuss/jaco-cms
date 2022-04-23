import { addSuccess } from '../state/actions.js'
import { iframe } from '../lib/iframe.js'
import { t } from '../models/i18n.js'
import { ModalMedium } from '../components/ModalMedium.js'
import { ButtonGroup } from '../components/ButtonGroup.js'
import { ButtonPrimary } from '../components/ButtonPrimary.js'
import { ButtonSecondary } from '../components/ButtonSecondary.js'
import { Fieldset } from '../components/Fieldset.js'

const save = () => {
  addSuccess(t('Page was successfully deleted.'))
  m.route.set('/')
}

const cancel = () => {
  m.route.set('/')
}

export const PageDelete = {
  oninit: () => {
    if (!window.sessionStorage.getItem('AUTHORIZATION_TOKEN')) {
      m.route.set('/logout')
    }
  },
  view: () => m(ModalMedium, { title: t('Delete page') },
    m(Fieldset, { legend: t('Confirm') },
      m('p', t('Do you really want to delete the page', {
        name: iframe.fileName()
      })),
      m(ButtonGroup, { wrap: 'div.row.text-right' },
        m(ButtonSecondary, { onclick: cancel }, t('No')),
        m(ButtonPrimary, { onclick: save }, t('Yes'))
      )
    )
  )
}
