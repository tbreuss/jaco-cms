// helpers.js
function guid() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
      var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
      return v.toString(16);
    });
  }

// Notifications.js

let state = {
    list: [],
    destroy(msg) {
        let index = state.list.findIndex(x => x.id === msg.id)
        state.list.splice(index, 1)
    }
}

function addSuccess(text, timeout = 3000) {
    state.list.push({
        id: guid(),
        type: 'success',
        text,
        timeout
    })
}

function addInfo(text, timeout = 3000) {
    state.list.push({
        id: guid(),
        type: 'info',
        text,
        timeout
    })
}

function addWarning(text, timeout = 3000) {
    state.list.push({
        id: guid(),
        type: 'warning',
        text,
        timeout
    })
}

function addDanger(text, timeout = 3000) {
    state.list.push({
        id: guid(),
        type: 'danger',
        text,
        timeout
    })
}

let Notifications = {
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


var enableEditor = false

var iframe = {
    pageTitle: function () {
        var iframe = document.getElementById('pageIframe')
        if (!iframe) {
            return ''
        }
        return iframe.contentDocument.title
    },
    fileName: function () {
        var iframe = document.getElementById('pageIframe')
        if (!iframe) {
            return ''
        }
        var pathname = iframe.contentDocument.location.pathname
        return pathname === '/' ? '/index.html' : pathname
    },
    makeEditable: function () {

        var iframe = document.getElementById('pageIframe')
        if (!iframe) {
            return
        }

        // add editor script
        if (!iframe.contentDocument.getElementById('editorInstanceJavascript')) {
            var script = iframe.contentDocument.createElement('script');
            script.id = 'editorInstanceJavascript';
            script.type = 'text/javascript';
            script.src = 'test/editor.js';
            script.async = false;
            script.onload = function () {
                iframe.contentWindow.initEditors()
            };
            iframe.contentDocument.head.appendChild(script);
        } else {
            iframe.contentWindow.initEditors()
        }
    },
    removeEditors: function () {
        var iframe = document.getElementById('pageIframe')
        if (!iframe) {
            return
        }
        if (typeof iframe.contentWindow.removeEditors === "function") {
            iframe.contentWindow.removeEditors()
        }
    },
    disableEditors: function () {
        var iframe = document.getElementById('pageIframe')
        if (!iframe) {
            return
        }
        if (typeof iframe.contentWindow.disableEditors === "function") {
            iframe.contentWindow.disableEditors()
        }
    },
    reload: function () {
        var iframe = document.getElementById('pageIframe')
        if (!iframe) {
            return
        }
        iframe.contentWindow.location.reload();
    },
    getHTML: function () {
        var iframe = document.getElementById('pageIframe')
        if (!iframe) {
            throw error
        }
        iframe.contentWindow.removeEditorInstances()
        iframe.contentWindow.removeEditors()
        return iframe.contentWindow.document.documentElement.outerHTML;
    }
}

// Layout.js
var Layout = {
    view: function (vnode) {
        // see https://www.w3schools.com/howto/howto_css_dropdown_navbar.asp
        return [
            m(".toolbar",
                window.sessionStorage.getItem('authenticated') ? m(".navbar", [
                    m(m.route.Link, {
                        class: 'title',
                        href: '/',
                    }, 'JACO'),
                    m('.dropdown',
                        m('button.dropbtn', 'Pages'),
                        m('.dropdown-content',
                            m(m.route.Link, {
                                href: '/page-edit'
                            }, "Edit page"),
                            m(m.route.Link, {
                                href: '/page-copy'
                            }, "Copy page"),
                            m(m.route.Link, {
                                href: '/page-delete'
                            }, "Delete page"),
                            m('.ruler'),
                            m(m.route.Link, {
                                href: '/page-list'
                            }, "List pages"),
                            m(m.route.Link, {
                                href: '/page-add'
                            }, "Add page"),
                        )
                    ),
                    m('a[href=#]', 'Uploads'),
                    m('a[href=#]', 'Backups'),
                    m('a[href=#]', 'CSS'),
                    m('a[href=#]', 'HTML'),
                    m(m.route.Link, {
                        class: 'logout',
                        href: '/logout'
                    }, "Logout"),
                ]) : m(".navbar", [
                    m(m.route.Link, {
                        class: 'title',
                        href: '/',
                    }, 'JACO'),
                    m(m.route.Link, {
                        class: 'login',
                        href: '/login'
                    }, "Login")
                ]),
            ),
            vnode.children,
            m(Notifications),
        ]
    }
}

// Start
var Start = {
    view: function () {}
}

// Login
var Login = {
    username: '',
    password: '',
    view: function () {
        return m('.modal',
            m('.modal-inner',
                m('h1', 'Login'),
                m('div',
                    m('input[type=text]', {
                        value: Login.username,
                        placeholder: 'Username',
                        oninput: function (e) {
                            Login.username = e.target.value
                        }
                    })
                ),
                m('div',
                    m('input[type=password]', {
                        value: Login.password,
                        placeholder: 'Password',
                        oninput: function (e) {
                            Login.password = e.target.value
                        }
                    })
                ),
                m('button', {
                    onclick: function (e) {
                        window.sessionStorage.setItem('authenticated', true)
                        m.route.set('/')
                    }
                }, 'Login')
            )
        )
    }
}

// Logout
var Logout = {
    view: function () {
        return m('.modal',
            m('.modal-inner',
                m('h1', 'Logged out'),
                m('ul.unstyled',
                    m('li', m('a', {
                        href: '..'
                    }, 'Go to homepage')),
                    m('li', m(m.route.Link, {
                        class: 'login',
                        href: '/login'
                    }, "Login again"))
                )
            )
        )
    }
}

// PageEdit
var PageEdit = {
    oninit: function () {
        // i.e. if page was reloaded with given route
        if (!document.getElementById('pageIframe')) {
            iframe.reload()
            m.route.set('/')
        }
        iframe.makeEditable()
    },
    view: function () {
        return m(".dialog",
            m('a[href=#]', {
                onclick: function (e) {
                    e.preventDefault()
                    const fileName = iframe.fileName()
                    const html = iframe.getHTML()
                    iframe.reload()
                    console.log(fileName, html)
                    addSuccess('Page was successfully saved.')
                    m.route.set('/')
                }
            }, 'Save'),
            m('a[href=#]', {
                onclick: function (e) {
                    e.preventDefault()
                    iframe.reload()
                    m.route.set('/')
                }
            }, 'Cancel'),
        )
    }
}

// PageCopy
var PageCopy = {
    view: function () {
        return m('.modal',
            m('.modal-inner',
                m('h2', 'Page Copy'),
                m('button', {
                    onclick: () => m.route.set('/')
                }, 'OK')
            )
        )
    }
}

// PageDelete
var PageDelete = {
    view: function () {
        return m('.modal',
            m('.modal-inner',
                m('h2', 'Delete page'),
                m('p', 'Do you really want to delete the page: ' + iframe.fileName() + '?'),
                m('div',
                    m('button', {
                        onclick: () => {
                            addSuccess('Page was successfully deleted.')                            
                            m.route.set('/')
                    }
                    }, 'Yes'),
                    m('a', {
                        href: '#',
                        onclick: function (e) {
                            e.preventDefault()
                            m.route.set('/')
                        }
                    }, 'No')
                )
            )
        )
    }
}

m.route(document.body, "/", {
    "/": {
        render: function () {
            return m(Layout, m(Start))
        }
    },
    "/page-edit": {
        render: function () {
            return m(Layout, m(PageEdit))
        }
    },
    "/page-copy": {
        render: function () {
            return m(Layout, m(PageCopy))
        }
    },
    "/page-delete": {
        render: function () {
            return m(Layout, m(PageDelete))
        }
    },
    "/login": {
        render: function () {
            return m(Layout, m(Login))
        }
    },
    "/logout": {
        render: function () {
            window.sessionStorage.removeItem('authenticated')
            return m(Layout, m(Logout))
        }
    }
})