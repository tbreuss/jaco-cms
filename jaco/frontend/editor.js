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
    newCustomizedStylesheet.onload = () => {
    }
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
    newEditorStylesheet.onload = () => {
    }
    document.head.appendChild(newEditorStylesheet)
  }

  // add editor script
  const editorJavascript = document.getElementById('editorJavascript')
  if (!editorJavascript) {
    let newEditorJavascript = document.createElement('script')
    newEditorJavascript.id = 'editorJavascript'
    newEditorJavascript.type = 'text/javascript'
    // asset version: quill 1.3.7
    newEditorJavascript.src = '/jaco/frontend/asset/quill.js'
    newEditorJavascript.async = false
    newEditorJavascript.onload = () => {
    }
    document.head.appendChild(newEditorJavascript)
  }
}

window.initImages = (root) => {
  const selector = '[data-jaco-editable-image], [data-jaco-editable-image-extended]'
  ;(root || document).querySelectorAll(selector).forEach((image) => {
    if (image.nextElementSibling && image.nextElementSibling.hasAttribute('data-jaco-editable-image-temp')) {
      return // already initialized
    }
    let container
    if (getDisplayType(image.parentNode) === 'inline') {
      container = document.createElement('span')
    } else {
      container = document.createElement('div')
    }
    container.setAttribute('data-jaco-editable-image-temp', '')
    container.innerText = 'Image size: '
      + 'HTML '
      + (image.getAttribute('width') ?? '0')
      + 'x'
      + (image.getAttribute('height') ?? '0')
      + ', Displayed '
      + image.clientWidth
      + 'x'
      + image.clientHeight
      + ', Original '
      + image.naturalWidth
      + 'x'
      + image.naturalHeight
    image.after(container)
  })
}

