import {BackupEditView} from './view/backup/BackupEditView.js'
import {BackupListView} from './view/backup/BackupListView.js'
import {DefaultLayout} from './layout/Default.js'
import {LoginView} from './view/LoginView.js'
import {LogoutView} from './view/LogoutView.js'
import {Notifications} from './service/Notifications.js'
import {PageAddView} from './view/PageAddView.js'
import {PageCopyView} from './view/PageCopyView.js'
import {PageDeleteView} from './view/PageDeleteView.js'
import {PageEditView} from './view/page/PageEditView.js'
import {PageInlineEditView} from './view/PageInlineEditView.js'
import {PageGotoView} from './view/PageGotoView.js'
import {PageListView} from './view/page/PageListView.js'
import {PhpInfoView} from './view/PhpInfoView.js'
import {PhpLogView} from './view/PhpLogView.js'
import {StartView} from './view/StartView.js'
import {SystemView} from './view/SystemView.js'
import {UploadListView} from './view/upload/UploadListView.js'

Number.prototype.pad = function (size) {
  let s = String(this)
  while (s.length < (size || 2)) {
    s = '0' + s
  }
  return s
}

Number.prototype.toDateTime = function () {
  const date = new Date(this * 1000)
  let parts = []
  parts.push(date.getDate().pad(2))
  parts.push('.')
  parts.push((date.getMonth() + 1).pad(2))
  parts.push('.')
  parts.push(date.getFullYear())
  parts.push(' ')
  parts.push(date.getHours().pad(2))
  parts.push(':')
  parts.push(date.getMinutes().pad(2))
  parts.push(':')
  parts.push(date.getSeconds().pad(2))
  return parts.join('')
}

// notifications
m.mount(document.getElementById('toasts'), Notifications)

// application
m.route(document.getElementById('app'), '/', {
  '/': {
    render: () => {
      return m(DefaultLayout, m(StartView))
    }
  },
  '/backups': {
    render: () => {
      return m(DefaultLayout, m(BackupListView))
    }
  },
  '/backups/view/:file': {
    render: (vnode) => {
      return m(DefaultLayout, m(BackupEditView, vnode.attrs))
    }
  },
  '/pages': {
    render: () => {
      return m(DefaultLayout, m(PageListView))
    }
  },
  '/pages/edit/:file': {
    render: (vnode) => {
      return m(DefaultLayout, m(PageEditView, vnode.attrs))
    }
  },
  '/page-add': {
    render: () => {
      return m(DefaultLayout, m(PageAddView))
    }
  },
  '/page-edit': {
    render: () => {
      return m(DefaultLayout, m(PageInlineEditView))
    }
  },
  '/page-copy': {
    render: () => {
      return m(DefaultLayout, m(PageCopyView))
    }
  },
  '/page-delete': {
    render: () => {
      return m(DefaultLayout, m(PageDeleteView))
    }
  },
  '/page-goto': {
    render: () => {
      return m(DefaultLayout, m(PageGotoView))
    }
  },
  '/uploads': {
    render: () => {
      return m(DefaultLayout, m(UploadListView))
    }
  },
  '/uploads/edit/:file': {
    render: (vnode) => {
      return m(DefaultLayout, m(ViewUploadView, vnode.attrs))
    }
  },
  '/login': {
    render: () => {
      return m(DefaultLayout, m(LoginView))
    }
  },
  '/logout': {
    render: () => {
      return m(DefaultLayout, m(LogoutView))
    }
  },
  '/system/env': {
    render: () => {
      return m(DefaultLayout, m(SystemView))
    }
  },
  '/system/log': {
    render: () => {
      return m(DefaultLayout, m(PhpLogView))
    }
  },
  '/system/php-info': {
    render: () => {
      return m(DefaultLayout, m(PhpInfoView))
    }
  }
})

window.addEventListener('load', (event) => {
  document.documentElement.style.visibility = 'visible' // prevent flickering
})
