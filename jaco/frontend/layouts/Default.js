import { closeDropdown } from '../lib/helpers.js'
import { i18n, t } from '../models/i18n.js'

const title = () => m(m.route.Link, {
  class: 'title',
  href: '/',
}, 'JACO')

export const DefaultLayout = {
  oncreate: () => {
    i18n.loadAndSetLocale(i18n.defaultLocale)
  },
  view: (vnode) => {
    // see https://www.w3schools.com/howto/howto_css_dropdown_navbar.asp
    return [
      i18n.status === 'loading' ?
        m('.toolbar', m('.navbar',
          title(),
          m('a', 'Loading...')
        )) :
        [
          m('.toolbar',
            window.sessionStorage.getItem('AUTHORIZATION_TOKEN') ? m('.navbar', [
              title(),
              m('.dropdown',
                m('button.dropbtn', t('Pages')),
                m('.dropdown-content',
                  m(m.route.Link, {
                    href: '/page-edit',
                    onclick: () => closeDropdown()
                  }, t('Edit page')),
                  m(m.route.Link, {
                    href: '/page-copy',
                    onclick: () => closeDropdown()
                  }, t('Copy page')),
                  m(m.route.Link, {
                    href: '/page-add',
                    onclick: () => closeDropdown()
                  }, t('Add page')),
                  m(m.route.Link, {
                    href: '/page-delete',
                    onclick: () => closeDropdown()
                  }, t('Delete page')),
                  m('.ruler'),
                  m(m.route.Link, {
                    href: '/page-list',
                    onclick: () => closeDropdown()
                  }, t('List pages'))
                )
              ),
              m('a[href=#]', t('Uploads')),
              m('a[href=#]', t('Backups')),
              m('a[href=#]', t('CSS')),
              m('a[href=#]', t('HTML')),
              m(m.route.Link, {
                class: 'logout',
                href: '/logout'
              }, t('Logout')),
            ]) : m('.navbar', [
              title(),
              m(m.route.Link, {
                class: 'login',
                href: '/login'
              }, t('Login'))
            ])
          ),
          vnode.children
        ]
    ]
  }
}
