import {t} from '../model/i18n.js'
import {AboutModel} from '../model/AboutModel.js'
import {app} from '../lib/app.js'

export const AboutView = {
  oninit: () => {
    if (!app.hasAuthToken()) {
      m.route.set('/logout')
      return
    }
    // public version data: every role may open this page, the admin-only
    // system endpoint is not involved
    AboutModel.load()
  },
  oncreate: () => {
    app.makeAppScrollable()
  },
  onremove: () => {
    app.makeIframeScrollable()
  },
  view: () => {
    const info = AboutModel.info || {}
    const version = info.version || ''
    const phpVersion = info.phpVersion || ''
    const link = (href, label) => m('a', {href: href, target: '_blank', rel: 'noopener noreferrer'}, label)
    return m('div.main',
      m('.content',
        m('h1', t('About')),
        m('p', t('Jaco CMS is a flat-file CMS that manages static HTML files directly in the web directory. No database, no build step — just PHP 8.2+ on any shared host.')),
        m('table.table.table-system-view',
          m('tbody',
            m('tr', m('td', t('Version')), m('td', version || t('Loading') + '...')),
            m('tr', m('td', 'PHP version'), m('td', phpVersion || t('Loading') + '...')),
            m('tr', m('td', t('License')), m('td', t('Freeware'))),
            m('tr', m('td', t('Project')), m('td', link('https://github.com/tbreuss/jaco-cms', 'github.com/tbreuss/jaco-cms'))),
            m('tr', m('td', t('Releases')), m('td', link('https://github.com/tbreuss/jaco-cms/releases', t('Download')))),
          )
        )
      )
    )
  }
}
