import {t, tError} from '../../model/i18n.js'
import {BackupListModel} from '../../model/BackupListModel.js'
import {ButtonPrimary} from '../../component/ButtonPrimary.js'
import {ButtonDanger} from '../../component/ButtonDanger.js'
import {ButtonSecondary} from '../../component/ButtonSecondary.js'
import {FilterInput} from '../../component/FilterInput.js'
import {Icon} from '../../component/Icon.js'
import {api} from '../../service/api.js'
import {addDanger, addSuccess} from '../../state/actions.js'
import {app} from '../../lib/app.js'
import {auth} from '../../lib/auth.js'
import {formatDateTimeCompact, formatDateTimeFull} from '../../lib/format.js'

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
          response.forEach(function (error) {
            addDanger(error.detail ? tError(error.detail) : t(error.title, null, error.title))
          })
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
          response.forEach(function (error) {
            addDanger(error.detail ? tError(error.detail) : t(error.title, null, error.title))
          })
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
  return m(FilterInput, {
    placeholder: t('Filter backup files'),
    value: BackupListModel.filter,
    oninput: (e) => {
      e.redraw = false
      BackupListModel.filter = e.target.value
      oninput()
    },
    onclear: () => {
      BackupListModel.filter = ''
      oninput()
    },
    clearLabel: t('Clear filter'),
  })
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
            m('col.col-30'),
            m('col'),
            m('col.col-shrink'),
            m('col.col-shrink'),
            m('col.col-shrink'),
            m('col.col-shrink')
          ),
          m('thead', m('tr',
            sortableHead(headings, loadList)
          )),
          m('tbody', BackupListModel.items.map((backup) => m('tr',
              m('td.cell-clip', {title: backup.name}, backup.name),
              m('td.cell-clip', {title: backup.title}, backup.title),
              m('td.text-nowrap', {title: formatDateTimeFull(backup.created)}, formatDateTimeCompact(backup.created)),
              m('td.text-nowrap', {title: formatDateTimeFull(backup.backedUp)}, formatDateTimeCompact(backup.backedUp)),
              m('td.text-nowrap', backup.type),
              m('td.text-right.text-nowrap',
                m(ButtonSecondary, {
                  onclick: () => {
                    viewBackup(backup.file)
                  },
                  title: t('View backup'),
                  'aria-label': t('View backup'),
                }, m(Icon, {name: 'view'})),
                // restoring and deleting rewrite files: admin only, matching the
                // route table. The buttons stay visible but disabled for everyone else.
                m(ButtonDanger, {
                  onclick: (e) => {
                    e.redraw = false
                    deleteAction(backup)
                  },
                  disabled: !auth.can('admin'),
                  title: auth.can('admin') ? t('Delete backup') : t('Delete backup') + ' – ' + t('Your role does not allow this.'),
                  'aria-label': t('Delete backup'),
                }, m(Icon, {name: 'delete'})),
                m(ButtonPrimary, {
                  onclick: (e) => {
                    e.redraw = false
                    restoreBackup(backup)
                  },
                  // an editor creates a backup with every change, so he may take
                  // his own work back; deleting a backup stays with the admin
                  disabled: !auth.can('editor'),
                  title: auth.can('editor') ? t('Restore backup') : t('Restore backup') + ' – ' + t('Your role does not allow this.'),
                  'aria-label': t('Restore backup'),
                }, m(Icon, {name: 'restore'}))
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
