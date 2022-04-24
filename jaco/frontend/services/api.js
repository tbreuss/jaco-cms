import { addDanger } from '../state/actions.js'

const handlers = {
  // 422 Unprocessable Entity (validation of form data failed)
  422: err => {
    // delegete error handling to parent
    throw err
  },
}

const request = method => (url, options) => {
  const token = window.sessionStorage.getItem('AUTHORIZATION_TOKEN')
  if (token) {
    if (!options) {
      options = {}
    }
    options.headers = {
      Authorization: 'Bearer ' + token
    }
  }

  return m.request({
    method,
    url: 'api.php?' + url, // + '&XDEBUG_SESSION_START',
    ...options // might need Object.assign for Edge
  })
    .catch(err => {
      const code = err.code || 0
      if (err.code in handlers) {
        handlers[err.code](err)
      } else {
        let message = err.response.error + ' (' + err.code + ')'
        addDanger(message)
        throw err
      }
    })
}

export const api = {
  get: request('GET'),
  put: request('PUT'),
  post: request('POST'),
  delete: request('DELETE')
}
