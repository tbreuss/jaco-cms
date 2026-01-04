import {closeDropdown} from '../lib/helpers.js'
import {i18n, t} from '../model/i18n.js'
import {app} from '../lib/app.js'

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
            m.trust('<span id="loader" class="is-stoped"></span>'),
            app.hasAuthToken() ? m('.navbar', [
              title(),
              m('.dropdown',
                m('button.dropbtn', t('Quick Action')),
                m('.dropdown-content',
                  m(m.route.Link, {
                    href: '/page-goto',
                    onclick: (e) => {
                      e.redraw = false
                      closeDropdown()
                    }
                  }, t('Go to page')),
                  m(m.route.Link, {
                    href: '/page-edit',
                    onclick: (e) => {
                      e.redraw = false
                      closeDropdown()
                    }
                  }, t('Edit page')),
                  m(m.route.Link, {
                    href: '/page-add',
                    onclick: (e) => {
                      e.redraw = false
                      closeDropdown()
                    }
                  }, t('Add page')),
                  m(m.route.Link, {
                    href: '/page-copy',
                    onclick: (e) => {
                      e.redraw = false
                      closeDropdown()
                    }
                  }, t('Copy page')),
                  m(m.route.Link, {
                    href: '/page-delete',
                    onclick: (e) => {
                      e.redraw = false
                      closeDropdown()
                    }
                  }, t('Delete page')),
                )
              ),
              m(m.route.Link, {href: '/pages'}, t('Pages')),
              m(m.route.Link, {href: '/uploads'}, t('Uploads')),
              m(m.route.Link, {href: '/backups'}, t('Backups')),
              m('.dropdown',
                m('button.dropbtn', t('Diagnostics')),
                m('.dropdown-content',
                  m(m.route.Link, {href: '/system/env'}, t('System environment')),
                  m(m.route.Link, {href: '/system/log'}, t('PHP error log')),
                  m(m.route.Link, {href: '/system/php-info'}, t('PHP info')),
                )
              ),
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
            ]),
          ),
          vnode.children,
        ]
    ]
  }
}
