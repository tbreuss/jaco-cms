import { t } from '../model/i18n.js'
import { iframe } from '../lib/iframe.js'
import { ModalMedium } from '../component/ModalMedium.js'
import { Fieldset } from '../component/Fieldset.js'
import { ButtonGroup } from '../component/ButtonGroup.js'
import { ButtonSecondary } from '../component/ButtonSecondary.js'
import { Page } from '../model/Page.js'

const cancel = (e) => {
  e.redraw = false
  m.route.set('/')
}

const gotoPage = (e) => {
  e.redraw = false
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
  oncreate: () => {
    document.documentElement.style.visibility = 'visible'
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
