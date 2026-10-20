import {t} from '../model/i18n.js'
import {visibleCommands} from '../lib/commands.js'
import {startProcess} from '../lib/process.js'

export const paletteState = {
  filter: '',
  selected: 0,
  open: false,
  previousFocus: null,
}

const itemId = (cmd) => 'palette-item-' + cmd.id.replace(/[^a-z0-9_-]/gi, '-')

const filtered = () => {
  const query = paletteState.filter.trim().toLowerCase()
  return visibleCommands().filter((cmd) =>
    query === '' || t(cmd.labelKey).toLowerCase().includes(query))
}

const selectedCommand = (items) =>
  items.length === 0 ? null : items[Math.min(paletteState.selected, items.length - 1)]

const focusFilter = () => {
  document.querySelector('.palette-filter')?.focus()
}

const open = () => {
  if (paletteState.open) {
    return
  }
  paletteState.previousFocus = document.activeElement
  paletteState.open = true
  paletteState.filter = ''
  paletteState.selected = 0
  m.redraw()
}

const close = (restoreFocus = true) => {
  const previous = paletteState.previousFocus
  paletteState.open = false
  paletteState.previousFocus = null
  m.redraw()

  if (restoreFocus && previous && previous !== document.body && typeof previous.focus === 'function') {
    setTimeout(() => previous.focus(), 0)
  }
}

const execute = (cmd) => {
  if (!cmd) {
    return
  }
  close(false)
  if (cmd.process) {
    startProcess(cmd.process)
  } else {
    cmd.run()
  }
}

const trapFocus = (e) => {
  const palette = document.querySelector('.palette')
  if (!palette) {
    return
  }
  const focusables = [...palette.querySelectorAll('input, button')]
  if (focusables.length === 0) {
    return
  }
  const first = focusables[0]
  const last = focusables[focusables.length - 1]

  if (e.shiftKey && document.activeElement === first) {
    e.preventDefault()
    last.focus()
  } else if (!e.shiftKey && document.activeElement === last) {
    e.preventDefault()
    first.focus()
  }
}

const onKeyDown = (e) => {
  if (e.repeat) {
    return
  }

  const items = filtered()

  if (e.key === 'Escape') {
    e.preventDefault()
    close()
    return
  }

  if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
    e.preventDefault()
    if (items.length === 0) {
      return
    }
    const delta = e.key === 'ArrowDown' ? 1 : -1
    paletteState.selected = (paletteState.selected + delta + items.length) % items.length
    focusFilter()
    return
  }

  if (e.key === 'Enter' && e.target?.classList.contains('palette-filter')) {
    e.preventDefault()
    execute(selectedCommand(items))
    return
  }

  if (e.key === 'Tab') {
    trapFocus(e)
  }
}

export const CommandPalette = {
  view: () => {
    if (!paletteState.open) {
      return null
    }

    const items = filtered()
    const selected = Math.min(paletteState.selected, Math.max(items.length - 1, 0))
    const current = selectedCommand(items)

    return m('.palette-overlay', {
      onclick: (e) => {
        if (e.target === e.currentTarget) {
          close()
        }
      },
    },
    m('.palette', {
      role: 'dialog',
      'aria-modal': 'true',
      'aria-label': t('Commands'),
      onkeydown: onKeyDown,
      oncreate: ({dom}) => dom.querySelector('.palette-filter')?.focus(),
    },
    m('input.palette-filter', {
      type: 'text',
      role: 'combobox',
      'aria-label': t('Search'),
      'aria-expanded': 'true',
      'aria-controls': 'palette-list',
      'aria-autocomplete': 'list',
      'aria-activedescendant': current ? itemId(current) : undefined,
      placeholder: t('Search') + ' …',
      value: paletteState.filter,
      oninput: (e) => {
        paletteState.filter = e.target.value
        paletteState.selected = 0
      },
    }),
    m('.palette-list', {id: 'palette-list', role: 'listbox', 'aria-label': t('Commands')},
      items.map((cmd, index) =>
        m('button.palette-item', {
          id: itemId(cmd),
          type: 'button',
          role: 'option',
          'aria-selected': index === selected ? 'true' : 'false',
          class: index === selected ? 'selected' : undefined,
          onclick: () => execute(cmd),
          onmouseenter: () => {
            paletteState.selected = index
          },
        },
        m('span.palette-label', t(cmd.labelKey))))
    ),
    m('.palette-hint', t('Use ↑↓ to select, Enter to run, Esc to close.'))
    ))
  },
}

export const openPalette = open
export const closePalette = close
