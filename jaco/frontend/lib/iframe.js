export const iframe = {
  pageTitle: () => {
    return iframe.contentDocument().title
  },
  fileName: () => {
    const contentDocument = iframe.contentDocument()
    const pathname = iframe.removeLeadingSlash(contentDocument.location.pathname)
    return pathname === '' ? 'index.html' : pathname
  },
  makeEditable: () => {
    const contentDocument = iframe.contentDocument()
    const contentWindow = iframe.contentWindow()
    const initEditors = () => {
      contentWindow.initRichTextEditor()
      contentWindow.initImages()
      if (contentWindow.JacoContentLists) {
        contentWindow.JacoContentLists.initContentLists()
      }
    }
    if (contentDocument.getElementById('editorInstanceJavascript')) {
      initEditors()
    } else {
      const script = contentDocument.createElement('script')
      script.id = 'editorInstanceJavascript'
      script.type = 'text/javascript'
      script.src = '/jaco/frontend/editor.js'
      script.async = false
      script.onload = () => {
        const contentLists = contentDocument.createElement('script')
        contentLists.id = 'editorContentListsJavascript'
        contentLists.type = 'text/javascript'
        contentLists.src = '/jaco/frontend/contentlists.js'
        contentLists.async = false
        contentLists.onload = initEditors
        contentDocument.head.appendChild(contentLists)
      }
      contentDocument.head.appendChild(script)
    }
  },
  removeEditors: () => {
    const contentWindow = iframe.contentWindow()
    if (typeof contentWindow.removeRichTextEditor === 'function') {
      contentWindow.removeRichTextEditor()
      contentWindow.removeTempImageElements()
    }
  },
  disableEditors: () => {
    const contentWindow = iframe.contentWindow()
    if (typeof contentWindow.disableRichTextEditor === 'function') {
      contentWindow.disableRichTextEditor()
    }
  },
  load: (url) => {
    iframe.getElement().src = url
  },
  reload: () => {
    iframe.contentWindow().location.reload()
  },
  getHTML: () => {
    const contentWindow = iframe.contentWindow()
    contentWindow.removeRichTextEditorElements()
    contentWindow.removeExtendedImages()
    contentWindow.removeLinks()
    contentWindow.removeRichTextEditor()
    contentWindow.removeTempImageElements()
    if (contentWindow.JacoContentLists) {
      contentWindow.JacoContentLists.removeContentListChrome()
    }

    // the inline editor owns the content of the page, not the document around it
    let bodyHtml = contentWindow.document.body.innerHTML

    // use empty data attributes only (and remove the empty string part)
    const dataAttrs = ['data-jaco-editable-image', 'data-jaco-editable-line', 'data-jaco-editable', 'data-jaco-content-list', 'data-jaco-content-item']
    dataAttrs.forEach((attr) => {
      bodyHtml = bodyHtml.replaceAll(attr + '=""', attr)
    })

    return bodyHtml
  },
  removeLeadingSlash: (str) => {
    return str.replace(/^\/+/, '')
  },
  getElement: () => {
    const iframe = document.getElementById('iframe')
    if (!iframe) {
      throw 'Could not find iframe'
    }
    return iframe
  },
  hasElement: () => {
    return document.getElementById('iframe') !== null
  },
  contentDocument: () => {
    return iframe.getElement().contentDocument
  },
  contentWindow: () => {
    return iframe.getElement().contentWindow
  }
}
