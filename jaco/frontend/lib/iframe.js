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
  // Outside the inline editor the preview fills the viewport behind the fixed
  // admin panel: shift it below the navbar by exactly its height, so the site
  // never hides behind it. The height depends on wrapping and fonts, hence
  // measured, not hardcoded; cached writes avoid layout work on redraws.
  // In edit mode the flex column owns the layout, so any offset is cleared.
  fitBelowNavbar: () => {
    if (document.body.classList.contains('page-edit')) {
      iframe.resetFit()
      return
    }
    const navbar = document.querySelector('.navbar')
    const frame = document.getElementById('iframe')
    if (!navbar || !frame) {
      return
    }
    const offset = Math.round(navbar.getBoundingClientRect().bottom)
    if (frame.dataset.fitOffset !== String(offset)) {
      frame.dataset.fitOffset = String(offset)
      // relative positioning instead of a margin: margins collapse through
      // the body and reintroduce a window scrollbar
      frame.style.position = 'relative'
      frame.style.top = offset + 'px'
      frame.style.height = 'calc(100vh - ' + offset + 'px)'
    }
  },
  resetFit: () => {
    const frame = document.getElementById('iframe')
    if (!frame) {
      return
    }
    delete frame.dataset.fitOffset
    frame.style.position = ''
    frame.style.top = ''
    frame.style.height = ''
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
    const dataAttrs = ['data-jaco-editable-image', 'data-jaco-editable-image-gallery', 'data-jaco-editable-line', 'data-jaco-editable', 'data-jaco-content-list', 'data-jaco-content-item']
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
