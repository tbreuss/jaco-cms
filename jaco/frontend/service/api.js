import {addDanger} from '../state/actions.js'
import {app} from '../lib/app.js'
import {denied} from '../lib/denied.js'
import {maintenance} from '../lib/maintenance.js'
import {t, tError} from '../model/i18n.js'

const handlers = {
  // 422 Unprocessable Entity (validation of form data failed)
  422: err => {
    // delegete error handling to parent
    throw err
  },
}

// 401/403 are handled inline (action-aware) rather than via the table above,
// because the login endpoint legitimately returns 401-ish statuses that must
// not trigger a logout/redirect.

const request = method => (action, params = {}, options = {}) => {
  // the route the request belongs to: a slow answer must not raise a denial on
  // the page the user has navigated to in the meantime
  const requestRoute = m.route.get()

  // a new page load on this route starts clean; a stale denial from an earlier
  // visit (or from before a role change) must not hide the page
  if (options.pageLoad === true) {
    denied.clear()
  }

  const token = app.getAuthToken()
  if (token) {
    options.headers = {
      ...(options.headers ?? {}),
      Authorization: 'Bearer ' + token
    }
  }

  let isLoading = true
  const loader = app.getLoaderElement()

  setTimeout(() => {
    if (loader && isLoading) {
      loader.classList.add('is-running')
    }
  }, 400)

  const query = m.buildQueryString(params)

  const response = m.request({
    method,
    url: 'api.php?action=' + action + (query ? '&' + query : ''),
    // Read the maintenance header on every response (success and error alike)
    // without touching extract, which would force every response into a success
    // and break the .catch() handlers below. mithril assigns onreadystatechange
    // before calling config, so wrapping it reads the header synchronously,
    // before the promise resolves or rejects.
    config: (xhr) => {
      const original = xhr.onreadystatechange
      xhr.onreadystatechange = function (event) {
        if (xhr.readyState === 4) {
          maintenance.readFrom(xhr)
        }
        if (original) original.call(this, event)
      }
      return xhr
    },
    // pageLoad is ours, not mithril's
    ...Object.fromEntries(Object.entries(options).filter(([key]) => key !== 'pageLoad'))
  })

  // a successful page load clears a denial that an earlier request left behind;
  // the rejection handler stays a no-op so this side chain never turns into an
  // unhandled rejection of its own
  if (options.pageLoad === true) {
    response.then(() => {
      denied.clear()
      m.redraw()
    }, () => {})
  }

  return response
    .catch(err => {
      const code = err.code || 0
      // expired/invalid session: drop the token, tell the user, and send them
      // to the login screen so they can re-authenticate instead of keeping a
      // stale editor open. The login endpoint itself is excluded (401 there
      // means bad credentials, handled by LoginView).
      // only 401 means the session is gone; a 403 is the role refusing one action,
      // and logging out would throw away a perfectly valid session
      // a page load that answers 403 could not be opened at all: the layout
      // renders the explanation instead of the view. A refused action keeps its
      // toast - the page is fine, only the click failed.
      if (code === 403 && options.pageLoad === true) {
        // only the page that asked shows the denial
        if (m.route.get() === requestRoute) {
          const answer = Array.isArray(err.response) ? err.response[0] : err.response
          denied.show(action, requestRoute, answer?.title ?? '', answer?.detail ?? '')
          m.redraw()
        }
        throw err
      }
      if (code === 401 && action !== 'login' && app.hasAuthToken()) {
        app.removeAuthToken()
        addDanger(t('Session expired', null, 'Session expired. Please log in again.'))
        try {
          m.route.set('/login')
        } catch (e) {
          // no router in this context (e.g. unit tests): ignore
        }
        throw err
      }
      if (code in handlers) {
        handlers[code](err)
      } else {
        // without a response body (network failure, timeout) there is
        // nothing to map, show a generic message instead of crashing
        const errors = Array.isArray(err.response) ? err.response : [err.response ?? {title: 'Request failed'}]
        errors.forEach((error) => {
          // title and detail each arrive as a key in their own right, only
          // their concatenation is not translatable: look both up separately
          // instead of translating the joined string, which always fell back
          // to the English source
          const title = t(error.title, null, error.title)
          addDanger(error.detail ? `${title}: ${tError(error.detail)}` : title)
        })
        // callers pass background: true to suppress mithril's automatic redraw, so
        // without this the toast would sit in the state until the next interaction
        // (it used to appear on whatever page was opened next)
        m.redraw()
        throw err
      }
    })
    .finally(() => {
      isLoading = false
      if (loader) {
        loader.classList.remove('is-running')
      }
    })
}

export const api = {
  get: request('GET'),
  put: request('PUT'),
  post: request('POST'),
  delete: request('DELETE')
}
