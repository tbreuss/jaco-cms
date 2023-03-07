import {PhpInfoModel} from '../model/PhpInfoModel.js'
import {t} from '../model/i18n.js'

export const PhpInfoView = {
  oninit: () => {
    if (!window.sessionStorage.getItem('AUTHORIZATION_TOKEN')) {
      m.route.set('/logout')
    }
    PhpInfoModel.load()
  },
  oncreate: () => {
    document.getElementById('app').classList.add('max-height')
    document.documentElement.style.visibility = 'visible'
  },
  onremove: () => {
    document.getElementById('app').classList.remove('max-height')
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
