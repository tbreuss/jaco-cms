import {t} from '../models/i18n.js'
import {Backup} from '../models/Backup.js'
import {ButtonPrimary} from '../components/ButtonPrimary.js'
import {ButtonSecondary} from '../components/ButtonSecondary.js';

Number.prototype.pad = function (size) {
  let s = String(this);
  while (s.length < (size || 2)) {
    s = "0" + s;
  }
  return s;
}

const dateTime = (timestamp) => {
  const date = new Date(timestamp * 1000);
  let parts = []
  parts.push(date.getDate().pad(2))
  parts.push('.')
  parts.push((date.getMonth() + 1).pad(2))
  parts.push('.')
  parts.push(date.getFullYear())
  parts.push(' ')
  parts.push(date.getHours().pad(2))
  parts.push(':')
  parts.push(date.getMinutes().pad(2))
  return parts.join('')
}

const restoreBackup = (backup) => {
  if (confirm(t('Do you want to restore this page?', {name: backup.name}))) {
    alert(backup.file)
  }
}

const viewBackup = () => {
  alert('View')
}

export const Backups = {
  oninit: () => {
    if (!window.sessionStorage.getItem('AUTHORIZATION_TOKEN')) {
      m.route.set('/logout')
    }
    Backup.loadList()
  },
  oncreate: () => {
    document.getElementById('app').classList.add('max-height')
  },
  onremove: () => {
    document.getElementById('app').classList.remove('max-height')
  },
  view: () => m('div.main',
    m('.content',
      m('h1', t('Backups')),
      Backup.loading
        ? m('p', t('Loading') + '...')
        : Backup.list.length
          ? m('table.table',
            m('thead', m('tr',
              m('th', t('Name')),
              m('th', t('Page title')),
              m('th', t('Backed up')),
              m('th', '')
            )),
            m('tbody', Backup.list.map((backup) => m('tr',
                m('td', backup.name),
                m('td', backup.title),
                m('td', dateTime(backup.created)),
                m('td.text-right',
                  m(ButtonSecondary, {onclick: viewBackup}, t('View')),
                  m(ButtonPrimary, {onclick: () => restoreBackup(backup)}, t('Restore'))
                )
              ))
            )
          )
          : m('p', t('No backups found.'))
    )
  )
}
