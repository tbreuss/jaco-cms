import {t} from '../../model/i18n.js'
import {PageList as PageListModel} from '../../model/PageList.js'
import {ButtonPrimary} from '../../component/ButtonPrimary.js'
import {ButtonSecondary} from '../../component/ButtonSecondary.js'
import {api} from "../../service/api.js"
import {addSuccess} from "../../state/actions.js"
import {addDanger} from "../../state/actions.js"

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
    api.post('action=backup/restore', {
      background: true, // no redraw
      body: {file: backup.file},
      withCredentials: false
    })
      .then(() => {
        PageListModel.load().then(() => {
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

const viewBackup = (file) => {
  m.route.set('/backups/view/:file', {file: file})
}

const deleteAction = (backup) => {
  if (confirm(t('Do you want to delete the page?', {name: backup.name}))) {
    api.post('action=backup/delete', {
      background: true, // no redraw
      body: {file: backup.file},
      withCredentials: false
    })
      .then(() => {
        PageListModel.load().then(() => {
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
  {field: 'file', label: 'Filename', sortable: true, default: 'asc'},
  {field: 'title', label: 'Page title', sortable: true, default: 'asc'},
  {field: 'created', label: 'Created at', sortable: true, default: 'desc'},
  {label: '', sortable: false}
]

const sortableHead = (headings, onclick) => {
  const sort = PageListModel.sort
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
          PageListModel.sort = newSort
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
      value: PageListModel.filter,
      oninput: (e) => {
        e.redraw = false
        PageListModel.filter = e.target.value
        oninput()
      }
    })
  )
}

const loadList = () => {
  PageListModel.load()
}

export const PageList = {
  oninit: () => {
    if (!window.sessionStorage.getItem('AUTHORIZATION_TOKEN')) {
      m.route.set('/logout')
    }
    PageListModel.load()
  },
  oncreate: () => {
    document.getElementById('app').classList.add('max-height')
    document.documentElement.style.visibility = 'visible'
  },
  onremove: () => {
    document.getElementById('app').classList.remove('max-height')
  },
  view: () => m('div.main',
    m('.content',
      m('h1', t('Pages')),
      dataFilter(loadList),
      PageListModel.loading
        ? m('p', t('Loading') + '...')
        : PageListModel.items.length
          ? m('table.table',
            m('colgroup',
              m('col', {width: '30%'}),
              m('col', {width: '30%'}),
              m('col', {width: '30%'}),
              m('col', {width: '10%'})
            ),
            m('thead', m('tr',
              sortableHead(headings, loadList)
            )),
            m('tbody', PageListModel.items.map((backup) => m('tr',
                m('td', backup.file),
                m('td', backup.title),
                m('td', dateTime(backup.created)),
                m('td.text-right.text-nowrap',
                  m(ButtonSecondary, {
                    onclick: () => {
                      //viewBackup(backup.file)
                      return
                    }
                  }, t('View')),
                  m(ButtonPrimary, {
                    onclick: (e) => {
                      e.redraw = false
                      //deleteAction(backup)
                      return
                    }
                  }, t('Delete')),
                )
              ))
            )
          )
          : m('p', t('No pages found.'))
    )
  )
}
