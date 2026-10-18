import {state} from '../state/state.js'
import {t} from '../model/i18n.js'

let Notification = {
  oninit(vnode) {
    vnode.state.timer = setTimeout(() => {
      Notification.destroy(vnode)
    }, vnode.attrs.timeout)
  },
  onremove(vnode) {
    if (vnode.state.timer) clearTimeout(vnode.state.timer)
    if (vnode.state.destroyTimer) clearTimeout(vnode.state.destroyTimer)
  },
  notificationClass(type) {
    const types = ['info', 'warning', 'success', 'danger']
    if (types.indexOf(type) > -1)
      return type
    return 'info'
  },
  roleFor(type) {
    return (type === 'danger' || type === 'warning') ? 'alert' : 'status'
  },
  pause(vnode) {
    if (vnode.state.timer) {
      clearTimeout(vnode.state.timer)
      vnode.state.timer = null
    }
  },
  resume(vnode) {
    if (vnode.state.timer) return
    vnode.state.timer = setTimeout(() => {
      Notification.destroy(vnode)
    }, vnode.attrs.timeout)
  },
  destroy(vnode) {
    if (vnode.state.timer) {
      clearTimeout(vnode.state.timer)
      vnode.state.timer = null
    }
    vnode.attrs.destroying = true
    m.redraw()
    vnode.state.destroyTimer = setTimeout(() => {
      state.destroy(vnode.attrs)
      m.redraw()
    }, 450)
  },
  view(vnode) {
    let ui = vnode.state
    let msg = vnode.attrs
    const role = ui.roleFor(msg.type)
    const classes = ui.notificationClass(msg.type) + (msg.destroying ? ' destroy' : '')
    return m('.m-notification', {
      class: classes,
      role: role,
      'aria-live': role === 'alert' ? 'assertive' : 'polite',
      onclick: () => ui.destroy(vnode),
      onmouseenter: () => ui.pause(vnode),
      onmouseleave: () => ui.resume(vnode),
    }, [
      m('span.m-notification-text', msg.text),
      m('button.m-notification-close', {
        type: 'button',
        'aria-label': t('Close'),
        onclick: (e) => {
          e.stopPropagation()
          ui.destroy(vnode)
        }
      }, '\u00D7')
    ])
  }
}

export const Notifications = {
  view(vnode) {
    let ui = vnode.state
    return state.list ?
      m('.m-notifications', state.list.map((msg) => {
        return m('div', {
          key: msg.id
        }, m(Notification, msg)) //wrap in div with key for proper dom updates
      })) : null
  }
}
