import {i18n, t} from '../model/i18n.js'
import {app} from '../lib/app.js'
import {auth} from '../lib/auth.js'
import {iframe} from '../lib/iframe.js'
import {maintenance} from '../lib/maintenance.js'
import {getTheme, setTheme} from '../lib/theme.js'
import {Icon} from '../component/Icon.js'
import {CommandPalette, openPalette, paletteState} from '../view/CommandPaletteView.js'

// Only one dropdown is open at a time. The state lives here instead of in each
// menu so clicking another trigger closes the previous menu.
let openMenu = null
// Distinguishes a touch tap from a mouse click on hybrid devices; a click event
// itself does not carry the pointer type.
let lastPointerType = 'mouse'

const closeMenu = () => {
  if (openMenu === null) {
    return
  }
  openMenu = null
  m.redraw()
}

const toggleMenu = (name) => {
  openMenu = openMenu === name ? null : name
  m.redraw()
}

const onDocumentClick = (event) => {
  if (openMenu === null) {
    return
  }
  if (event.target instanceof Element && event.target.closest('.dropdown')) {
    return
  }
  closeMenu()
}

const onDocumentKeydown = (event) => {
  if (event.key !== 'Escape' || openMenu === null) {
    return
  }
  const trigger = document.querySelector('.dropdown.is-open .dropbtn')
  closeMenu()
  trigger?.focus()
}

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

// Menu entries follow the same rule as the action buttons: stay visible, but
// disabled, so a client can see the capabilities without being able to use them.
const disabledNav = (label) =>
  m('span.nav-disabled', {
    title: t('Your role does not allow this.'),
    'aria-disabled': 'true',
    role: 'menuitem',
    tabindex: '0',
  }, label)

const roleLabel = (role) => {
  if (role === 'admin') {
    return t('Admin')
  }
  if (role === 'editor') {
    return t('Editor')
  }
  return t('Viewer')
}

const accountInfo = () => {
  const username = auth.username()
  const role = roleLabel(auth.role())
  const roleNode = m('span.nav-account-role', username ? `(${role})` : role)
  return m('span.nav-account-info', {role: 'presentation'},
    username ? [username, ' ', roleNode] : roleNode)
}

const menuLink = (href, label) => m(m.route.Link, {
  href: href,
  role: 'menuitem',
  onclick: () => {
    closeMenu()
  }}, label)

// Hybrid dropdown: a mouse hover opens it on fine pointers, a click/tap toggles
// it on touch, and keyboard users get the same click path. The open menu stays
// in the DOM flow; hover only applies where the device actually has a hover.
const canHover = () => typeof window.matchMedia === 'function'
  && window.matchMedia('(hover: hover) and (pointer: fine)').matches

const openMenuOnHover = (name) => {
  if (openMenu !== name) {
    openMenu = name
    m.redraw()
  }
}

const dropdown = (name, label, items, extraClass = '') => {
  const open = openMenu === name
  const id = 'menu-' + name

  return m('.dropdown' + extraClass + (open ? '.is-open' : ''), {
    onpointerdown: (e) => {
      lastPointerType = e.pointerType
    },
    onpointerenter: (e) => {
      if (e.pointerType === 'mouse' && canHover()) {
        openMenuOnHover(name)
      }
    },
    onpointerleave: (e) => {
      if (e.pointerType === 'mouse' && canHover() && openMenu === name) {
        closeMenu()
      }
    },
    onfocusout: (e) => {
      if (!e.currentTarget.contains(e.relatedTarget)) {
        closeMenu()
      }
    },
  }, [
    m('button.dropbtn', {
      type: 'button',
      'aria-haspopup': 'menu',
      'aria-expanded': open ? 'true' : 'false',
      'aria-controls': id,
      onclick: (e) => {
        e.stopPropagation()
        // A mouse click on a hover-capable device keeps the already opened menu
        // open; a keyboard click (detail 0) and a touch/pen tap toggle it.
        if (e.detail > 0 && lastPointerType === 'mouse' && canHover()) {
          if (openMenu !== name) {
            openMenuOnHover(name)
          }
          return
        }
        toggleMenu(name)
      },
      onkeydown: (e) => {
        if (e.key === 'Escape') {
          e.preventDefault()
          closeMenu()
          return
        }
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
          e.preventDefault()
          if (!open) {
            toggleMenu(name)
          }
          setTimeout(() => {
            const entries = [...document.querySelectorAll('#' + id + ' a, #' + id + ' .nav-disabled')]
            const target = e.key === 'ArrowUp' ? entries[entries.length - 1] : entries[0]
            if (target) {
              target.focus()
            }
          }, 0)
        }
      },
    }, label),
    m('.dropdown-content', {
      id: id,
      role: 'menu',
      class: open ? 'is-open' : undefined,
    }, items),
  ])
}

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

// A viewer cannot persist anything. That stays true when the inline editor is
// open, so the hint says what actually happens instead of forbidding the editor.
const readOnlyBanner = () =>
  m('.readonly-banner', {role: 'status'},
    t('Read-only access: changes are not saved.'))

// Ctrl+K / Cmd+K opens the palette. The shortcut still must not steal keys
// from the rich-text or Ace editors; modals are deliberately not blocked, so
// the palette is also available over a dialog.
const paletteShortcut = typeof navigator !== 'undefined' && /Mac|iPhone|iPad|iPod/.test(navigator.platform)
  ? '⌘K'
  : 'Ctrl+K'

