// Shared registry for "unsaved changes" guarding. Views register a predicate
// (returning true when the editor has unsaved changes) via dirty.set() on
// mount and call dirty.clear() on unmount. The patched m.route.set consults
// it before any in-app navigation (links, programmatic calls) so a stray
// click on a menu item cannot silently discard work.
import {t} from '../model/i18n.js'

const DISCARD_MESSAGE = () => t('Discard unsaved changes?', null, 'Discard unsaved changes?')

const dirty = {
  _check: null,
  set: (fn) => { dirty._check = typeof fn === 'function' ? fn : null },
  clear: () => { dirty._check = null },
  isDirty: () => (typeof dirty._check === 'function' ? !!dirty._check() : false),
  // Run the guard: returns true if navigation should proceed. Prompts when
  // dirty; clears dirty on acceptance. Used by the patched m.route.set and
  // by tests.
  _guard: () => {
    if (!dirty.isDirty()) return true
    const discard = window.confirm(DISCARD_MESSAGE())
    if (!discard) return false
    dirty.clear()
    return true
  },
}

// Patch m.route.set once so navigation is guarded while a view reports
// unsaved changes. Idempotent: a second import does not re-patch.
if (typeof m !== 'undefined' && m.route && typeof m.route.set === 'function' && !m.route.set.__dirtyPatched) {
  const originalSet = m.route.set
  const patched = (path, params, options) => {
    if (!dirty._guard()) {
      return  // abort navigation, stay on the page
    }
    return originalSet(path, params, options)
  }
  patched.__dirtyPatched = true
  m.route.set = patched
}

// Browser back/forward changes the hash without going through m.route.set, so
// guard those too: on a hash change while dirty, confirm; on cancel, restore
// the previous hash (mithril then switches back to the editor).
let lastHash = (typeof location !== 'undefined') ? location.hash : ''
let restoringHash = false
if (typeof window !== 'undefined') {
  window.addEventListener('hashchange', () => {
    if (restoringHash) {
      restoringHash = false
      lastHash = location.hash
      return
    }
    if (!dirty.isDirty()) {
      lastHash = location.hash
      return
    }
    if (!dirty._guard()) {
      restoringHash = true
      location.hash = lastHash  // triggers a hashchange, handled above
      return
    }
    lastHash = location.hash
  })
}

export default dirty
export { dirty }
