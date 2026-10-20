import {api} from '../service/api.js'
import {iframe} from './iframe.js'

export const createPage = (data) => api.post('page/create', {}, {
  background: true, // no redraw
  body: data,
  withCredentials: false,
})

// Continue the add-page flow in the inline editor instead of dropping the user
// back on the list. Falls back to the list when the preview is not available.
export const continueAfterCreate = (fileName) => {
  let frame = null
  try {
    if (!iframe.hasElement()) {
      m.route.set('/pages')
      return
    }
    frame = iframe.getElement()
  } catch {
    m.route.set('/pages')
    return
  }

  frame.onload = () => {
    frame.onload = null
    frame.onerror = null
    m.route.set('/page-edit')
  }
  frame.onerror = () => {
    frame.onload = null
    frame.onerror = null
    m.route.set('/pages')
  }
  iframe.load('/' + fileName)
}
