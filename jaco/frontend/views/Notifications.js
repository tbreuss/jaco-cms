import { state } from '../state/state.js'

let Notification = {
    oninit(vnode) {
        setTimeout(() => {
            Notification.destroy(vnode)
        }, vnode.attrs.timeout)
    },
    notificationClass(type) {
        const types = ['info', 'warning', 'success', 'danger']
        if (types.indexOf(type) > -1)
            return type
        return 'info'
    },
    destroy(vnode) {
        vnode.dom.classList.add('destroy')
        setTimeout(() => {
            state.destroy(vnode.attrs)
            m.redraw()
        }, 300)
    },
    view(vnode) {
        let ui = vnode.state
        let msg = vnode.attrs
        return m('.m-notification', {
            class: ui.notificationClass(msg.type),
            onclick: () => {
                ui.destroy(vnode)
            }
        }, msg.text)
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
