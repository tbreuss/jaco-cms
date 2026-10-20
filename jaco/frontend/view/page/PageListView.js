import {ButtonDanger} from '../../component/ButtonDanger.js'
import {ButtonPrimary} from '../../component/ButtonPrimary.js'
import {ButtonSecondary} from '../../component/ButtonSecondary.js'
import {FilterInput} from '../../component/FilterInput.js'
import {Icon} from '../../component/Icon.js'
import {PageCopySource} from '../../model/PageCopySource.js'
import {PageListModel as PageListModel} from '../../model/PageListModel.js'
import {addDanger, addSuccess} from '../../state/actions.js'
import {api} from '../../service/api.js'
import {t, tError} from '../../model/i18n.js'
import {app} from '../../lib/app.js'
import {auth} from '../../lib/auth.js'
import {iframe} from '../../lib/iframe.js'
import {startProcess} from '../../lib/process.js'
import {pageAddProcess} from '../../processes/pageAdd.js'
import {formatDateTimeCompact, formatDateTimeFull} from '../../lib/format.js'

const editPage = (file) => {
  m.route.set('/pages/edit/:file...', {file: file})
}

const viewPage = (file) => {
  iframe.load('/' + file)
  m.route.set('/')
}

const editContentPage = (file) => {
  const target = '/page-edit'
  let frame = null
  try {
    if (!iframe.hasElement()) {
      m.route.set('/')
      return
    }
    frame = iframe.getElement()
  } catch (e) {
    m.route.set('/')
    return
  }
  frame.onload = () => {
    frame.onload = null
    frame.onerror = null
    m.route.set(target)
  }
  frame.onerror = () => {
    frame.onload = null
    frame.onerror = null
    m.route.set('/')
  }
  iframe.load('/' + file)
}

const copyPage = (page) => {
  PageCopySource.set(page.file, page.title, '/pages')
  m.route.set('/page-copy')
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
          response.forEach(function (error) {
            addDanger(error.detail ? tError(error.detail) : t(error.title, null, error.title))
          })
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
      m('div', {style: {display: 'flex', justifyContent: 'space-between', alignItems: 'center'}},
        m('h1', t('Pages')),
        m(ButtonPrimary, {
          onclick: () => startProcess(pageAddProcess),
          disabled: !auth.can('editor'),
          title: auth.can('editor') ? t('Add page') : t('Add page') + ' – ' + t('Your role does not allow this.'),
        }, m(Icon, {name: 'plus'}), m('span', t('Add page')))
      ),
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
                  // view the page in the site preview
                  m(ButtonSecondary, {
                    onclick: () => {
                      viewPage(page.file)
                    },
                    title: t('View page'),
                    'aria-label': t('View page'),
                  }, m(Icon, {name: 'view'})),
                  // edit the page content inline (rich-text editor)
                  m(ButtonSecondary, {
                    onclick: () => {
                      editContentPage(page.file)
                    },
                    disabled: !auth.can('editor'),
                    title: auth.can('editor') ? t('Edit content') : t('Edit content') + ' – ' + t('Your role does not allow this.'),
                    'aria-label': t('Edit content'),
                  }, m(Icon, {name: 'edit-content'})),
                  // edit the page document (Ace editor; read-only for a viewer)
                  m(ButtonSecondary, {
                    onclick: () => {
                      editPage(page.file)
                    },
                    disabled: !auth.can('editor'),
                    title: auth.can('editor') ? t('Edit page') : t('Edit page') + ' – ' + t('Your role does not allow this.'),
                    'aria-label': t('Edit page'),
                  }, m(Icon, {name: 'edit'})),
                  m(ButtonSecondary, {
                    onclick: () => {
                      copyPage(page)
                    },
                    disabled: !auth.can('editor'),
                    title: auth.can('editor') ? t('Copy page') : t('Copy page') + ' – ' + t('Your role does not allow this.'),
                    'aria-label': t('Copy page'),
                  }, m(Icon, {name: 'copy'})),
                  m(ButtonDanger, {
                    onclick: (e) => {
                      e.redraw = false
                      deleteAction(page)
                    },
                    disabled: !auth.can('editor'),
                    title: auth.can('editor') ? t('Delete page') : t('Delete page') + ' – ' + t('Your role does not allow this.'),
                    'aria-label': t('Delete page'),
                  }, m(Icon, {name: 'delete'})),
                )
              ))
            )
          )
          : m('table.table', m('tr', m('td', t('No pages found.'))))
    )
  )
}
