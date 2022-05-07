import { Backups } from './views/Backups.js'
import { PageAdd } from './views/PageAdd.js'
import { PageCopy } from './views/PageCopy.js'
import { PageDelete } from './views/PageDelete.js'
import { PageEdit } from './views/PageEdit.js'
import { PageGoto } from './views/PageGoto.js'
import { Login } from './views/Login.js'
import { Logout } from './views/Logout.js'
import { Start } from './views/Start.js'
import { System } from './views/System.js'
import { DefaultLayout } from './layouts/Default.js'
import { Notifications } from './services/Notifications.js'

// notifications
m.mount(document.getElementById('toasts'), Notifications)

// application
m.route(document.getElementById('app'), '/', {
  '/': {
    render: () => {
      return m(DefaultLayout, m(Start))
    }
  },
  '/backups': {
    render: () => {
      return m(DefaultLayout, m(Backups))
    }
  },
  '/page-add': {
    render: () => {
      return m(DefaultLayout, m(PageAdd))
    }
  },
  '/page-edit': {
    render: () => {
      return m(DefaultLayout, m(PageEdit))
    }
  },
  '/page-copy': {
    render: () => {
      return m(DefaultLayout, m(PageCopy))
    }
  },
  '/page-delete': {
    render: () => {
      return m(DefaultLayout, m(PageDelete))
    }
  },
  '/page-goto': {
    render: () => {
      return m(DefaultLayout, m(PageGoto))
    }
  },
  '/login': {
    render: () => {
      return m(DefaultLayout, m(Login))
    }
  },
  '/logout': {
    render: () => {
      return m(DefaultLayout, m(Logout))
    }
  },
  '/system': {
    render: () => {
      return m(DefaultLayout, m(System))
    }
  }
})
