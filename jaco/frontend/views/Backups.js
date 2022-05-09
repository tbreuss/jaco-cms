import {t} from '../models/i18n.js'
import {Backup} from '../models/Backup.js'
import {ButtonPrimary} from '../components/ButtonPrimary.js'
import {ButtonSecondary} from '../components/ButtonSecondary.js';
import {api} from "../services/api.js";
import {addSuccess} from "../state/actions.js";
import {addDanger} from "../state/actions.js";
import {addInfo} from '../state/actions.js';

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
    api.post('action=backup/restore', {
      background: true, // no redraw
      body: {file: backup.file},
      withCredentials: false
    })
      .then(() => {
        Backup.loadList().then(() => {
          addSuccess(t('Backup was successfully restored.'))
        })
      })
      .catch(({code, response}) => {
        if (code === 422) {
          addDanger(response.errors[0].title)
        }
        m.redraw()
      })
  }
}

const viewBackup = () => {
  addInfo('This is not yet implemented.')
}

const deleteAction = (backup) => {
  if (confirm(t('Do you want to delete the page?', {name: backup.name}))) {
    api.post('action=backup/delete', {
      background: true, // no redraw
      body: {file: backup.file},
      withCredentials: false
    })
      .then(() => {
        Backup.loadList().then(() => {
          addSuccess(t('Backup was successfully deleted.'))
        })
      })
      .catch(({code, response}) => {
        if (code === 422) {
          addDanger(response.errors[0].title)
        }
        m.redraw()
      })
  }
}

const headings = [
  {field: 'name', label: 'Name', sortable: true, default: 'asc'},
  {field: 'title', label: 'Page title', sortable: true, default: 'asc'},
  {field: 'created', label: 'Backed up', sortable: true, default: 'desc'},
  {field: '', sortable: false, label: 'Type'},
  {label: '', sortable: false}
]

const sortableHead = (headings, onclick) => {
  const sort = Backup.sort
  const [sortField, sortDir] = sort.substring(0, 1) === '-' ? [sort.substring(1), 'desc'] : [sort, 'asc']
  const markup = headings.map((heading) => {
    const label = heading.label.length ? t(heading.label) : ''
    if (heading.sortable) {
      let arrow = ''
      let newSort = ''
      if (heading.field === sortField) {
        arrow = m('i.arrow ' + (sortDir === 'desc' ? 'down' : 'up'))
        newSort = (sortDir === 'desc' ? '' : '-') + sortField
      } else {
        newSort = (heading.default === 'desc' ? '-' : '') + heading.field
      }
      return m('th', m('a[href=#]', {
        onclick: (e) => {
          e.redraw = false
          e.preventDefault()
          Backup.sort = newSort
          onclick()
        }
      }, label, arrow))
    }
    return m('th', label)
  })
  return markup
}

const dataFilter = (oninput) => {
  return m('.list-filter',
    m('input[type=text]', {
      placeholder: t('Filter backup files'),
      oninput: (e) => {
        e.redraw = false
        Backup.filter = e.target.value
        oninput()
      }
    })
  )
}

const loadList = () => {
  Backup.loadList()
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
      dataFilter(loadList),
      Backup.loading
        ? m('p', t('Loading') + '...')
        : Backup.list.length
          ? m('table.table',
            m('colgroup',
              m('col', {width: '30%'}),
              m('col', {width: '30%'}),
              m('col', {width: '20%'}),
              m('col', {width: '10%'}),
              m('col', {width: '15%'})
            ),
            m('thead', m('tr',
              sortableHead(headings, loadList)
            )),
            m('tbody', Backup.list.map((backup) => m('tr',
                m('td', backup.name),
                m('td', backup.title),
                m('td', dateTime(backup.created)),
                m('td', 'Page'),
                m('td.text-right.text-nowrap',
                  m(ButtonSecondary, {onclick: viewBackup}, t('View')),
                  m(ButtonPrimary, {
                    onclick: (e) => {
                      e.redraw = false
                      deleteAction(backup)
                    }
                  }, t('Delete')),
                  m(ButtonPrimary, {
                    onclick: (e) => {
                      e.redraw = false
                      restoreBackup(backup)
                    }
                  }, t('Restore'))
                )
              ))
            )
          )
          : m('p', t('No backups found.'))
    )
  )
}
