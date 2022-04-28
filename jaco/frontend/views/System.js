import { System as SystemModel } from '../models/System.js'
import { t } from '../models/i18n.js'

const constants = (object) => object
  ? m('div',
    m('h2', 'Constants'),
    m('table.table',
      m('thead', m('tr', m('th', t('Name')), m('th', t('Value')))),
      m('tbody', Object.entries(object).map((constant) => m('tr',
        m('td', constant[0]),
        m('td', constant[1]),
      ))
      )
    ))
  : ''

const functions = (array) => array
  ? m('div',
    m('h2', 'Functions'),
    m('table.table',
      m('thead', m('tr', m('th', t('Name')))),
      m('tbody', array.map((constant) => m('tr',
        m('td', constant)
      ))
      )
    ))
  : ''

export const System = {
  oninit: SystemModel.load,
  oncreate: () => {
    document.getElementById('app').classList.add('max-height')
  },
  onremove: () => {
    document.getElementById('app').classList.remove('max-height')
  },
  view: () => m('div.main',
    m('.content',
      m('h1', 'System'),
      constants(SystemModel.info.constants),
      functions(SystemModel.info.functions),
    )
  )
}