window.getDisplayType = (element) => {
  let cStyle = element.currentStyle || window.getComputedStyle(element, '')
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
    if (event.target.closest('.ql-container, .ql-toolbar, [data-jaco-is-editing], #editableImageExtended, #editableLink, .jaco-content-add-button, .jaco-content-item-bar')) {
      //event.stopPropagation()
      return
    }

    removeRichTextEditorElements()
    removeExtendedImages()
    removeLinks()

    let editable = event.target.closest('[data-jaco-editable]')
    if (editable) {
      editable.setAttribute('data-jaco-is-editing', '')
      let toolbarOptions = [
        [{'header': [1, 2, 3, 4, 5, 6, false]}],
        ['bold', 'italic', 'underline', 'strike'],
        ['blockquote', 'code-block'],
        [{'list': 'ordered'}, {'list': 'bullet'}],
        [{'indent': '-1'}, {'indent': '+1'}],
        ['link', 'video'],
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
                handler: () => { /* do nothing */
                }
              },
              enter: {
                key: 13,
                handler: () => { /* do nothing */
                }
              }
            }
          }
        },
        theme: 'snow'
      })
      handlePosition(editableLine)
      return
    }

    const editableImage = event.target.closest('[data-jaco-editable-image]')
    if (editableImage) {

      editableImage.onload = () => {
        console.debug('image loaded')
        window.resize()
      }

      const input = document.createElement('input')
      input.type = 'file'
      input.onchange = e => {
        const [width, height] = detectImageSize(editableImage)
        const file = e.target.files[0]
        const reader = new FileReader()
        reader.onload = readerEvent => {
          resizeImage(readerEvent.target.result, file.type, width, height, 'fill', editableImage)
          editableImage.setAttribute('data-jaco-editable-image-file', file.name)
          if (editableImage.hasAttribute('data-jaco-editable-image-gallery')) {
            scaleDataUrlToWidth(readerEvent.target.result, file.type, GALLERY_FULL_MAX_WIDTH, (fullDataUrl) => {
              editableImage.setAttribute('data-jaco-gallery-full', fullDataUrl)
            })
          }
        }
        reader.onloadend = () => {
        }
        reader.readAsDataURL(file)
      }
      input.click()
      return
    }

    let editableImageExtended = event.target.closest('[data-jaco-editable-image-extended]')
    if (editableImageExtended) {

      const [width, height] = detectImageSize(editableImageExtended)

      const divElement = document.createElement('div')
      divElement.id = 'editableImageExtended'

      // alt
      const label = document.createElement('label')
      label.innerText = 'Alt Text'
      divElement.appendChild(label)

      const inputAlt = document.createElement('input')
      inputAlt.type = 'text'
      inputAlt.value = editableImageExtended.getAttribute('alt')
      inputAlt.oninput = e => {
        editableImageExtended.setAttribute('alt', e.target.value)
      }
      divElement.appendChild(inputAlt)

      if (width > 0 && height > 0) {
        // see https://www.bannerbear.com/help/articles/26-difference-between-fit-and-fill/
        const imageScalingLabel = document.createElement('label')
        imageScalingLabel.innerText = 'Image Scaling '
        divElement.appendChild(imageScalingLabel)

        const radio1 = document.createElement('input')
        radio1.type = 'radio'
        radio1.id = 'radio1'
        radio1.name = 'type'
        radio1.value = 'fit'
        radio1.onclick = e => {
          e.stopPropagation()
          document.getElementById('radio2').checked = false
          e.target.checked = true
        }
        divElement.appendChild(radio1)

        const radioLabel1 = document.createElement('label')
        radioLabel1.innerText = 'Fit'
        radioLabel1.htmlFor = 'radio1'
        divElement.appendChild(radioLabel1)

        const radio2 = document.createElement('input')
        radio2.type = 'radio'
        radio2.id = 'radio2'
        radio2.name = 'type'
        radio2.value = 'fill'
        radio2.checked = true
        radio2.onclick = e => {
          e.stopPropagation()
          document.getElementById('radio1').checked = false
          e.target.checked = true
        }
        divElement.appendChild(radio2)

        const radioLabel2 = document.createElement('label')
        radioLabel2.innerText = 'Fill'
        radioLabel2.htmlFor = 'radio2'
        divElement.appendChild(radioLabel2)
      }

      const button = document.createElement('button')
      button.innerText = 'Choose file'
      button.onclick = () => {
        // within onclick handler because it doesn't work outside!
        const inputFile = document.createElement('input')
        inputFile.type = 'file'
        inputFile.onchange = e => {
          const file = e.target.files[0]
          const reader = new FileReader()
          reader.readAsDataURL(file)
          reader.onload = readerEvent => {
            let scaleType = 'fit'
            if (width > 0 && height > 0) {
              scaleType = document.querySelector('input[name=type]:checked').value
            }
            resizeImage(readerEvent.target.result, file.type, width, height, scaleType, editableImageExtended)
            editableImageExtended.setAttribute('data-jaco-editable-image-file', file.name)
          }
        }
        inputFile.click()
      }
      divElement.appendChild(button)

      editableImageExtended.parentNode.insertBefore(divElement, editableImageExtended)

      // position element after inserting into DOM
      const rect = editableImageExtended.getBoundingClientRect()
      const top = rect.top +  window.scrollY - divElement.offsetHeight
      divElement.style.top = (top).toString() + 'px'
      divElement.style.minWidth = (rect.width - 2 * 8).toString() + 'px'
      return
    }

    const editableLink = event.target.closest('[data-jaco-editable-link]')
    if (editableLink) {

      const divElement = document.createElement('div')
      divElement.id = 'editableLink'

      const labelHref = document.createElement('label')
      labelHref.innerText = 'URL'
      divElement.appendChild(labelHref)

      const inputHref = document.createElement('input')
      inputHref.type = 'text'
      inputHref.value = editableLink.getAttribute('href')
      inputHref.oninput = (e) => {
        editableLink.setAttribute('href', e.target.value)
      }
      divElement.appendChild(inputHref)

      const labelTarget = document.createElement('label')
      labelTarget.innerText = 'Target'
      divElement.appendChild(labelTarget)

      const selectTarget = document.createElement('select')

      const targetOptions = [
        ['', ''],
        ['_self', 'the current browsing context (self)'],
        ['_blank', 'a new tab or window (blank)'],
        ['_parent', 'the parent browsing context (parent)'],
        ['_top', 'the topmost browsing context (top)'],
      ]

      targetOptions.forEach(([value, label]) => {
        const option = document.createElement('option')
        option.text = label
        option.value = value
        selectTarget.add(option)
      })

      selectTarget.value = editableLink.getAttribute('target') ?? ''

      selectTarget.onchange = (e) => {
        if (e.target.value === '') {
          editableLink.removeAttribute('target')
        } else {
          editableLink.setAttribute('target', e.target.value)
        }
      }

      divElement.appendChild(selectTarget)

      editableLink.parentNode.insertBefore(divElement, editableLink)

      // position element after inserting into DOM
      const rect = editableLink.getBoundingClientRect()
      const top = rect.top +  window.scrollY - divElement.offsetHeight
      divElement.style.top = (top).toString() + 'px'
      divElement.style.minWidth = (rect.width - 2 * 8).toString() + 'px'
      return
    }

  })
}

