import { t } from '../models/i18n.js'
import { iframe } from '../lib/iframe.js'
import { ModalMedium } from '../components/ModalMedium.js'
import { Fieldset } from '../components/Fieldset.js'
import { ButtonGroup } from '../components/ButtonGroup.js'
import { ButtonSecondary } from '../components/ButtonSecondary.js'
import { FormErrors } from '../components/FormErrors.js'
import { Page } from '../models/Page.js'

const state = {
  pages: [],
  errors: []
}

const cancel = () => {
  m.route.set('/')
}

const gotoPage = (e) => {
  e.preventDefault()
  iframe.load('/' + e.target.textContent)
  m.route.set('/')
}

const pageList = (pages) => {
  return pages.map((page) => {
    return m('div',
      m('a[href=#]', {
        onclick: gotoPage
      },
        page.file)
    )
  })
}

export const PageGoto = {
  oninit: () => {
    if (!window.sessionStorage.getItem('AUTHORIZATION_TOKEN')) {
      m.route.set('/logout')
    }
    // i.e. if page was reloaded with given route
    if (!document.getElementById('iframe')) {
      m.route.set('/')
    }
    state.pages = ''
    state.errors = []
    Page.loadList()
  },
  view: () => m(ModalMedium, { title: t('Goto page') },
    m(Fieldset, { legend: t('Select page') },
      pageList(Page.list || []),
      m(ButtonGroup, { wrap: 'div.row.text-right' },
        m(ButtonSecondary, { onclick: cancel }, t('Cancel'))
      ),
    ),
    m(FormErrors, { errors: state.errors })
  )
}
