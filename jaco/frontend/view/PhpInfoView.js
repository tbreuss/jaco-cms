import {PhpInfoModel} from '../model/PhpInfoModel.js'
import {app} from '../lib/app.js'
import {auth} from '../lib/auth.js'
import {t} from '../model/i18n.js'

export const PhpInfoView = {
  oninit: () => {
    if (!app.hasAuthToken()) {
      m.route.set('/logout')
      return
    }
    // the route is admin-only; a stale link must not load a 403 page
    if (!auth.can('admin')) {
      m.route.set('/')
      return
    }
    PhpInfoModel.load()
  },
  oncreate: () => {
    app.makeAppScrollable()
  },
  onremove: () => {
    app.makeIframeScrollable()
  },
  view: () => m('div.main',
    m('.content.system-php-info',
      m('h1', t('PHP info')),
      PhpInfoModel.data.content
        ? m.trust(PhpInfoModel.data.content)
        : m('table.table', m('tr', m('td', t('Loading') + '...')))
    )
  )
}
