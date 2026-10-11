import {t} from '../../model/i18n.js'
import {BackupListModel} from '../../model/BackupListModel.js'
import {ButtonPrimary} from '../../component/ButtonPrimary.js'
import {ButtonSecondary} from '../../component/ButtonSecondary.js'
import {api} from '../../service/api.js'
import {addDanger, addSuccess} from '../../state/actions.js'
import {app} from '../../lib/app.js'

const dateTime = (timestamp) => {
  const date = new Date(timestamp * 1000)
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
  parts.push(':')
  parts.push(date.getSeconds().pad(2))
  return parts.join('')
}

const restoreBackup = (backup) => {
  if (confirm(t('Do you want to restore this page?', {name: backup.name}))) {
    api.post('backup/restore', {}, {
      background: true, // no redraw
      body: {file: backup.file},
      withCredentials: false
    })
      .then(() => {
        BackupListModel.loadList().then(() => {
          addSuccess(t('Backup was successfully restored.'))
        })
      })
      .catch(({code, response}) => {
        if (code === 422) {
          addDanger(response[0].title)
        }
        m.redraw()
      })
  }
}

const viewBackup = (file) => {
  m.route.set('/backups/view/:file...', {file: file})
}

const deleteAction = (backup) => {
  if (confirm(t('Do you want to delete the page?', {name: backup.name}))) {
    api.post('backup/delete', {}, {
      background: true, // no redraw
      body: {file: backup.file},
      withCredentials: false
    })
      .then(() => {
        BackupListModel.loadList().then(() => {
          addSuccess(t('Backup was successfully deleted.'))
        })
      })
      .catch(({code, response}) => {
        if (code === 422) {
          addDanger(response[0].title)
        }
        m.redraw()
      })
  }
}

const headings = [
  {field: 'name', label: 'Name', sortable: true, default: 'asc'},
  {field: 'title', label: 'Page title', sortable: true, default: 'asc'},
  {field: 'created', label: 'Created at', sortable: true, default: 'desc'},
  {field: 'backedUp', label: 'Backed up', sortable: true, default: 'desc'},
  {field: 'type', label: 'Type', sortable: true},
  {label: '', sortable: false}
]

const sortableHead = (headings, onclick) => {
  const sort = BackupListModel.sort
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
          BackupListModel.sort = newSort
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
      value: BackupListModel.filter,
      oninput: (e) => {
        e.redraw = false
        BackupListModel.filter = e.target.value
        oninput()
      }
    })
  )
}

const loadList = () => {
  BackupListModel.loadList()
}

export const BackupListView = {
  oninit: () => {
    if (!app.hasAuthToken()) {
      m.route.set('/logout')
    }
    BackupListModel.loadList()
  },
  oncreate: () => {
    app.makeAppScrollable()
  },
  onremove: () => {
    app.makeIframeScrollable()
  },
  view: () => m('div.main',
    m('.content',
      m('h1', t('Backups')),
      dataFilter(loadList),
      BackupListModel.items.length > 0
        ? m('table.table',
          m('colgroup',
            m('col', {width: '30%'}),
            m('col', {width: '30%'}),
            m('col', {width: '20%'}),
            m('col', {width: '20%'}),
            m('col', {width: '10%'}),
            m('col', {width: '1%'})
          ),
          m('thead', m('tr',
            sortableHead(headings, loadList)
          )),
          m('tbody', BackupListModel.items.map((backup) => m('tr',
              m('td', backup.name),
              m('td', backup.title),
              m('td', dateTime(backup.created)),
              m('td', dateTime(backup.backedUp)),
              m('td', backup.type),
              m('td.text-right.text-nowrap',
                m(ButtonSecondary, {
                  onclick: () => {
                    viewBackup(backup.file)
                  }
                }, t('View')),
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
        : BackupListModel.isLoading
          ? m('table.table', m('tr', m('td', t('Loading') + '...')))
          : m('table.table', m('tr', m('td', t('No backups found.'))))
    )
  )
}
