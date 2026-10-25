import {PhpLogModel} from '../model/PhpLogModel.js'
import {ButtonDanger} from '../component/ButtonDanger.js'
import {Icon} from '../component/Icon.js'
import {t} from '../model/i18n.js'
import {addSuccess} from '../state/actions.js'
import {app} from '../lib/app.js'

const clearLog = () => {
  if (confirm(t('Do you want to clear the log?'))) {
    PhpLogModel.clear()
      .then(() => {
        addSuccess(t('Log was successfully cleared.'))
      })
      .catch(() => {
        // the api layer already reported the failure (one toast per error), so only
        // swallow the rejection here: a second toast would repeat the same message
      })
  }
}

const logTable = (data) => m('div',
  m('table.table.table-log-view',
    m('colgroup',
      m('col.col-shrink'),
      m('col')
    ),
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
      return
    }
    // the route is admin-only: the load answers 403 and the layout explains it
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
      m('div', {style: {display: 'flex', justifyContent: 'space-between', alignItems: 'center'}},
        m('h1', t('PHP error log')),
        m('div',
          m(ButtonDanger, {
            onclick: (e) => {
              e.redraw = false
              clearLog()
            }
          }, m(Icon, {name: 'delete'}), m('span', t('Clear log')))
        )
      ),
      PhpLogModel.data.length > 0
        ? logTable(PhpLogModel.data)
        : PhpLogModel.isLoading
          ? m('table.table', m('tr', m('td', t('Loading') + '...')))
          : m('table.table', m('tr', m('td', t('The log is empty.'))))
    )
  )
}
