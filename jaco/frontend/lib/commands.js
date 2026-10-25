import {auth} from './auth.js'
import {iframe} from './iframe.js'
import {getTheme, setTheme} from './theme.js'

// Command registry: every action is defined once here, the palette only renders
// and executes. Actions call existing functions — no second implementation.
//
// A command has:
//   id       unique key
//   labelKey translation key shown in the palette
//   context  optional function returning the object the command acts on
//   search   optional function returning extra words the filter matches
//   roles    roles that may see/run it (empty = everyone)
//   when     optional context check (returns boolean)
//   run      function receiving the context

// The page the commands act on: the one in the preview. The palette names it,
// so "Edit page" cannot be mistaken for an arbitrary page. The title says more
// than the file name ("Contact" instead of "kontakt.html"), so it leads; the
// file name stays searchable in case the title is unknown.
const currentPageTitle = () => (iframe.hasElement() ? iframe.pageTitle() : '')
const currentPageFile = () => (iframe.hasElement() ? iframe.fileName() : '')
const currentPage = () => currentPageTitle() || currentPageFile()
const currentPageSearch = () => [currentPageTitle(), currentPageFile()]

export const commands = [
  {
    id: 'page.add',
    labelKey: 'Add page',
    roles: ['editor', 'admin'],
    run: () => m.route.set('/page-add'),
  },
  {
    id: 'page.edit-content',
    labelKey: 'Edit page',
    context: currentPage,
    search: currentPageSearch,
    roles: ['editor', 'admin'],
    when: () => iframe.hasElement(),
    run: () => m.route.set('/page-edit'),
  },
  {
    id: 'page.copy',
    labelKey: 'Copy page',
    context: currentPage,
    search: currentPageSearch,
    roles: ['editor', 'admin'],
    when: () => iframe.hasElement(),
    run: () => m.route.set('/page-copy'),
  },
  {
    id: 'page.delete',
    labelKey: 'Delete page',
    context: currentPage,
    search: currentPageSearch,
    roles: ['editor', 'admin'],
    when: () => iframe.hasElement(),
    run: () => m.route.set('/page-delete'),
  },
  {
    id: 'page.goto',
    labelKey: 'Jump to page',
    roles: ['editor', 'admin'],
    run: () => m.route.set('/page-goto'),
  },
  {
    id: 'nav.pages',
    labelKey: 'List pages',
    roles: ['editor', 'admin'],
    run: () => m.route.set('/pages'),
  },
  {
    id: 'nav.uploads',
    labelKey: 'List media',
    roles: ['editor', 'admin'],
    run: () => m.route.set('/uploads'),
  },
  {
    id: 'nav.templates',
    labelKey: 'List templates',
    roles: ['editor', 'admin'],
    run: () => m.route.set('/templates'),
  },
  {
    // backups are an admin area: without a role an editor saw the command and
    // landed on the access-denied page
    id: 'nav.backups',
    labelKey: 'List backups',
    roles: ['admin'],
    run: () => m.route.set('/backups'),
  },
  {
    id: 'nav.design',
    labelKey: 'Edit CSS',
    roles: ['admin'],
    run: () => m.route.set('/css'),
  },
  {
    id: 'theme.toggle',
    labelKey: 'Change theme',
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
