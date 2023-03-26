import {PhpLogModel} from '../model/PhpLogModel.js'
import {t} from '../model/i18n.js'
import {app} from '../lib/app.js'

const logTable = (data) => m('div',
  m('table.table.table-log-view',
    m('thead', m('tr', m('th', t('Timestamp')), m('th', t('Message')))),
    m('tbody', data.map((value) => m('tr',
        m('td', value.substring(1, 24)),
        m('td', value.substring(26))
      ))
    )
  )
)

export const PhpLogView = {
  oninit: () => {
    if (!app.hasAuthToken()) {
      m.route.set('/logout')
    }
    PhpLogModel.load()
  },
  oncreate: () => {
    app.makeAppScrollable()
  },
  onremove: () => {
    app.makeIframeScrollable()
  },
  view: () => m('div.main',
    m('.content',
      m('h1', t('PHP error log')),
      PhpLogModel.data.length > 0
        ? logTable(PhpLogModel.data)
        : PhpLogModel.isLoading
          ? m('table.table', m('tr', m('td', t('Loading') + '...')))
          : m('table.table', m('tr', m('td', t('The log is empty.'))))
    )
  )
}