window.drawImageScaled = (img, ctx, scaleType) => {
  var canvas = ctx.canvas
  var hRatio = canvas.width / img.width
  var vRatio = canvas.height / img.height
  if (scaleType === 'fit') {
    var ratio = Math.min(hRatio, vRatio) // fit
  } else {
    var ratio = Math.max(hRatio, vRatio) // fill
  }
  var centerShift_x = (canvas.width - img.width * ratio) / 2
  var centerShift_y = (canvas.height - img.height * ratio) / 2
  ctx.clearRect(0, 0, canvas.width, canvas.height)
  ctx.drawImage(img, 0, 0, img.width, img.height,
    centerShift_x, centerShift_y, img.width * ratio, img.height * ratio)
}

window.resizeImage = (dataUrl, fileType, width, height, scaleType, editableImage) => {
  if (width === 0 && height === 0) {
    // no restriction on size
    editableImage.src = dataUrl
  } else {
    const image = new Image()
    image.onload = () => {
      const canvas = document.createElement('canvas')
      if (width > 0 && height > 0) {
        canvas.width = width
        canvas.height = height
      } else if (width > 0) {
        canvas.width = width
        canvas.height = image.height / image.width * width
      } else {
        canvas.width = height * image.width / image.height
        canvas.height = height
      }
      const ctx = canvas.getContext('2d')
      window.drawImageScaled(image, ctx, scaleType)
      editableImage.src = canvas.toDataURL(fileType, 1.0)
    }
    image.src = dataUrl
  }
}

// full version for gallery images, downscaled to a sane lightbox width
const GALLERY_FULL_MAX_WIDTH = 1920

window.scaleDataUrlToWidth = (dataUrl, fileType, maxWidth, callback) => {
  const image = new Image()
  image.onload = () => {
    let width = image.width
    let height = image.height
    if (width > maxWidth) {
      height = Math.round(height * maxWidth / width)
      width = maxWidth
    }
    const canvas = document.createElement('canvas')
    canvas.width = width
    canvas.height = height
    canvas.getContext('2d').drawImage(image, 0, 0, width, height)
    callback(canvas.toDataURL(fileType, 0.92))
  }
  image.src = dataUrl
}

window.detectImageSize = (image) => {
  const width = parseInt(image.getAttribute('width') ?? '0')
  const height = parseInt(image.getAttribute('height') ?? '0')
  return [width, height]

  /*
  if (width === 0 && height === 0) {
    return [0, 0]
  }

  const ratio = image.naturalWidth / image.naturalHeight

  if (width === 0) {
    return [Math.round(height * ratio), height]
  }

  if (height === 0) {
    const h = width / ratio
    return [width, Math.round(h)]
  }

  return [width, height]
  */
}

