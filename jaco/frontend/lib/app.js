const AUTH_TOKEN = 'AUTHORIZATION_TOKEN'

export const app = {
  makeAppScrollable: () => {
    const app = document.getElementById('app')
    if (!app.classList.contains('max-height')) {
      app.classList.add('max-height')
    }
  },
  makeIframeScrollable: () => {
    document.getElementById('app').classList.remove('max-height')
  },
  getAuthToken: () => {
    return window.sessionStorage.getItem(AUTH_TOKEN)
  },
  hasAuthToken: () => {
    return app.getAuthToken() !== null
  },
  removeAuthToken: () => {
    window.sessionStorage.removeItem(AUTH_TOKEN)
  },
  setAuthToken: (token) => {
    window.sessionStorage.setItem(AUTH_TOKEN, token)
  },
}
