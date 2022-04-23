window.initEditors = () => {
  // add editor styles
  if (!document.getElementById('editorStylesheet')) {
    let cssLink = document.createElement('link')
    cssLink.href = './jaco/frontend/quill.snow-customized.css'
    cssLink.id = 'editorStylesheet'
    cssLink.rel = 'stylesheet'
    cssLink.type = 'text/css'
    cssLink.async = false
    cssLink.onload = () => { }
    document.head.appendChild(cssLink)
  } else {
    document.getElementById('editorStylesheet').disabled = false
  }

  // add editor script
  if (!document.getElementById('editorJavascript')) {
    let script = document.createElement('script')
    script.id = 'editorJavascript'
    script.type = 'text/javascript'
    script.src = 'https://cdn.quilljs.com/1.3.6/quill.js'
    script.async = false
    script.onload = () => { }
    document.head.appendChild(script)
  }

  // add editor instance styles
  if (!document.getElementById('editorInstanceStylesheet')) {
    let cssLink = document.createElement('link')
    cssLink.href = 'jaco/frontend/editor.css'
    cssLink.id = 'editorInstanceStylesheet'
    cssLink.rel = 'stylesheet'
    cssLink.type = 'text/css'
    cssLink.async = false
    cssLink.onload = () => { }
    document.head.appendChild(cssLink)
  } else {
    document.getElementById('editorInstanceStylesheet').disabled = false
  }
}

window.removeEditors = () => {
  const selector = '#editorStylesheet, #editorJavascript, #editorInstanceStylesheet, #editorInstanceJavascript'
  document.querySelectorAll(selector).forEach((element) => {
    element.remove()
  })
}

window.disableEditors = () => {
  const selector = '#editorStylesheet, #editorJavascript, #editorInstanceStylesheet, #editorInstanceJavascript'
  document.querySelectorAll(selector).forEach((element) => {
    element.disabled = true
  })
}

window.addClickHandler = () => {
  document.addEventListener('click', (event) => {
    if (event.target.closest('.ql-container, .ql-toolbar, .jaco-is-editing')) return

    removeEditorInstances()

    let editorOptions = {
      modules: {
        toolbar: true
      },
      theme: 'snow'
    }

    let jacoEditable = event.target.closest('.jaco-editable')
    if (jacoEditable) {
      jacoEditable.classList.add('jaco-is-editing')
      new Quill(jacoEditable, editorOptions)
      return
    }
  })
}

window.removeEditorInstances = () => {
  document.querySelectorAll('.ql-editor').forEach((element) => {
    element.parentElement.classList.remove('ql-container', 'ql-snow', 'jaco-is-editing')
    element.parentElement.innerHTML = element.innerHTML
  })
  document.querySelectorAll('.ql-toolbar, .ql-clipboard, .ql-tooltip').forEach((element) => {
    element.remove()
  })
}

window.addInternalLinkHandler = () => {
  const originReg = new RegExp(location.hostname, 'i');
  document.querySelectorAll('a').forEach(a => {
    if (!originReg.test(a.href)) return // external link
    document.addEventListener('click', (event) => {
      event.preventDefault()
    });
  });
}

window.addClickHandler()
window.addInternalLinkHandler()
