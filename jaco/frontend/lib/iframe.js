export const iframe = {
  pageTitle: () => {
    let iframe = document.getElementById('iframe')
    if (!iframe) {
      return ''
    }
    return iframe.contentDocument.title
  },
  fileName: () => {
    let el = document.getElementById('iframe')
    if (!el) {
      return ''
    }
    let pathname = iframe.removeLeadingSlash(el.contentDocument.location.pathname)
    return pathname === '' ? 'index.html' : pathname
  },
  makeEditable: () => {

    let iframe = document.getElementById('iframe')
    if (!iframe) {
      return
    }

    // add editor script
    if (!iframe.contentDocument.getElementById('editorInstanceJavascript')) {
      let script = iframe.contentDocument.createElement('script');
      script.id = 'editorInstanceJavascript';
      script.type = 'text/javascript';
      script.src = 'jaco/frontend/editor.js';
      script.async = false;
      script.onload = () => {
        iframe.contentWindow.initEditors()
      };
      iframe.contentDocument.head.appendChild(script);
    } else {
      iframe.contentWindow.initEditors()
    }
  },
  removeEditors: () => {
    let iframe = document.getElementById('iframe')
    if (!iframe) {
      return
    }
    if (typeof iframe.contentWindow.removeEditors === 'function') {
      iframe.contentWindow.removeEditors()
    }
  },
  disableEditors: () => {
    let iframe = document.getElementById('iframe')
    if (!iframe) {
      return
    }
    if (typeof iframe.contentWindow.disableEditors === 'function') {
      iframe.contentWindow.disableEditors()
    }
  },
  reload: () => {
    let iframe = document.getElementById('iframe')
    if (!iframe) {
      return
    }
    iframe.contentWindow.location.reload();
  },
  getHTML: () => {
    let iframe = document.getElementById('iframe')
    if (!iframe) {
      throw error
    }
    iframe.contentWindow.removeEditorInstances()
    iframe.contentWindow.removeEditors()
    return iframe.contentWindow.document.documentElement.outerHTML;
  },
  removeLeadingSlash: (str) => {
    return str.replace(/^\/+/, '')
  }
}
