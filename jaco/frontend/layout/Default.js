import {closeDropdown} from '../lib/helpers.js'
import {i18n, t} from '../model/i18n.js'
import {app} from '../lib/app.js'
import {iframe} from '../lib/iframe.js'
import {maintenance} from '../lib/maintenance.js'
import {getTheme, setTheme} from '../lib/theme.js'

const themeLabel = (value) => t(value === 'light' ? 'Light' : value === 'dark' ? 'Dark' : 'Auto')

// native select: one visible control, theme popup handled by the browser -
// no state, nothing to get stuck, touch and keyboard free.
// Only the hover of the dropdown options is styled, in both themes.
const themeSwitch = () => m('select.theme-switch', {
  'aria-label': t('Theme'),
  value: getTheme(),
  onchange: (e) => {
    e.redraw = false
    setTheme(e.target.value)
  },
}, ['light', 'dark', 'auto'].map((value) =>
  m('option', {value}, themeLabel(value))
))

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

// role=status so it is announced when it appears. The mode travels with every
// response header, so it is there from the first paint rather than only after a
// save failed. First in the flow, because it outranks the navigation; the loader
// above it is a fixed overlay and takes no space.
const maintenanceBanner = () =>
  m('.maintenance-banner', {role: 'status'},
    t('Changes are disabled while maintenance mode is enabled.'))

export const DefaultLayout = {
  oncreate: () => {
    iframe.fitBelowNavbar()
    // navbar height depends on wrapping and fonts: re-fit only when it
    // actually changes, not on every redraw
    if (!DefaultLayout.fitObserved) {
      DefaultLayout.fitObserved = true
      const navbar = document.querySelector('.navbar')
      if (navbar && typeof ResizeObserver !== 'undefined') {
        new ResizeObserver(() => iframe.fitBelowNavbar()).observe(navbar)
      }
      window.addEventListener('resize', () => iframe.fitBelowNavbar())
      if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(() => iframe.fitBelowNavbar())
      }
    }
  },
  view: (vnode) => {
    // see https://www.w3schools.com/howto/howto_css_dropdown_navbar.asp
    // the page waits for its translations: labels render from t() and would
    // flash "{keys}" until the translations arrive
    return [
      i18n.status === 'loading' ?
        [m('.toolbar', m('.navbar',
          title(),
          m('a', 'Loading...')
        )),
        m('.main', m('.content', m('p', '…')))] :
        [
          m('.toolbar',
            m.trust('<span id="loader" class="is-stoped"></span>'),
            maintenance.isActive() ? maintenanceBanner() : null,
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
              m(m.route.Link, {href: '/css'}, t('CSS')),
              m('.dropdown',
                m('button.dropbtn', t('System')),
                m('.dropdown-content',
                  m(m.route.Link, {href: '/system/about'}, t('About')),
                  m(m.route.Link, {href: '/system/env'}, t('System environment')),
                  m(m.route.Link, {href: '/system/log'}, t('PHP error log')),
                  m(m.route.Link, {href: '/system/php-info'}, t('PHP info')),
                )
              ),
              m(m.route.Link, {
                class: 'logout',
                href: '/logout'
              }, t('Logout')),
              themeSwitch(),
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
