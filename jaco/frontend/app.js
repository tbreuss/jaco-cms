import { Backups } from './view/Backups.js'
import { PageAdd } from './view/PageAdd.js'
import { PageCopy } from './view/PageCopy.js'
import { PageDelete } from './view/PageDelete.js'
import { PageEdit } from './view/PageEdit.js'
import { PageGoto } from './view/PageGoto.js'
import { Login } from './view/Login.js'
import { Logout } from './view/Logout.js'
import { Start } from './view/Start.js'
import { System } from './view/System.js'
import { DefaultLayout } from './layout/Default.js'
import { Notifications } from './service/Notifications.js'

Number.prototype.pad = function (size) {
  let s = String(this);
  while (s.length < (size || 2)) {
    s = '0' + s;
  }
  return s;
}

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
