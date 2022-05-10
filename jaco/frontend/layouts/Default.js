import {closeDropdown} from '../lib/helpers.js'
import {i18n, t} from '../models/i18n.js'

const title = () => m(m.route.Link, {
  class: 'title',
  href: '/',
}, 'JACO')

const showInfo = (e) => {
  if (!e) {
    console.debug('Not yet implemented')
    return
  }
  e.redraw = false
  console.debug(e.target.textContent + ' not yet implemented')
}

export const DefaultLayout = {
  oncreate: () => {
    i18n.loadAndSetLocale(i18n.defaultLocale)
    console.debug('layouts/Default.js oncreate')
  },
  onupdate: () => {
    console.debug('layouts/Default.js onupdate')
    document.documentElement.style.visibility = 'visible'
    document.documentElement.style.opacity = '1'
  },
  view: (vnode) => {
    console.debug('layouts/Default.js view')
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
                    onclick: (e) => {
                      e.redraw = false
                      closeDropdown()
                    }
                  }, t('Edit page')),
                  m(m.route.Link, {
                    href: '/page-copy',
                    onclick: (e) => {
                      e.redraw = false
                      closeDropdown()
                    }
                  }, t('Copy page')),
                  m(m.route.Link, {
                    href: '/page-add',
                    onclick: (e) => {
                      e.redraw = false
                      closeDropdown()
                    }
                  }, t('Add page')),
                  m(m.route.Link, {
                    href: '/page-delete',
                    onclick: (e) => {
                      e.redraw = false
                      closeDropdown()
                    }
                  }, t('Delete page')),
                  m(m.route.Link, {
                    href: '/page-goto',
                    onclick: (e) => {
                      e.redraw = false
                      closeDropdown()
                    }
                  }, t('Goto page')),
                  m('.ruler'),
                  m(m.route.Link, {
                    href: '/page-list',
                    onclick: (e) => {
                      e.redraw = false
                      showInfo()
                      closeDropdown()
                    }
                  }, t('List pages'))
                )
              ),
              m('a[href=#]', {onclick: showInfo}, t('Uploads')),
              m(m.route.Link, {href: '/backups'}, t('Backups')),
              m('a[href=#]', {onclick: showInfo}, t('CSS')),
              m('a[href=#]', {onclick: showInfo}, t('HTML')),
              m(m.route.Link, {href: '/system'}, t('System')),
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
