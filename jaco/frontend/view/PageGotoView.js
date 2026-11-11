import {t} from '../model/i18n.js'
import {iframe} from '../lib/iframe.js'
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

const openPage = (page, edit = false) => {
  if (!page) {
    return
  }
  const target = edit ? '/page-edit' : '/'
  let frame = null
  try {
    frame = iframe.getElement()
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
    iframe.load('/' + page.file)
  } catch (e) {
    iframe.load('/' + page.file)
    m.route.set(target)
  }
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
  if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
    e.preventDefault()
    if (pages.length === 0) {
      return
    }
    const delta = e.key === 'ArrowDown' ? 1 : -1
    state.selected = (state.selected + delta + pages.length) % pages.length
    return
  }
  if (e.key === 'Enter') {
    e.preventDefault()
    openPage(pages[state.selected], e.shiftKey)
  }
}

const action = (page, label, icon, run) =>
  m('button.palette-action', {
    type: 'button',
    title: label,
    'aria-label': label,
    onclick: (e) => {
      e.preventDefault()
      e.stopPropagation()
      run(page)
    },
  }, m(Icon, {name: icon}))

// Same look as the command palette: one row per page, the file as the label
// and the title below it, the secondary actions on the right of the row.
const pageList = (pages, selected) =>
  m('.palette-list', {id: 'goto-list', role: 'listbox', 'aria-label': t('Jump to page')},
    pages.length
      ? pages.map((page, index) =>
        m('.palette-row', {
          class: index === selected ? 'selected' : undefined,
          onmouseenter: () => {
            state.selected = index
          },
        },
        m('button.palette-item', {
          id: 'goto-item-' + index,
          type: 'button',
          role: 'option',
          'aria-selected': index === selected ? 'true' : 'false',
          onclick: () => openPage(page),
        },
        m('span.palette-label', page.file),
        page.title ? m('span.palette-context', page.title) : null),
        m('span.palette-actions',
          action(page, t('Edit content'), 'edit-content', (p) => openPage(p, true)),
          action(page, t('Edit page'), 'edit', (p) => m.route.set('/pages/edit/:file...', {file: p.file})))))
      : m('p.palette-empty', t('Sorry, no page found')))

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

    return m('.palette-overlay', {
      onclick: (e) => {
        if (e.target === e.currentTarget) {
          cancel(e)
        }
      },
    },
    m('.palette', {
      role: 'dialog',
      'aria-modal': 'true',
      'aria-label': t('Jump to page'),
      onkeydown: onKeyDown,
      oncreate: ({dom}) => dom.querySelector('.palette-filter')?.focus(),
    },
    m('h2.sr-only', t('Jump to page')),
    m('input.palette-filter', {
      type: 'text',
      role: 'combobox',
      'aria-label': t('Filter page files'),
      'aria-expanded': 'true',
      'aria-controls': 'goto-list',
      'aria-autocomplete': 'list',
      'aria-activedescendant': pages.length ? 'goto-item-' + selected : undefined,
      placeholder: t('Filter page files') + ' …',
      value: state.filter,
      oninput: (e) => {
        state.filter = e.target.value
        state.selected = 0
      },
    }),
    state.loading
      ? m('p.palette-empty', t('Loading') + '...')
      : pageList(pages, selected),
    m('.palette-hint', t('Use ↑↓ to select, Enter to open, Shift+Enter to edit, Esc to close.'))
    ))
  }
}
