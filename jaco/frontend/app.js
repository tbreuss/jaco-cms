import { PageCopy } from './views/PageCopy.js'
import { PageEdit } from './views/PageEdit.js'
import { PageDelete } from './views/PageDelete.js'
import { Login } from './views/Login.js'
import { Logout } from './views/Logout.js'
import { Start } from './views/Start.js'
import { DefaultLayout } from './layouts/Default.js'
import { PageAdd } from './views/PageAdd.js'

m.route(document.getElementById('app'), '/', {
  '/': {
    render: () => {
      return m(DefaultLayout, m(Start))
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
  '/login': {
    render: () => {
      return m(DefaultLayout, m(Login))
    }
  },
  '/logout': {
    render: () => {
      return m(DefaultLayout, m(Logout))
    }
  }
})
