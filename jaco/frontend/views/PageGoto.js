import { t } from '../models/i18n.js'
import { iframe } from '../lib/iframe.js'
import { ModalMedium } from '../components/ModalMedium.js'
import { Fieldset } from '../components/Fieldset.js'
import { ButtonGroup } from '../components/ButtonGroup.js'
import { ButtonSecondary } from '../components/ButtonSecondary.js'
import { Page } from '../models/Page.js'

const cancel = () => {
  m.route.set('/')
}

const gotoPage = (e) => {
  e.preventDefault()
  iframe.load('/' + e.target.textContent)
  m.route.set('/')
}

const pageList = (pages) =>
  pages.length
    ? m('.list-group', pages.map((page) =>
      m('a[href=#].list-group-item', {
        onclick: gotoPage
      }, page.file))
    )
    : m('p', t('Sorry, no page found'))

export const PageGoto = {
  oninit: () => {
    if (!window.sessionStorage.getItem('AUTHORIZATION_TOKEN')) {
      m.route.set('/logout')
    }
    // i.e. if page was reloaded with given route
    if (!document.getElementById('iframe')) {
      m.route.set('/')
    }
    Page.loadList()
  },
  view: () => m(ModalMedium, { title: t('Goto page') },
    m(Fieldset, { legend: t('Select page') },
      pageList(Page.list || []),
      m(ButtonGroup, { wrap: 'div.row.text-right' },
        m(ButtonSecondary, { onclick: cancel }, t('Cancel'))
      )
    )
  )
}
