import { t } from '../models/i18n.js'

export const Backups = {
  oninit: () => {
    if (!window.sessionStorage.getItem('AUTHORIZATION_TOKEN')) {
      m.route.set('/logout')
    }
  },
  oncreate: () => {
    document.getElementById('app').classList.add('max-height')
  },
  onremove: () => {
    document.getElementById('app').classList.remove('max-height')
  },
  view: () => m('div.main',
    m('.content',
      m('h1', t('Backups'))
    )
  )
}
