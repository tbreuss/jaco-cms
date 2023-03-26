import {addDanger} from '../state/actions.js'
import {app} from '../lib/app.js'

const handlers = {
  // 422 Unprocessable Entity (validation of form data failed)
  422: err => {
    // delegete error handling to parent
    throw err
  },
}

const request = method => (url, options) => {
  const token = app.getAuthToken()
  if (token) {
    if (!options) {
      options = {}
    }
    options.headers = {
      Authorization: 'Bearer ' + token
    }
  }

  let isLoading = true
  const loader = document.getElementById('loader')

  setTimeout(() => {
    if (loader && isLoading) {
      loader.classList.add('is-running')
    }
  }, 400)

  return m.request({
    method,
    url: 'api.php?' + url + '&XDEBUG_SESSION_START',
    ...options // might need Object.assign for Edge
  })
    .catch(err => {
      const code = err.code || 0
      if (code in handlers) {
        handlers[code](err)
      } else {
        err.response.errors.forEach((error) => {
          addDanger(error.title)
        })
        throw err
      }
    })
    .finally(() => {
      isLoading = false
      const loader = document.getElementById('loader')
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
