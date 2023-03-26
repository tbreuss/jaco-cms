window.initRichTextEditor = () => {
  // add editor styles
  const customizedStylesheet = document.getElementById('editorStylesheet')
  if (customizedStylesheet) {
    customizedStylesheet.disabled = false
  } else {
    let newCustomizedStylesheet = document.createElement('link')
    newCustomizedStylesheet.href = '/jaco/frontend/asset/quill.snow-customized.css'
    newCustomizedStylesheet.id = 'editorStylesheet'
    newCustomizedStylesheet.rel = 'stylesheet'
    newCustomizedStylesheet.type = 'text/css'
    newCustomizedStylesheet.async = false
    newCustomizedStylesheet.onload = () => { }
    document.head.appendChild(newCustomizedStylesheet)
  }

  // add editor instance styles
  const editorStylesheet = document.getElementById('editorInstanceStylesheet')
  if (editorStylesheet) {
    editorStylesheet.disabled = false
  } else {
    let newEditorStylesheet = document.createElement('link')
    newEditorStylesheet.href = '/jaco/frontend/asset/editor.css'
    newEditorStylesheet.id = 'editorInstanceStylesheet'
    newEditorStylesheet.rel = 'stylesheet'
    newEditorStylesheet.type = 'text/css'
    newEditorStylesheet.async = false
    newEditorStylesheet.onload = () => { }
    document.head.appendChild(newEditorStylesheet)
  }

  // add editor script
  const editorJavascript = document.getElementById('editorJavascript')
  if (!editorJavascript) {
    let newEditorJavascript = document.createElement('script')
    newEditorJavascript.id = 'editorJavascript'
    newEditorJavascript.type = 'text/javascript'
    newEditorJavascript.src = 'https://cdn.quilljs.com/1.3.7/quill.js'
    newEditorJavascript.async = false
    newEditorJavascript.onload = () => { }
    document.head.appendChild(newEditorJavascript)
  }
}

window.initImages = () => {
  const selector = '[data-jaco-editable-image], [data-jaco-editable-image-extended]'
  document.querySelectorAll(selector).forEach((image) => {
    let container
    if (getDisplayType(image.parentNode) === 'inline') {
      container = document.createElement('span')
    } else {
      container = document.createElement('div')
    }
    container.setAttribute('data-jaco-editable-image-temp', '')
    container.innerText = 'Image size: Original ' + image.naturalWidth + 'x' + image.naturalHeight;
    container.innerText += ', Client ' + image.clientWidth + 'x' + image.clientHeight;
    image.after(container)
  })
}

window.getDisplayType = (element) => {
  let cStyle = element.currentStyle || window.getComputedStyle(element, "")
  return cStyle.display
}

window.removeRichTextEditor = () => {
  const selectors = [
    '#editorStylesheet',
    '#editorJavascript',
    '#editorInstanceStylesheet',
    '#editorInstanceJavascript',
  ]
  document.querySelectorAll(selectors.join(', ')).forEach((element) => {
    element.remove()
  })
}

window.removeTempImageElements = () => {
  const selectors = [
    '[data-jaco-editable-image-temp]'
  ]
  document.querySelectorAll(selectors.join(', ')).forEach((element) => {
    element.remove()
  })
}

window.disableRichTextEditor = () => {
  const selector = '#editorStylesheet, #editorJavascript, #editorInstanceStylesheet, #editorInstanceJavascript'
  document.querySelectorAll(selector).forEach((element) => {
    element.disabled = true
  })
}

