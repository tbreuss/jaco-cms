import {addDanger} from '../state/actions.js'
import {app} from '../lib/app.js'
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

  return m.request({
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
    ...options // might need Object.assign for Edge
  })
    .catch(err => {
      const code = err.code || 0
      // expired/invalid session: drop the token, tell the user, and send them
      // to the login screen so they can re-authenticate instead of keeping a
      // stale editor open. The login endpoint itself is excluded (401 there
      // means bad credentials, handled by LoginView).
      if ((code === 401 || code === 403) && action !== 'login' && app.hasAuthToken()) {
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
