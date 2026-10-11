import {addDanger} from '../state/actions.js'
import {app} from '../lib/app.js'

const handlers = {
  // 422 Unprocessable Entity (validation of form data failed)
  422: err => {
    // delegete error handling to parent
    throw err
  },
}

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

  return m.request({
    method,
    url: 'api.php?action=' + action + '&' + m.buildQueryString(params),
    ...options // might need Object.assign for Edge
  })
    .catch(err => {
      const code = err.code || 0
      if (code in handlers) {
        handlers[code](err)
      } else {
        err.response.forEach((error) => {
          addDanger(error.title)
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
