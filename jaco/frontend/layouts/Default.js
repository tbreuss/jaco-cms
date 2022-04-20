import { Notifications } from '../views/Notifications.js'
import { closeDropdown } from '../lib/helpers.js'

export const DefaultLayout = {
    view: function (vnode) {
        // see https://www.w3schools.com/howto/howto_css_dropdown_navbar.asp
        return [
            m('.toolbar',
                window.sessionStorage.getItem('authenticated') ? m('.navbar', [
                    m(m.route.Link, {
                        class: 'title',
                        href: '/',
                    }, 'JACO'),
                    m('.dropdown',
                        m('button.dropbtn', 'Pages'),
                        m('.dropdown-content',
                            m(m.route.Link, {
                                href: '/page-edit',
                                onclick: () => closeDropdown()
                            }, 'Edit page'),
                            m(m.route.Link, {
                                href: '/page-copy',
                                onclick: () => closeDropdown()
                            }, 'Copy page'),
                            m(m.route.Link, {
                                href: '/page-delete',
                                onclick: () => closeDropdown()
                            }, 'Delete page'),
                            m('.ruler'),
                            m(m.route.Link, {
                                href: '/page-list',
                                onclick: () => closeDropdown()
                            }, 'List pages'),
                            m(m.route.Link, {
                                href: '/page-add',
                                onclick: () => closeDropdown()
                            }, 'Add page'),
                        )
                    ),
                    m('a[href=#]', 'Uploads'),
                    m('a[href=#]', 'Backups'),
                    m('a[href=#]', 'CSS'),
                    m('a[href=#]', 'HTML'),
                    m(m.route.Link, {
                        class: 'logout',
                        href: '/logout'
                    }, 'Logout'),
                ]) : m('.navbar', [
                    m(m.route.Link, {
                        class: 'title',
                        href: '/',
                    }, 'JACO'),
                    m(m.route.Link, {
                        class: 'login',
                        href: '/login'
                    }, 'Login')
                ]),
            ),
            vnode.children,
            m(Notifications),
            m('iframe', {
                src: '../',
                frameborder:'0',
                id: 'iframe'
            }),
        ]
    }
}
