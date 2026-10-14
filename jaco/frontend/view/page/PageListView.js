import {ButtonPrimary} from '../../component/ButtonPrimary.js'
import {ButtonSecondary} from '../../component/ButtonSecondary.js'
import {FilterInput} from '../../component/FilterInput.js'
import {Icon} from '../../component/Icon.js'
import {PageListModel as PageListModel} from '../../model/PageListModel.js'
import {addDanger, addSuccess} from '../../state/actions.js'
import {api} from '../../service/api.js'
import {t} from '../../model/i18n.js'
import {app} from '../../lib/app.js'
import {formatDateTimeCompact, formatDateTimeFull} from '../../lib/format.js'

const editPage = (file) => {
  m.route.set('/pages/edit/:file...', {file: file})
}

const deleteAction = (page) => {
  console.log(page)
  if (confirm(t('Do you want to delete the page?', {name: page.file}))) {
    api.post('page/delete', {}, {
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
  return m(FilterInput, {
    placeholder: t('Filter page files'),
    value: PageListModel.filter,
    oninput: (e) => {
      e.redraw = false
      PageListModel.filter = e.target.value
      oninput()
    },
    onclear: () => {
      PageListModel.filter = ''
      oninput()
    },
    clearLabel: t('Clear filter'),
  })
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
              m('col.col-30'),
              m('col'),
              m('col.col-shrink'),
              m('col.col-shrink')
            ),
            m('thead', m('tr',
              sortableHead(headings, loadList)
            )),
            m('tbody', PageListModel.items.map((page) => m('tr',
                m('td.text-nowrap', page.file),
                m('td', page.title),
                m('td.text-nowrap', {title: formatDateTimeFull(page.created)}, formatDateTimeCompact(page.created)),
                m('td.text-right.text-nowrap',
                  m(ButtonSecondary, {
                    onclick: () => {
                      editPage(page.file)
                    },
                    title: t('Edit'),
                    'aria-label': t('Edit'),
                  }, m(Icon, {name: 'edit'})),
                  m(ButtonPrimary, {
                    onclick: (e) => {
                      e.redraw = false
                      deleteAction(page)
                    },
                    title: t('Delete'),
                    'aria-label': t('Delete'),
                  }, m(Icon, {name: 'delete'})),
                )
              ))
            )
          )
          : m('table.table', m('tr', m('td', t('No pages found.'))))
    )
  )
}
