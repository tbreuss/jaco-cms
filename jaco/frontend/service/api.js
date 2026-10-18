import {addDanger} from '../state/actions.js'
import {app} from '../lib/app.js'
import {t} from '../model/i18n.js'

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
          const msg = error.detail ? `${error.title}: ${error.detail}` : error.title
          addDanger(t(msg, null, msg))
        })
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
