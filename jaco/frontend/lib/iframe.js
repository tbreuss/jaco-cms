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
  load: (url) => {
    let iframe = document.getElementById('iframe')
    if (!iframe) {
      return
    }
    iframe.src = url;
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

    const doctype = iframe.contentWindow.document.doctype
    const outerHtml = iframe.contentWindow.document.documentElement.outerHTML

    let html = '<!DOCTYPE html>' // take html5 doctype as default
    if (doctype) {
      html = "<!DOCTYPE "
        + doctype.name
        + (doctype.publicId ? ' PUBLIC "' + doctype.publicId + '"' : '')
        + (!doctype.publicId && doctype.systemId ? ' SYSTEM' : '')
        + (doctype.systemId ? ' "' + doctype.systemId + '"' : '')
        + '>';
    }

    return html + "\r\n" + outerHtml
  },
  removeLeadingSlash: (str) => {
    return str.replace(/^\/+/, '')
  }
}