const paletteGuard = (e) => {
  const isCtrlK = e.key.toLowerCase() === 'k' && (e.ctrlKey || e.metaKey)

  if (!isCtrlK || e.repeat || e.defaultPrevented || paletteState.open) {
    return
  }
  if (!app.hasAuthToken()) {
    return
  }
  if (e.altKey || e.shiftKey) {
    return
  }

  const el = (e.currentTarget && e.currentTarget.activeElement
    ? e.currentTarget
    : document).activeElement
  const inEditor = el && (
    el.isContentEditable ||
    (el.closest && el.closest('.ql-editor, .ace_editor, .palette'))
  )

  if (inEditor) {
    return
  }

  e.preventDefault()
  openPalette()
}

// The preview is an iframe: key events inside it never reach the parent
// document. Bind the same guard to the iframe document and re-bind after every
// navigation, because a new document starts without listeners.
const IFRAME_PALETTE_FLAG = 'jacoPaletteGuardBound'

const bindIframePalette = () => {
  if (DefaultLayout.iframePaletteBinding) {
    return
  }
  const frame = document.getElementById('iframe')
  if (!frame) {
    return
  }
  const bind = () => {
    try {
      const doc = frame.contentDocument
      if (!doc || doc[IFRAME_PALETTE_FLAG]) {
        return
      }
      doc[IFRAME_PALETTE_FLAG] = true
      doc.addEventListener('keydown', paletteGuard)
    } catch {
      // a cross-origin preview cannot be reached; the parent listener still works
    }
  }
  frame.addEventListener('load', bind)
  DefaultLayout.iframePaletteBinding = {frame, bind}
  bind()
}

const unbindIframePalette = () => {
  const binding = DefaultLayout.iframePaletteBinding
  if (!binding) {
    return
  }
  binding.frame.removeEventListener('load', binding.bind)
  try {
    const doc = binding.frame.contentDocument
    if (doc && doc[IFRAME_PALETTE_FLAG]) {
      doc.removeEventListener('keydown', paletteGuard)
      delete doc[IFRAME_PALETTE_FLAG]
    }
  } catch {
    // the document may already be gone
  }
  DefaultLayout.iframePaletteBinding = null
}

export const DefaultLayout = {
  oncreate: () => {
    iframe.fitBelowNavbar()
    if (!DefaultLayout.paletteListener) {
      DefaultLayout.paletteListener = true
      document.addEventListener('keydown', paletteGuard)
    }
    bindIframePalette()
    if (!DefaultLayout.dropdownListeners) {
      DefaultLayout.dropdownListeners = true
      document.addEventListener('click', onDocumentClick)
      document.addEventListener('keydown', onDocumentKeydown)
    }
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
  onremove: () => {
    unbindIframePalette()
    document.removeEventListener('keydown', paletteGuard)
    DefaultLayout.paletteListener = false
    document.removeEventListener('click', onDocumentClick)
    document.removeEventListener('keydown', onDocumentKeydown)
    DefaultLayout.dropdownListeners = false
    openMenu = null
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
        m('.main', m('.content', m('p', '…'))),
        m(CommandPalette)] :
        [
          m(CommandPalette),
          m('.toolbar',
            m.trust('<span id="loader" class="is-stoped"></span>'),
            maintenance.isActive() ? maintenanceBanner() : null,
            app.hasAuthToken() && auth.isViewer() ? readOnlyBanner() : null,
            app.hasAuthToken() ? m('.navbar', [
              title(),
              dropdown('content', t('Content'), [
                menuLink('/pages', t('Pages')),
                menuLink('/uploads', t('Media')),
                menuLink('/backups', t('Backups')),
                menuLink('/css', t('Design')),
              ]),
              dropdown('this-page', t('This page'), [
                menuLink('/page-edit', t('Edit page')),
                auth.can('editor') ? menuLink('/page-copy', t('Copy page')) : disabledNav(t('Copy page')),
                auth.can('editor') ? menuLink('/page-delete', t('Delete page')) : disabledNav(t('Delete page')),
              ]),
              m(m.route.Link, {href: '/page-goto', class: 'jump-to-page'},
                m(Icon, {name: 'search'}),
                m('span', t('Jump to page'))),
              m('button.palette-trigger', {
                type: 'button',
                onclick: () => openPalette(),
                title: t('Commands') + ' (' + paletteShortcut + ')',
                'aria-label': t('Commands') + ' (' + paletteShortcut + ')',
              }, m('kbd.palette-shortcut', paletteShortcut), m('span', t('Commands'))),
              dropdown('admin', t('Admin'), auth.can('admin') ? [
                menuLink('/system/env', t('Environment')),
                menuLink('/system/log', t('PHP error log')),
                menuLink('/system/php-info', t('PHP info')),
              ] : [
                disabledNav(t('Environment')),
                disabledNav(t('PHP error log')),
                disabledNav(t('PHP info')),
              ]),
              dropdown('account', t('Account'), [
                accountInfo(),
                menuLink('/system/about', t('About')),
                auth.canChangePassword() ? menuLink('/password', t('Change password')) : disabledNav(t('Change password')),
                menuLink('/logout', t('Logout')),
              ], '.account-menu'),
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
