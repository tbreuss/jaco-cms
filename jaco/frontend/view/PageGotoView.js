import {t} from '../model/i18n.js'
import {iframe} from '../lib/iframe.js'
import {ModalMedium} from '../component/ModalMedium.js'
import {Fieldset} from '../component/Fieldset.js'
import {FilterInput} from '../component/FilterInput.js'
import {ButtonGroup} from '../component/ButtonGroup.js'
import {ButtonSecondary} from '../component/ButtonSecondary.js'
import {Icon} from '../component/Icon.js'
import {PageListModel} from '../model/PageListModel.js'
import {app} from '../lib/app.js'

export const state = {
  filter: '',
  selected: 0,
  items: [],
  loading: false,
}

const filtered = () => {
  const query = state.filter.trim().toLowerCase()
  if (query === '') {
    return state.items
  }
  return state.items.filter((page) => `${page.file} ${page.title ?? ''}`.toLowerCase().includes(query))
}

const cancel = (e) => {
  e.redraw = false
  m.route.set('/')
}

const openPage = (page) => {
  if (!page) {
    return
  }
  iframe.load('/' + page.file)
  m.route.set('/')
}

const load = () => {
  state.loading = true
  PageListModel.loadAll()
    .then((items) => {
      state.items = items
      state.loading = false
      m.redraw()
    }, () => {
      state.loading = false
      m.redraw()
    })
}

const onKeyDown = (e) => {
  const pages = filtered()

  if (e.key === 'Escape') {
    e.preventDefault()
    cancel(e)
    return
  }
  if (e.key === 'ArrowDown') {
    e.preventDefault()
    if (pages.length > 0) {
      state.selected = Math.min(state.selected + 1, pages.length - 1)
    }
    return
  }
  if (e.key === 'ArrowUp') {
    e.preventDefault()
    if (pages.length > 0) {
      state.selected = Math.max(state.selected - 1, 0)
    }
    return
  }
  if (e.key === 'Enter') {
    e.preventDefault()
    openPage(pages[state.selected])
  }
}

const pageList = (pages, selected) =>
  pages.length
    ? m('.list-group.page-goto-list', pages.map((page, index) =>
      m('a.list-group-item', {
        class: index === selected ? 'selected' : undefined,
        href: '#',
        onclick: (e) => {
          e.preventDefault()
          openPage(page)
        }
      },
      m('span', page.file),
      page.title ? m('span.page-goto-title', ' · ' + page.title) : null)
    ))
    : m('p', t('Sorry, no page found'))

export const PageGotoView = {
  oninit: () => {
    if (!app.hasAuthToken()) {
      m.route.set('/logout')
      return
    }
    // i.e. if page was reloaded with given route
    if (!iframe.hasElement()) {
      m.route.set('/')
      return
    }
    state.filter = ''
    state.selected = 0
    state.items = []
    load()
  },
  view: () => {
    const pages = filtered()
    const selected = Math.min(state.selected, Math.max(pages.length - 1, 0))

    return m(ModalMedium, {title: t('Jump to page')},
      m(Fieldset, {legend: t('Select page')},
        m(FilterInput, {
          placeholder: t('Filter page files'),
          value: state.filter,
          autofocus: true,
          onkeydown: onKeyDown,
          oninput: (e) => {
            state.filter = e.target.value
            state.selected = 0
          },
          onclear: () => {
            state.filter = ''
            state.selected = 0
          },
          clearLabel: t('Clear filter'),
        }),
        state.loading
          ? m('p', t('Loading') + '...')
          : pageList(pages, selected),
        m('p.hint', t('Use ↑↓ to select, Enter to open, Esc to close.')),
        m(ButtonGroup, {wrap: 'div.row.text-right'},
          m(ButtonSecondary, {onclick: cancel}, m(Icon, {name: 'clear'}), m('span', t('Cancel')))
        )
      )
    )
  }
}
