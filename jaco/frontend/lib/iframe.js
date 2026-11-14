const PREVIEW_STORAGE_KEY = 'jaco-preview-page'

// How long the editor scripts in the page may take before loading counts as failed
const EDITOR_LOAD_TIMEOUT_MS = 10000

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
  // Loads and initializes the editors in the page. The promise settles when the
  // editor is usable: editor.js and contentlists.js are loaded, Quill is there,
  // the editors are initialized and the editor texts are loaded. It rejects when
  // a script fails to load or nothing happens within EDITOR_LOAD_TIMEOUT_MS, so
  // a page that stays uneditable says so instead of failing silently.
  // onReady runs just before it resolves. Callers that read the content need it:
  // getHTML() relies on serializeBody() from editor.js.
  makeEditable: (onReady) => new Promise((resolve, reject) => {
    const contentDocument = iframe.contentDocument()
    const contentWindow = iframe.contentWindow()
    let timedOut = false
    const timer = setTimeout(() => {
      timedOut = true
      reject(new Error('The editor did not load in time'))
    }, EDITOR_LOAD_TIMEOUT_MS)
    const loadScript = (id, src) => new Promise((done, fail) => {
      const script = contentDocument.createElement('script')
      script.id = id
      script.type = 'text/javascript'
      script.src = src
      script.async = false
      script.onload = () => done()
      script.onerror = () => fail(new Error('Could not load ' + src))
      contentDocument.head.appendChild(script)
    })
    const initEditors = () => {
      const quillLoaded = contentWindow.initRichTextEditor()
      contentWindow.initImages()
      if (contentWindow.JacoContentLists) {
        contentWindow.JacoContentLists.initContentLists()
      }
      return Promise.all([quillLoaded, contentWindow.jacoEditorReady])
    }
    const scriptsLoaded = contentDocument.getElementById('editorInstanceJavascript')
      ? Promise.resolve()
      : loadScript('editorInstanceJavascript', '/jaco/frontend/editor.js')
        .then(() => loadScript('editorContentListsJavascript', '/jaco/frontend/contentlists.js'))
    scriptsLoaded
      .then(initEditors)
      .then(() => {
        // too late: the caller was told it failed, do not act as if it had worked
        if (timedOut) {
          return
        }
        if (onReady) {
          onReady()
        }
        resolve()
      })
      .catch(reject)
      .finally(() => clearTimeout(timer))
  }),
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
  // marks the page document (not the body, so it never reaches the saved HTML)
  setShowEditable: (on) => {
    iframe.contentDocument().documentElement.classList.toggle('jaco-show-editable', on)
  },
  reload: () => {
    iframe.contentWindow().location.reload()
  },
  // Read-only: the page is serialized from a copy (window.serializeBody in
  // editor.js), so the editors stay usable, whether the caller compares the
  // content or saves it. A save reloads the page afterwards anyway.
  getHTML: () => {
    const contentWindow = iframe.contentWindow()
    if (typeof contentWindow.serializeBody !== 'function') {
      throw new Error('The editor is not loaded, the page content cannot be read')
    }
    // the inline editor owns the content of the page, not the document around it
    let bodyHtml = contentWindow.serializeBody()

    // The browser writes every empty attribute as x="". A data-jaco marker is an
    // empty attribute on purpose, so its ="" goes; every other attribute keeps its
    // value, empty or not. One rule instead of a list of names that the contract
    // would have to be copied into.
    bodyHtml = bodyHtml.replace(/\s(data-jaco-[a-z-]+)=""/g, ' $1')

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
