import {SystemModel as SystemModel} from '../model/SystemModel.js'
import {t} from '../model/i18n.js'
import {app} from '../lib/app.js'

const keyValueTable = (title, object) => object
  ? m('div',
    m('h2', title),
    m('table.table.table-system-view',
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
    m('table.table.table-system-view',
      m('thead', m('tr', m('th', t('Name')))),
      m('tbody', array.map((constant) => m('tr',
          m('td', constant)
        ))
      )
    ))
  : ''

export const SystemView = {
  oninit: () => {
    if (!app.hasAuthToken()) {
      m.route.set('/logout')
    }
    SystemModel.load()
  },
  oncreate: () => {
    app.makeAppScrollable()
  },
  onremove: () => {
    app.makeIframeScrollable()
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
