import {DefaultLayout} from './layout/Default.js'
import {Notifications} from './service/Notifications.js'
import {addDanger} from './state/actions.js'

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

const loadComponent = async (path) => {
  const name = path
    .split('/')
    .slice(-1)
    .shift()
    .replace('.js', '')
  try {
    const module = await import('./view/' + path)
    return module[name]
  } catch {
    // e.g. the network is offline while a route module loads
    addDanger('Request failed')
    throw new Error('Could not load component: ' + path)
  }
}

// notifications
m.mount(document.getElementById('toasts'), Notifications)

// application
m.route(document.getElementById('app'), '/', {
  '/': {
    onmatch:  () => {
      return loadComponent('StartView.js')
    },
    render: (vnode) => {
      return m(DefaultLayout, m(vnode.tag))
    }
  },
  '/backups': {
    onmatch:  () => {
      return loadComponent('backup/BackupListView.js')
    },
    render: (vnode) => {
      return m(DefaultLayout, m(vnode.tag))
    }
  },
  '/backups/view/:file...': {
    onmatch:  () => {
      return loadComponent('backup/BackupEditView.js')
    },
    render: (vnode) => {
      return m(DefaultLayout, m(vnode.tag, vnode.attrs))
    }
  },
  '/pages': {
    onmatch:  () => {
      return loadComponent('page/PageListView.js')
    },
    render: (vnode) => {
      return m(DefaultLayout, m(vnode.tag))
    }
  },
  '/pages/edit/:file...': {
    onmatch:  () => {
      return loadComponent('page/PageEditView.js')
    },
    render: (vnode) => {
      return m(DefaultLayout, m(vnode.tag, vnode.attrs))
    }
  },
  '/page-add': {
    onmatch:  () => {
      return loadComponent('PageAddView.js')
    },
    render: (vnode) => {
      return m(DefaultLayout, m(vnode.tag))
    }
  },
  '/page-edit': {
    onmatch:  () => {
      return loadComponent('PageInlineEditView.js')
    },
    render: (vnode) => {
      return m(DefaultLayout, m(vnode.tag))
    }
  },
  '/page-copy': {
    onmatch:  () => {
      return loadComponent('PageCopyView.js')
    },
    render: (vnode) => {
      return m(DefaultLayout, m(vnode.tag))
    }
  },
  '/page-delete': {
    onmatch:  () => {
      return loadComponent('PageDeleteView.js')
    },
    render: (vnode) => {
      return m(DefaultLayout, m(vnode.tag))
    }
  },
  '/page-goto': {
    onmatch:  () => {
      return loadComponent('PageGotoView.js')
    },
    render: (vnode) => {
      return m(DefaultLayout, m(vnode.tag))
    }
  },
  '/uploads': {
    onmatch:  () => {
      return loadComponent('upload/UploadListView.js')
    },
    render: (vnode) => {
      return m(DefaultLayout, m(vnode.tag))
    }
  },
  '/css': {
    onmatch:  () => {
      return loadComponent('CssEditView.js')
    },
    render: (vnode) => {
      return m(DefaultLayout, m(vnode.tag))
    }
  },
  '/uploads/view/:file...': {
    onmatch:  () => {
      return loadComponent('upload/ViewUploadView.js')
    },
    render: (vnode) => {
      return m(DefaultLayout, m(vnode.tag, vnode.attrs))
    }
  },
  '/login': {
    onmatch:  () => {
      return loadComponent('LoginView.js')
    },
    render: (vnode) => {
      return m(DefaultLayout, m(vnode.tag))
    }
  },
  '/logout': {
    onmatch:  () => {
      return loadComponent('LogoutView.js')
    },
    render: (vnode) => {
      return m(DefaultLayout, m(vnode.tag))
    }
  },
  '/system/env': {
    onmatch:  () => {
      return loadComponent('SystemView.js')
    },
    render: (vnode) => {
      return m(DefaultLayout, m(vnode.tag))
    }
  },
  '/system/log': {
    onmatch:  () => {
      return loadComponent('PhpLogView.js')
    },
    render: (vnode) => {
      return m(DefaultLayout, m(vnode.tag))
    }
  },
  '/system/php-info': {
    onmatch:  () => {
      return loadComponent('PhpInfoView.js')
    },
    render: (vnode) => {
      return m(DefaultLayout, m(vnode.tag))
    }
  }
})

window.addEventListener('load', (event) => {
  document.documentElement.style.visibility = 'visible' // prevent flickering
})
