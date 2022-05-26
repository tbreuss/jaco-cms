window.initEditors = () => {
  // add editor styles
  if (!document.getElementById('editorStylesheet')) {
    let cssLink = document.createElement('link')
    cssLink.href = '/jaco/frontend/asset/quill.snow-customized.css'
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
    script.src = 'https://cdn.quilljs.com/1.3.7/quill.js'
    script.async = false
    script.onload = () => { }
    document.head.appendChild(script)
  }

  // add editor instance styles
  if (!document.getElementById('editorInstanceStylesheet')) {
    let cssLink = document.createElement('link')
    cssLink.href = '/jaco/frontend/asset/editor.css'
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
    if (event.target.closest('.ql-container, .ql-toolbar, .jaco-is-editing')) {
      return
    }

    removeEditorInstances()

    let jacoEditable = event.target.closest('.jaco-editable')
    if (jacoEditable) {
      jacoEditable.classList.add('jaco-is-editing')
      let toolbarOptions = [
        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        ['blockquote', 'code-block'],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        [{ 'indent': '-1'}, { 'indent': '+1' }],
        ['link'],
        ['clean']
      ]
      let editorOptions = {
        modules: {
          toolbar: toolbarOptions
        },
        theme: 'snow'
      }
      new Quill(jacoEditable, editorOptions)
      return
    }

    let jacoEditableLine = event.target.closest('.jaco-editable-line')
    if (jacoEditableLine) {
      jacoEditableLine.classList.add('jaco-is-editing')
      let toolbarLineOptions = [
        ['bold', 'italic', 'underline', 'strike'],
        ['link'],
        ['clean']
      ]
      let editorLineOptions = {
        modules: {
          toolbar: toolbarLineOptions,
        },
        theme: 'snow'
      }
      new Quill(jacoEditableLine, {
        modules: {
          toolbar: toolbarLineOptions,
          keyboard: {
            bindings: {
              shift_enter: {
                key: 13,
                shiftKey: true,
                handler: () => { /* do nothing */ }
              },
              enter: {
                key: 13,
                handler: () => { /* do nothing */ }
              }
            }
          }
        },
        theme: 'snow'
      })
      return
    }
  })
}

window.removeEditorInstances = () => {
  document.querySelectorAll('.ql-editor').forEach((element) => {
    element.parentElement.classList.remove('ql-container', 'ql-snow', 'jaco-is-editing')
    if (element.parentElement.classList.contains('jaco-editable-line')) {
      element.parentElement.innerHTML = removeBlockTags(element)
    } else if (element.parentElement.classList.contains('jaco-editable')) {
      element.parentElement.innerHTML = element.innerHTML
    }
  })
  document.querySelectorAll('.ql-toolbar, .ql-clipboard, .ql-tooltip').forEach((element) => {
    element.remove()
  })
}



window.removeBlockTags = (element) => {
  let tags = 'address,article,aside,blockquote,canvas,dd,div,dl,dt,fieldset,figcaption,figure,footer,form,h1,h1,h2,h3,h4,h5,h6,header,hr,li,main,nav,noscript,ol,p,pre,section,table,tfoot,ul,video'
  let blocks = element.querySelectorAll(tags)
  let counter = blocks.length - 1;
  for(let i=counter; i >= 0; i--) {
    blocks[i].outerHTML = blocks[i].innerHTML;
  }
  return element.innerHTML
}

window.addInternalLinkHandler = () => {
  const originReg = new RegExp(location.hostname, 'i')
  document.querySelectorAll('a').forEach(a => {
    if (!originReg.test(a.href)) return // external link
    document.addEventListener('click', (event) => {
      event.preventDefault()
    })
  })
}

window.addClickHandler()
window.addInternalLinkHandler()