window.addClickHandler = () => {
  document.addEventListener('click', (event) => {
    if (event.target.closest('.ql-container, .ql-toolbar, [data-jaco-is-editing], #editableImageExtended')) {
      return
    }

    removeRichTextEditorElements()
    removeExtendedImages()

    let editable = event.target.closest('[data-jaco-editable]')
    if (editable) {
      editable.setAttribute('data-jaco-is-editing', '')
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
      new Quill(editable, editorOptions)
      handlePosition(editable)
      return
    }

    let editableLine = event.target.closest('[data-jaco-editable-line]')
    if (editableLine) {
      editableLine.setAttribute('data-jaco-is-editing', '')
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
      new Quill(editableLine, {
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
      handlePosition(editableLine)
      return
    }

    let editableImage = event.target.closest('[data-jaco-editable-image]')
    if (editableImage) {

      let [width, height] = detectImageSize(editableImage)

      let input = document.createElement('input')
      input.type = 'file'
      input.onchange = e => {
        let file = e.target.files[0]
        let reader = new FileReader()
        reader.onload = readerEvent => {
          resizeImage(readerEvent.target.result, file.type, width, height, editableImage)
          editableImage.setAttribute('data-jaco-editable-image-file', file.name)
        }
        reader.readAsDataURL(file)
      }
      input.click()
    }

    let editableImageExtended = event.target.closest('[data-jaco-editable-image-extended]')
    if (editableImageExtended) {

      const rect = editableImageExtended.getBoundingClientRect()

      const divElement = document.createElement('div')
      divElement.id = 'editableImageExtended'
      divElement.style.top = (rect.top - 63).toString() + 'px'
      divElement.style.minWidth = (rect.width - 2 * 8).toString() + 'px'

      // alt
      const label = document.createElement('label')
      label.innerText = 'Alt'
      divElement.appendChild(label)

      const inputAlt = document.createElement('input')
      inputAlt.type = 'text'
      inputAlt.value = editableImageExtended.getAttribute('alt')
      inputAlt.oninput = e => {
        editableImageExtended.setAttribute('alt', e.target.value)
      }
      divElement.appendChild(inputAlt)

      const button = document.createElement('button')
      button.innerText = 'Choose file'
      button.onclick = e => {
        // within onclick handler because it doesn't work outside!
        const inputFile = document.createElement('input')
        inputFile.type = 'file'
        inputFile.onchange = e => {
          const file = e.target.files[0]
          const reader = new FileReader()
          reader.readAsDataURL(file)
          reader.onload = readerEvent => {
            const content = readerEvent.target.result
            editableImageExtended.src = content
            editableImageExtended.setAttribute('data-jaco-editable-image-file', file.name)
          }
        }
        inputFile.click()
      }
      divElement.appendChild(button)

      editableImageExtended.parentNode.insertBefore(divElement, editableImageExtended)
    }
  })
}

window.drawImageScaled = (img, ctx) => {
  var canvas = ctx.canvas ;
  var hRatio = canvas.width  / img.width    ;
  var vRatio =  canvas.height / img.height  ;
  var ratio  = Math.max ( hRatio, vRatio );
  var centerShift_x = ( canvas.width - img.width*ratio ) / 2;
  var centerShift_y = ( canvas.height - img.height*ratio ) / 2;
  ctx.clearRect(0,0,canvas.width, canvas.height);
  ctx.drawImage(img, 0,0, img.width, img.height,
    centerShift_x,centerShift_y,img.width*ratio, img.height*ratio);
}

window.resizeImage = (dataUrl, fileType, width, height, editableImage) => {
  if (width === 0 && height === 0) {
    // no restriction on size
    editableImage.src = dataUrl
  } else {
    const image = new Image();
    image.onload = e => {
      const canvas = document.createElement("canvas")
      canvas.width = width
      canvas.height = height
      const ctx = canvas.getContext("2d")

      window.drawImageScaled(image, ctx)

      const data = canvas.toDataURL(fileType, 1.0)
      editableImage.src = data
    }
    image.src = dataUrl
  }
}

window.detectImageSize = (image) => {
  const width = image.getAttribute('width') ?? 0
  const height = image.getAttribute('height') ?? 0

  if (width === 0 && height === 0) {
    return [0, 0]
  }

  const ratio = image.naturalWidth / image.naturalHeight

  if (width === 0) {
    return [Math.round(height * ratio), height]
  }

  if  (height === 0) {
    return [width, Math.round(width / ratio)]
  }

  return [width, height]
}

window.handlePosition = (element) => {
  const rect = element.getBoundingClientRect()
  const toolbar = element.parentElement.querySelectorAll('.ql-toolbar')[0]
  const toolbarHeight = toolbar.offsetHeight // or clientHeight?
  const scrollTop = document.body.scrollTop || document.documentElement.scrollTop
  toolbar.style.top = (rect.top - toolbarHeight + scrollTop - 1) + 'px'
  toolbar.style.left = rect.left + 'px'
  toolbar.style.width = element.offsetWidth + 'px'
}

window.removeExtendedImages = () => {
  document.querySelectorAll('#editableImageExtended').forEach((element) => {
    element.remove()
  })
}

window.removeRichTextEditorElements = () => {
  document.querySelectorAll('.ql-editor').forEach((element) => {
    element.parentElement.classList.remove('ql-container', 'ql-snow')
    // remove empty class attribute
    if (element.parentElement.classList.length === 0) {
      element.parentElement.removeAttribute('class')
    }
    element.parentElement.removeAttribute('data-jaco-is-editing')
    if (element.parentElement.hasAttribute('data-jaco-editable-line')) {
      element.parentElement.innerHTML = removeBlockTags(element)
    } else if (element.parentElement.hasAttribute('data-jaco-editable')) {
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
  let counter = blocks.length - 1
  for(let i=counter; i >= 0; i--) {
    blocks[i].outerHTML = blocks[i].innerHTML
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

window.onresize = () => {
  const editingElement = document.querySelector('[data-jaco-is-editing]')
  if (editingElement) {
    handlePosition(editingElement)
  }
  removeTempImageElements()
  initImages()
}

window.addClickHandler()
window.addInternalLinkHandler()
