import {t} from '../../model/i18n.js'
import {UploadListModel} from '../../model/UploadListModel.js'
import {ButtonPrimary} from '../../component/ButtonPrimary.js'
import {ButtonSecondary} from '../../component/ButtonSecondary.js'
import {FilterInput} from '../../component/FilterInput.js'
import {Icon} from '../../component/Icon.js'
import {api} from '../../service/api.js'
import {addDanger, addSuccess} from '../../state/actions.js'
import {app} from '../../lib/app.js'
import {formatDateTimeCompact, formatDateTimeFull} from '../../lib/format.js'

const viewUpload = (file) => {
  m.route.set('/uploads/view/:file...', {file: file})
}

const uploadFiles = (files) => {
  const uploads = [...files].map((file) => {
    const body = new FormData()
    body.append('file', file)
    return api.post('upload/create', {}, {
      background: true, // no redraw
      body: body,
      withCredentials: false
    })
  })

  Promise.all(uploads)
    .then(() => {
      UploadListModel.load().then(() => {
        addSuccess(t('Upload was successfully uploaded.'))
      })
    })
    .catch(({code, response}) => {
      if (code === 422) {
        addDanger(response[0].title)
      }
      m.redraw()
    })
}

const deleteAction = (page) => {
  if (confirm(t('Do you want to delete the upload?', {name: page.file}))) {
    api.post('upload/delete', {}, {
      background: true, // no redraw
      body: {fileName: page.file},
      withCredentials: false
    })
      .then(() => {
        UploadListModel.load().then(() => {
          addSuccess(t('Upload was successfully deleted.'))
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
  {field: 'extension', label: 'Type', sortable: true, default: 'desc'},
  {field: 'size', label: 'Size', sortable: true, default: 'desc', align: 'right'},
  {field: 'created', label: 'Created at', sortable: true, default: 'desc'},
  {label: '', sortable: false}
]

const sortableHead = (headings, onclick) => {
  const sort = UploadListModel.sort
  const [sortField, sortDir] = sort.substring(0, 1) === '-' ? [sort.substring(1), 'desc'] : [sort, 'asc']
  const markup = headings.map((heading) => {
    const label = heading.label.length ? t(heading.label) : ''
    const align = heading.align === 'right' ? '.text-right' : ''
    if (heading.sortable) {
      let arrow = ''
      let newSort = ''
      if (heading.field === sortField) {
        arrow = m('i.arrow ' + (sortDir === 'desc' ? 'down' : 'up'))
        newSort = (sortDir === 'desc' ? '' : '-') + sortField
      } else {
        newSort = (heading.default === 'desc' ? '-' : '') + heading.field
      }
      return m('th' + align, m('a[href=#]', {
        onclick: (e) => {
          e.redraw = false
          e.preventDefault()
          UploadListModel.sort = newSort
          onclick()
        }
      }, label, arrow))
    }
    return m('th' + align, label)
  })
  return markup
}

const dataFilter = (oninput) => {
  return m(FilterInput, {
    placeholder: t('Filter upload files'),
    value: UploadListModel.filter,
    oninput: (e) => {
      e.redraw = false
      UploadListModel.filter = e.target.value
      oninput()
    },
    onclear: () => {
      UploadListModel.filter = ''
      oninput()
    },
    clearLabel: t('Clear filter'),
  })
}

const loadList = () => {
  UploadListModel.load()
}

const formatSize = (size) => Number(size).toLocaleString('de-CH')

export const UploadListView = {
  oninit: () => {
    if (!app.hasAuthToken()) {
      m.route.set('/logout')
    }
    UploadListModel.load()
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
        m('h1', t('Uploads')),
        m('div',
          m(ButtonPrimary, {
            onclick: () => {
              const input = document.getElementById('uploadFiles')
              if (input) {
                input.click()
              }
            }
          }, t('Upload files')),
          m('input#uploadFiles[type=file][multiple]', {
            style: {display: 'none'},
            onchange: ({target}) => {
              uploadFiles(target.files)
              target.value = ''
            }
          })
        )
      ),
      dataFilter(loadList),
      UploadListModel.isLoading
        ? m('table.table', m('tr', m('td', t('Loading') + '...')))
        : UploadListModel.items.length
          ? m('table.table',
            m('colgroup',
              m('col'),
              m('col'),
              m('col.col-shrink'),
              m('col.col-shrink'),
              m('col.col-shrink'),
            ),
            m('thead', m('tr',
              sortableHead(headings, loadList)
            )),
            m('tbody', UploadListModel.items.map((page) => m('tr',
                m('td', page.file),
                m('td.text-nowrap', page.extension),
                m('td.text-right.text-nowrap', formatSize(page.size)),
                m('td.text-nowrap', {title: formatDateTimeFull(page.created)}, formatDateTimeCompact(page.created)),
                m('td.text-right.text-nowrap',
                  m(ButtonSecondary, {
                    onclick: () => {
                      viewUpload(page.file)
                    },
                    title: t('View'),
                    'aria-label': t('View'),
                  }, m(Icon, {name: 'view'})),
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
          : m('table.table', m('tr', m('td', t('No uploads found.'))))
    )
  )
}