window.handlePosition = (element) => {
  const toolbar = element.parentElement.querySelectorAll('.ql-toolbar')[0]
  // Take the toolbar out of flow before measuring the editor. If the Quill
  // stylesheet is still loading, the toolbar would otherwise occupy space above
  // the block and the content would jump when the stylesheet lands.
  toolbar.style.position = 'absolute'
  const rect = element.getBoundingClientRect()
  // Let the toolbar use its natural one-line width instead of the card width.
  // It may extend over neighbouring cards, but the edited card itself keeps its
  // size; on small screens it is clamped to the viewport.
  toolbar.style.width = 'max-content'
  const viewportWidth = document.documentElement.clientWidth
  const margin = 8
  const toolbarWidth = Math.min(toolbar.offsetWidth, viewportWidth - 2 * margin)
  const left = Math.max(margin, Math.min(rect.left, viewportWidth - toolbarWidth - margin))
  const toolbarHeight = toolbar.offsetHeight // or clientHeight?
  const scrollTop = document.body.scrollTop || document.documentElement.scrollTop
  toolbar.style.top = Math.max(margin, rect.top - toolbarHeight + scrollTop - 1) + 'px'
  toolbar.style.left = left + 'px'
  toolbar.style.width = toolbarWidth + 'px'
}

window.removeExtendedImages = () => {
  document.querySelectorAll('#editableImageExtended').forEach((element) => {
    element.remove()
  })
}

window.removeLinks = () => {
  document.querySelectorAll('#editableLink').forEach((element) => {
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
      removeBoundaryEmptyParagraphs(element)
      normalizeVideoFrames(element)
      element.parentElement.innerHTML = element.innerHTML
    }
  })
  document.querySelectorAll('.ql-toolbar, .ql-clipboard, .ql-tooltip').forEach((element) => {
    element.remove()
  })
}

// Quill drops the iframe dimensions when it turns a video into a ql-video
// blot. Restore a sane box before the body is written to disk.
window.normalizeVideoFrames = (element) => {
  element.querySelectorAll('iframe.ql-video').forEach((frame) => {
    if (!frame.getAttribute('width')) {
      frame.setAttribute('width', '560')
    }
    if (!frame.getAttribute('height')) {
      frame.setAttribute('height', '315')
    }
  })
}

// Quill uses empty paragraphs as caret helpers around lists and videos. They
// are editing chrome, not content: do not persist them, and do not leave the
// saved page taller than the source page.
window.removeBoundaryEmptyParagraphs = (element) => {
  const block = (node) => node && ['UL', 'OL', 'IFRAME'].includes(node.tagName)
  element.querySelectorAll('p').forEach((paragraph) => {
    const onlyBreaks = [...paragraph.childNodes].every((node) =>
      node.nodeType === Node.TEXT_NODE
        ? node.textContent.trim() === ''
        : node.nodeName === 'BR')
    if (onlyBreaks && (block(paragraph.previousElementSibling) || block(paragraph.nextElementSibling))) {
      paragraph.remove()
    }
  })
}

window.removeBlockTags = (element) => {
  let tags = 'address,article,aside,blockquote,canvas,dd,div,dl,dt,fieldset,figcaption,figure,footer,form,h1,h1,h2,h3,h4,h5,h6,header,hr,li,main,nav,noscript,ol,p,pre,section,table,tfoot,ul,video'
  let blocks = element.querySelectorAll(tags)
  let counter = blocks.length - 1
  for (let i = counter; i >= 0; i--) {
    blocks[i].outerHTML = blocks[i].innerHTML
  }
  return element.innerHTML
}

window.addLinkHandler = () => {
  document.addEventListener('click', (event) => {
    // prevent navigation to internal and external links
    // to keep unsaved changes in the editor
    if (event.target.closest('a')) {
      event.preventDefault()
    }
  })
}

window.onresize = () => {
  const editingElement = document.querySelector('[data-jaco-is-editing]')
  if (editingElement) {
    handlePosition(editingElement)
  }
  removeTempImageElements()
  initImages()
  console.debug('window resized')
}

window.resize = () => {
  window.dispatchEvent(new Event('resize'));
}

window.addClickHandler()
window.addLinkHandler()
