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

    if (contentDocument.getElementById('editorInstanceJavascript')) {
      contentWindow.initEditors()
      contentWindow.initImages()
    } else {
      const script = contentDocument.createElement('script')
      script.id = 'editorInstanceJavascript'
      script.type = 'text/javascript'
      script.src = '/jaco/frontend/editor.js'
      script.async = false
      script.onload = () => {
        contentWindow.initRichTextEditor()
        contentWindow.initImages()
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
    contentWindow.removeRichTextEditor()
    contentWindow.removeTempImageElements()

    const doctype = contentWindow.document.doctype
    let outerHtml = contentWindow.document.documentElement.outerHTML

    let html = '<!DOCTYPE html>' // take html5 doctype as default
    if (doctype) {
      html = "<!DOCTYPE "
        + doctype.name
        + (doctype.publicId ? ' PUBLIC "' + doctype.publicId + '"' : '')
        + (!doctype.publicId && doctype.systemId ? ' SYSTEM' : '')
        + (doctype.systemId ? ' "' + doctype.systemId + '"' : '')
        + '>'
    }

    // use empty data attributes only (and remove the empty string part)
    const dataAttrs = ['data-jaco-editable-image', 'data-jaco-editable-line', 'data-jaco-editable']
    dataAttrs.forEach((attr) => {
      outerHtml = outerHtml.replaceAll(attr + '=""', attr)
    })

    return html + "\r\n" + outerHtml
  },
  removeLeadingSlash: (str) => {
    return str.replace(/^\/+/, '')
  },
  getElement: () => {
    const iframe = document.getElementById('iframe')
    if (!iframe) {
      throw "Could not find iframe"
    }
    return iframe
  },
  contentDocument: () => {
    return iframe.getElement().contentDocument
  },
  contentWindow: () => {
    return iframe.getElement().contentWindow
  }
}
