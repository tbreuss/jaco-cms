import {SystemModel as SystemModel} from '../model/SystemModel.js'
import {t} from '../model/i18n.js'
import {formatBytes} from '../lib/format.js'
import {app} from '../lib/app.js'

const keyValueTable = (title, object) => object
  ? m('div',
    m('h2', title),
    m('table.table.table-system-view',
      m('colgroup',
        m('col.col-30'),
        m('col')
      ),
      m('thead', m('tr', m('th', t('Name')), m('th', t('Value')))),
      m('tbody', Object.entries(object).map(([key, value]) => m('tr',
          m('td', key),
          m('td', configValue(key, value)),
        ))
      )
    ))
  : ''

// maintenanceMode arrives as the string 'true'/'false' (display_config converts
// the bool). Rendered as a translated yes/no instead of a raw string, but like
// every other row: no special marking.
const configValue = (key, value) => {
  if (key === 'maintenanceMode') {
    return value === 'true' ? t('Yes') : t('No')
  }
  return Array.isArray(value) ? value.join(', ') : value
}

const checksTable = (title, checks) => checks
  ? m('div',
    m('h2', title),
    m('table.table.table-system-view',
      m('colgroup',
        m('col.col-30'),
        m('col'),
        m('col.col-shrink')
      ),
      m('thead', m('tr', m('th', t('Check')), m('th', t('Detail')), m('th.text-center', t('Status')))),
      m('tbody', checks.map((check) => m('tr',
          m('td.text-nowrap', check.label),
          m('td', check.detail),
          m('td.text-center', {class: check.ok ? 'check-ok' : 'check-fail'}, check.ok ? '✓' : '✗'),
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
      checksTable(t('Requirements'), SystemModel.info.checks),
      overviewTable(SystemModel.info.overview),
      keyValueTable(t('Configuration'), SystemModel.info.config),
    )
  )
}

const overviewTable = (overview) => {
  if (overview == null) {
    return ''
  }
  const size = formatBytes(overview.projectSize)
  return keyValueTable(t('Overview'), {
    [t('Pages')]: String(overview.pages),
    [t('Uploads')]: String(overview.uploads),
    [t('Backups')]: String(overview.backups),
    [t('Project size')]: size,
    [t('PHP error log')]: `${formatBytes(overview.logSize)} / ${overview.logLines === 0 ? t('Empty') : overview.logLines.toLocaleString('de-CH')}`,
  })
}
