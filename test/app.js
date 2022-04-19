import { PageCopy } from './views/PageCopy.js'
import { PageEdit } from './views/PageEdit.js'
import { PageDelete } from './views/PageDelete.js'
import { Login } from './views/Login.js'
import { Logout } from './views/Logout.js'
import { Start } from './views/Start.js'
import { DefaultLayout } from './layouts/Default.js'

m.route(document.body, "/", {
    "/": {
        render: function () {
            return m(DefaultLayout, m(Start))
        }
    },
    "/page-edit": {
        render: function () {
            return m(DefaultLayout, m(PageEdit))
        }
    },
    "/page-copy": {
        render: function () {
            return m(DefaultLayout, m(PageCopy))
        }
    },
    "/page-delete": {
        render: function () {
            return m(DefaultLayout, m(PageDelete))
        }
    },
    "/login": {
        render: function () {
            return m(DefaultLayout, m(Login))
        }
    },
    "/logout": {
        render: function () {
            window.sessionStorage.removeItem('authenticated')
            return m(DefaultLayout, m(Logout))
        }
    }
})