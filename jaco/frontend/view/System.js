import { System as SystemModel } from '../model/System.js'
import { t } from '../model/i18n.js'

const keyValueTable = (title, object) => object
  ? m('div',
    m('h2', title),
    m('table.table',
      m('thead', m('tr', m('th', t('Name')), m('th', t('Value')))),
      m('tbody', Object.entries(object).map((constant) => m('tr',
        m('td', constant[0]),
        m('td', constant[1]),
      ))
      )
    ))
  : ''

const listTable = (title, array) => array
  ? m('div',
    m('h2', title),
    m('table.table',
      m('thead', m('tr', m('th', t('Name')))),
      m('tbody', array.map((constant) => m('tr',
        m('td', constant)
      ))
      )
    ))
  : ''

export const System = {
  oninit: () => {
    if (!window.sessionStorage.getItem('AUTHORIZATION_TOKEN')) {
      m.route.set('/logout')
    }
    SystemModel.load()
  },
  oncreate: () => {
    document.getElementById('app').classList.add('max-height')
    document.documentElement.style.visibility = 'visible'
  },
  onremove: () => {
    document.getElementById('app').classList.remove('max-height')
  },
  view: () => m('div.main',
    m('.content',
      m('h1', t('System environment')),
      keyValueTable(t('Constants'), SystemModel.info.constants),
      listTable(t('Classes'), SystemModel.info.classes),
      listTable(t('Functions'), SystemModel.info.functions),
    )
  )
}
