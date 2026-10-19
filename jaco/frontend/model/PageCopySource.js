// The row action opens the copy dialog for a page that is not necessarily the
// one in the preview. Passing it through the route did not survive Mithril's
// query handling, so the selected row is handed over here instead.
let pending = null

export const PageCopySource = {
  set: (file, title, returnTo = '/') => {
    pending = {file, title, returnTo}
  },
  take: () => {
    const source = pending
    pending = null
    return source
  },
  clear: () => {
    pending = null
  },
}
