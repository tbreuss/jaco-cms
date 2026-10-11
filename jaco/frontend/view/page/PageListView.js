import {ButtonPrimary} from '../../component/ButtonPrimary.js'
import {ButtonSecondary} from '../../component/ButtonSecondary.js'
import {PageListModel as PageListModel} from '../../model/PageListModel.js'
import {addDanger, addSuccess} from '../../state/actions.js'
import {api} from '../../service/api.js'
import {t} from '../../model/i18n.js'
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

const editPage = (file) => {
  m.route.set('/pages/edit/:file...', {file: file})
}

const deleteAction = (page) => {
  console.log(page)
  if (confirm(t('Do you want to delete the page?', {name: page.file}))) {
    api.post('action=page/delete', {
      background: true, // no redraw
      body: {fileName: page.file},
      withCredentials: false
    })
      .then(() => {
        PageListModel.load().then(() => {
          addSuccess(t('Page was successfully deleted.'))
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
      placeholder: t('Filter page files'),
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

export const PageListView = {
  oninit: () => {
    if (!app.hasAuthToken()) {
      m.route.set('/logout')
    }
    PageListModel.load()
  },
  oncreate: () => {
    app.makeAppScrollable()
  },
  onremove: () => {
    app.makeIframeScrollable()
  },
  view: () => m('div.main',
    m('.content',
      m('h1', t('Pages')),
      dataFilter(loadList),
      PageListModel.isLoading
        ? m('table.table', m('tr', m('td', t('Loading') + '...')))
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
            m('tbody', PageListModel.items.map((page) => m('tr',
                m('td', page.file),
                m('td', page.title),
                m('td', dateTime(page.created)),
                m('td.text-right.text-nowrap',
                  m(ButtonSecondary, {
                    onclick: () => {
                      editPage(page.file)
                    }
                  }, t('Edit')),
                  m(ButtonPrimary, {
                    onclick: (e) => {
                      e.redraw = false
                      deleteAction(page)
                    }
                  }, t('Delete')),
                )
              ))
            )
          )
          : m('table.table', m('tr', m('td', t('No pages found.'))))
    )
  )
}
