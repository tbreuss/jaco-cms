// A page that answers 403 could not be opened at all: the role is too low. The
// api layer records the action together with the route it happened on, and the
// layout renders the explanation instead of the view.
//
// The route is part of the state on purpose. Clearing it from a lifecycle hook
// proved fragile - Mithril reuses components, so "oninit" does not necessarily
// run on every navigation and a stale denial stayed on screen. Comparing with
// the current route and session is stateless: a denial simply does not apply
// anywhere else.

import {app} from './app.js'

export const denied = {
  action: '',
  route: '',
  token: '',
  // the answer as the server worded it: a role that is too low and a read-only
  // account both answer 403, and only the server knows which one it was
  reason: '',
  detail: '',
  show: (action, route, reason = '', detail = '') => {
    denied.action = action
    denied.route = route
    denied.reason = reason
    denied.detail = detail
    // the session as well: after a logout and a fresh login the same route may be
    // allowed, and the old denial must not hide it
    denied.token = app.getAuthToken() ?? ''
  },
  clear: () => {
    denied.action = ''
    denied.route = ''
    denied.token = ''
    denied.reason = ''
    denied.detail = ''
  },
  // only the page that produced the denial, in the session that produced it
  isActive: () => denied.route !== ''
    && denied.route === m.route.get()
    && denied.token === (app.getAuthToken() ?? ''),
}
