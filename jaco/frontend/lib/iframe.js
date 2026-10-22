const PREVIEW_STORAGE_KEY = 'jaco-preview-page'

const isSafePreviewPath = (path) => {
  if (typeof path !== 'string' || path === '') {
    return false
  }
  if (path.includes('..') || path.includes('\\') || path.includes(':')) {
    return false
  }
  return path === 'index.html' || /^[\w./-]+\.html$/i.test(path)
}

const pathToPreviewUrl = (file) => (file === 'index.html' ? '/' : '/' + file)

const rememberPreviewPage = () => {
  const file = iframe.fileName()
  if (!isSafePreviewPath(file)) {
    return Promise.resolve()
  }
  return fetch(pathToPreviewUrl(file), {method: 'HEAD', credentials: 'same-origin'})
    .then((res) => {
      if (res.ok) {
        sessionStorage.setItem(PREVIEW_STORAGE_KEY, file)
        return
      }
      sessionStorage.removeItem(PREVIEW_STORAGE_KEY)
      if (file !== 'index.html') {
        const frame = iframe.getElement()
        frame.dataset.previewFallback = '1'
        frame.src = '/'
      }
    })
    .catch(() => {})
}

export const iframe = {
  pageTitle: () => {
    return iframe.contentDocument().title
  },
  fileName: () => {
    const contentDocument = iframe.contentDocument()
    const pathname = iframe.removeLeadingSlash(contentDocument.location.pathname)
    return pathname === '' ? 'index.html' : pathname
  },
  // onReady runs once the editor scripts are loaded and the editors are
  // initialized, on the already-loaded path as well as after the async script
  // load. Callers that read the content need it: getHTML() relies on the
  // remove* helpers the scripts install.
  makeEditable: (onReady) => {
    const contentDocument = iframe.contentDocument()
    const contentWindow = iframe.contentWindow()
    const initEditors = () => {
      contentWindow.initRichTextEditor()
      contentWindow.initImages()
      if (contentWindow.JacoContentLists) {
        contentWindow.JacoContentLists.initContentLists()
      }
      if (onReady) {
        onReady()
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
  // Remember which site page is open in the preview so a hard refresh of the
  // admin shell does not always reset the iframe to index.html.
  rememberPreviewPage,
  initPreview: () => {
    if (!iframe.hasElement()) {
      return
    }
    const frame = iframe.getElement()
    if (frame.dataset.previewPersistence) {
      return
    }
    frame.dataset.previewPersistence = '1'
    frame.addEventListener('load', () => {
      if (frame.dataset.previewFallback) {
        delete frame.dataset.previewFallback
        return
      }
      try {
        rememberPreviewPage()
      } catch {
        // preview not readable yet
      }
    })

    const saved = sessionStorage.getItem(PREVIEW_STORAGE_KEY)
    if (saved && isSafePreviewPath(saved)) {
      frame.src = pathToPreviewUrl(saved)
    }
  },
  reload: () => {
    iframe.contentWindow().location.reload()
  },
  // keepEditorScripts serializes without unhooking the editor. The remove*
  // helpers below tear down live editor chrome (and removeRichTextEditor()
  // drops the editor scripts entirely), which is what save() wants but not what
  // an observer may do: reading would disable editing mid-session. Callers that
  // only compare content pass true and keep the editors usable.
  getHTML: ({keepEditorScripts = false} = {}) => {
    const contentWindow = iframe.contentWindow()
    contentWindow.removeRichTextEditorElements()
    contentWindow.removeExtendedImages()
    contentWindow.removeLinks()
    if (!keepEditorScripts) {
      contentWindow.removeRichTextEditor()
    }
    contentWindow.removeTempImageElements()
    // keepEditorScripts is the dirty-check read: keep the content-list controls
    // mounted, otherwise the next init call would short-circuit on the flags.
    if (!keepEditorScripts && contentWindow.JacoContentLists) {
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
