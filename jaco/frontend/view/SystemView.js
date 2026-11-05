import {SystemModel as SystemModel} from '../model/SystemModel.js'
import {t} from '../model/i18n.js'
import {formatBytes} from '../lib/format.js'
import {app} from '../lib/app.js'
import {ButtonPrimary} from '../component/ButtonPrimary.js'
import {ButtonSecondary} from '../component/ButtonSecondary.js'

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

// users have their own table above; the configuration list would only show the
// word "array"
const configWithoutUsers = () => {
  const config = {...SystemModel.info.config}
  delete config.users
  return config
}

// The accounts of this installation: name, role and whether the second factor is
// enrolled. The server sends those four fields only, never the digest or the
// secret itself.
const usersTable = (users) => users && users.length
  ? m('div',
    m('h2', t('Users')),
    m('table.table.table-system-view',
      m('colgroup',
        m('col.col-30'),
        m('col'),
        m('col.col-shrink'),
        m('col.col-shrink')
      ),
      m('thead', m('tr',
        m('th', t('User')),
        m('th', t('Role')),
        m('th.text-center', t('Read-only')),
        m('th.text-center', t('Two-factor')),
      )),
      m('tbody', users.map((user) => m('tr',
          m('td.text-nowrap', user.name),
          m('td', t(user.role === 'admin' ? 'Admin' : 'Editor')),
          m('td.text-center', user.readOnly ? t('Yes') : t('No')),
          m('td.text-center', user.totpEnabled ? t('Enabled') : t('No')),
        ))
      )
    ))
  : ''

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

const updatePanel = () => {
  const u = SystemModel.update || {}
  return m('div',
    m('h2', t('Updates')),
    m(ButtonSecondary, {
      onclick: () => SystemModel.checkUpdate().then(() => m.redraw()).catch(() => m.redraw())
    }, t('Check for updates')),
    u.available === true
      ? m('div',
          m('p', t('Update available') + ': ' + u.current + ' → ' + u.latest),
          m(ButtonPrimary, {
            onclick: () => {
              if (!confirm(t('Install update?'))) return
              SystemModel.performUpdate(u.downloadUrl, u.latest)
                .then(() => m.redraw())
                .catch(() => m.redraw())
            }
          }, t('Install update')),
          u.result?.upToDate ? m('p', t('Already up to date') + ' (' + u.result.from + ').') : (u.result ? m('p', t('Updated') + ': ' + u.result.from + ' → ' + u.result.to) : ''),
          u.error ? m('p', t('Update failed') + ': ' + u.error) : '',
        )
      : (u.available === false
          ? m('p', t('Already up to date') + ' (' + (u.current || '') + ').')
          : ''),
  )
}

export const SystemView = {
  oninit: () => {
    if (!app.hasAuthToken()) {
      m.route.set('/logout')
      return
    }
    // the route is admin-only: the load answers 403 and the layout explains it
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
      usersTable(SystemModel.info.config?.users),
      keyValueTable(t('Configuration'), configWithoutUsers()),
      updatePanel(),
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
    [t('Media')]: String(overview.uploads),
    [t('Backups')]: String(overview.backups),
    [t('Project size')]: size,
    [t('Application log')]: `${formatBytes(overview.logSize)} / ${overview.logLines === 0 ? t('Empty') : overview.logLines.toLocaleString('de-CH')}`,
  })
}
