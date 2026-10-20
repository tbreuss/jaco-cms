import {auth} from './auth.js'
import {iframe} from './iframe.js'
import {getTheme, setTheme} from './theme.js'
import {pageAddProcess} from '../processes/pageAdd.js'

// Command registry: every action is defined once here, the palette only renders
// and executes. Actions call existing functions — no second implementation.
//
// A command has:
//   id       unique key
//   labelKey translation key shown in the palette
//   roles    roles that may see/run it (empty = everyone)
//   when     optional context check (returns boolean)
//   run      function receiving the context

export const commands = [
  {
    id: 'page.add',
    labelKey: 'Add page',
    roles: ['editor', 'admin'],
    process: pageAddProcess,
  },
  {
    id: 'page.edit-content',
    labelKey: 'Edit page',
    roles: ['editor', 'admin'],
    when: () => iframe.hasElement(),
    run: () => m.route.set('/page-edit'),
  },
  {
    id: 'page.copy',
    labelKey: 'Copy page',
    roles: ['editor', 'admin'],
    when: () => iframe.hasElement(),
    run: () => m.route.set('/page-copy'),
  },
  {
    id: 'page.delete',
    labelKey: 'Delete page',
    roles: ['editor', 'admin'],
    when: () => iframe.hasElement(),
    run: () => m.route.set('/page-delete'),
  },
  {
    id: 'page.goto',
    labelKey: 'Jump to page',
    run: () => m.route.set('/page-goto'),
  },
  {
    id: 'nav.pages',
    labelKey: 'Pages',
    run: () => m.route.set('/pages'),
  },
  {
    id: 'nav.uploads',
    labelKey: 'Media',
    run: () => m.route.set('/uploads'),
  },
  {
    id: 'nav.backups',
    labelKey: 'Backups',
    run: () => m.route.set('/backups'),
  },
  {
    id: 'nav.design',
    labelKey: 'Design',
    roles: ['admin'],
    run: () => m.route.set('/css'),
  },
  {
    id: 'theme.toggle',
    labelKey: 'Theme',
    run: () => {
      const order = ['auto', 'light', 'dark']
      const current = getTheme()
      setTheme(order[(order.indexOf(current) + 1) % order.length])
    },
  },
]

// Returns the commands the current role may see, filtered by context.
export const visibleCommands = () =>
  commands.filter((cmd) => {
    if (cmd.roles && !cmd.roles.includes(auth.role())) {
      return false
    }
    if (cmd.when && !cmd.when()) {
      return false
    }
    return true
  })
