import {t} from '../model/i18n.js'
import {iframe} from '../lib/iframe.js'
import {ModalMedium} from '../component/ModalMedium.js'
import {Fieldset} from '../component/Fieldset.js'
import {ButtonGroup} from '../component/ButtonGroup.js'
import {ButtonSecondary} from '../component/ButtonSecondary.js'
import {PageListModel} from '../model/PageListModel.js'
import {app} from '../lib/app.js'

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

export const PageGotoView = {
  oninit: () => {
    if (!app.hasAuthToken()) {
      m.route.set('/logout')
    }
    // i.e. if page was reloaded with given route
    if (!iframe.hasElement()) {
      m.route.set('/')
    }
    PageListModel.load()
  },
  view: () => m(ModalMedium, {title: t('Go to page')},
    m(Fieldset, {legend: t('Select page')},
      PageListModel.isLoading
        ? m('p', t('Loading') + '...')
        : pageList(PageListModel.items || []),
      m(ButtonGroup, {wrap: 'div.row.text-right'},
        m(ButtonSecondary, {onclick: cancel}, t('Cancel'))
      )
    )
  )
}
