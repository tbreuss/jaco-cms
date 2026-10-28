
const editorMessages = {}
let editorMessagesPromise = null

const editorLanguage = () => {
  const token = window.sessionStorage.getItem('AUTHORIZATION_TOKEN')
  if (!token) {
    return ''
  }
  try {
    const payload = token.split('.')[1]
    if (!payload) {
      return ''
    }
    const base64 = payload.replace(/-/g, '+').replace(/_/g, '/')
    const padded = base64.padEnd(Math.ceil(base64.length / 4) * 4, '=')
    const bytes = Uint8Array.from(atob(padded), (character) => character.charCodeAt(0))
    const claims = JSON.parse(new TextDecoder().decode(bytes))
    return typeof claims.language === 'string' ? claims.language : ''
  } catch {
    return ''
  }
}

const loadEditorMessages = () => {
  if (editorMessagesPromise) {
    return editorMessagesPromise
  }
  const token = window.sessionStorage.getItem('AUTHORIZATION_TOKEN')
  const language = editorLanguage()
  const url = '/jaco/api.php?action=translations' + (language ? '&language=' + encodeURIComponent(language) : '')
  editorMessagesPromise = fetch(url, {
    headers: token ? {Authorization: 'Bearer ' + token} : {},
  })
    .then((response) => response.ok ? response.json() : {})
    .then((data) => {
      Object.assign(editorMessages, data)
      return editorMessages
    })
    .catch(() => editorMessages)
  return editorMessagesPromise
}

const t = (key, fallback = null, placeholders = null) => {
  let text = editorMessages[key] ?? fallback ?? key
  if (placeholders) {
    text = text.replace(/\{(\w+)\}/g, (all, name) => placeholders[name] ?? all)
  }
  return text
}

window.jacoEditorT = t
const editorMessagesReady = loadEditorMessages()
window.jacoEditorReady = editorMessagesReady
const imageSizeKey = 'Image size: HTML {width}x{height}, Displayed {displayWidth}x{displayHeight}, Original {originalWidth}x{originalHeight}'
const imageSizeFallback = 'Image size: HTML {width}x{height}, Displayed {displayWidth}x{displayHeight}, Original {originalWidth}x{originalHeight}'

const imageSizeText = (image) => t(imageSizeKey, imageSizeFallback, {
  width: image.getAttribute('width') ?? '0',
  height: image.getAttribute('height') ?? '0',
  displayWidth: image.clientWidth,
  displayHeight: image.clientHeight,
  originalWidth: image.naturalWidth,
  originalHeight: image.naturalHeight,
})

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
  const selector = '[data-jaco-image]'
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
    container.innerText = imageSizeText(image)
    image.after(container)
    editorMessagesReady.then(() => {
      if (container.isConnected) {
        container.innerText = imageSizeText(image)
      }
    })
  })
}

// How an image treats its file: --mode, or replace when the property is absent
const imageModeOf = (image) => {
  const value = (image.getAttribute('data-jaco-image--mode') || '').toLowerCase()
  return ['fill', 'fit', 'choice'].includes(value) ? value : 'replace'
}

const imageScaleType = (image, chosen) => {
  const mode = imageModeOf(image)
  if (mode === 'fill' || mode === 'fit') {
    return mode
  }
  if (mode === 'choice') {
    return chosen === 'fit' ? 'fit' : 'fill'
  }
  return null
}

// What the toolbar offers: the value of <role>--tools, or plain when it is absent
const toolsOf = (element, allowed) => {
  const name = [...element.attributes]
    .map((attribute) => attribute.name)
    .find((attributeName) => attributeName.endsWith('--tools'))
  const value = name ? (element.getAttribute(name) || '').toLowerCase() : ''
  return allowed.includes(value) ? value : 'plain'
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


window.removeMediaPicker = () => {
  document.getElementById('editableMedia')?.remove()
}

// Pick an image that is already in the media library. The image dialog keeps
// its own "upload new file" path; this picker only assigns an existing file.
window.openMediaPicker = async (targetImage, onUpload = null) => {
  await editorMessagesReady
  window.removeMediaPicker()

  const overlay = document.createElement('div')
  overlay.id = 'editableMedia'
  overlay.setAttribute('role', 'dialog')
  overlay.setAttribute('aria-modal', 'true')
  overlay.setAttribute('aria-labelledby', 'editableMediaTitle')

  const dialog = document.createElement('div')
  dialog.className = 'jaco-media-dialog'

  const header = document.createElement('div')
  header.className = 'jaco-media-header'
  const title = document.createElement('h2')
  title.id = 'editableMediaTitle'
  title.textContent = t('Choose an image', 'Choose an image')
  const closeButton = document.createElement('button')
  closeButton.type = 'button'
  closeButton.textContent = '×'
  closeButton.setAttribute('aria-label', t('Close media picker', 'Close media picker'))
  header.append(title, closeButton)

  const actions = document.createElement('div')
  actions.className = 'jaco-media-actions'
  const status = document.createElement('p')
  status.className = 'jaco-media-status'
  status.textContent = t('Loading media...', 'Loading media...')
  actions.append(status)
  if (onUpload) {
    const uploadButton = document.createElement('button')
    uploadButton.type = 'button'
    uploadButton.textContent = t('Upload new image', 'Upload new image')
    uploadButton.onclick = () => {
      close()
      onUpload()
    }
    actions.append(uploadButton)
  }

  const grid = document.createElement('div')
  grid.className = 'jaco-media-grid'

  dialog.append(header, actions, grid)
  overlay.append(dialog)
  document.body.appendChild(overlay)
  closeButton.focus()

  const close = () => {
    overlay.remove()
    document.removeEventListener('keydown', onKeyDown)
  }
  const onKeyDown = (event) => {
    if (event.key === 'Escape') {
      close()
    }
  }
  document.addEventListener('keydown', onKeyDown)
  overlay.addEventListener('click', (event) => {
    if (event.target === overlay) {
      close()
    }
  })
  closeButton.addEventListener('click', close)

  const token = window.sessionStorage.getItem('AUTHORIZATION_TOKEN')
  fetch('/jaco/api.php?action=upload/list&filter=&sort=file', {
    headers: token ? {Authorization: 'Bearer ' + token} : {},
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error('Could not load media')
      }
      return response.json()
    })
    .then((data) => {
      const imageExtensions = ['png', 'gif', 'jpg', 'jpeg', 'svg', 'webp']
      const items = (data.items || []).filter((item) =>
        imageExtensions.includes(String(item.extension).toLowerCase()))
      status.textContent = items.length ? '' : t('No images in the media library.', 'No images in the media library.')
      items.forEach((item) => {
        const button = document.createElement('button')
        button.type = 'button'
        button.className = 'jaco-media-item'
        button.title = item.file
        const mediaSrc = '/' + String(item.file).replace(/^\/+/, '')
        const image = document.createElement('img')
        image.src = mediaSrc
        image.alt = ''
        image.loading = 'lazy'
        const label = document.createElement('span')
        label.textContent = item.file
        button.append(image, label)
        button.onclick = () => {
          targetImage.src = mediaSrc
          targetImage.removeAttribute('data-jaco-editable-image-file')
          close()
        }
        grid.append(button)
      })
    })
    .catch(() => {
      status.textContent = t('Could not load the media library.', 'Could not load the media library.')
    })
}


window.removeVideoDialog = () => {
  document.getElementById('editableVideo')?.remove()
}

const findVideoAtPoint = (event) => {
  const editor = event.target.closest('.ql-editor')
  if (!editor) {
    return null
  }
  return [...editor.querySelectorAll('iframe.ql-video')].find((frame) => {
    const rect = frame.getBoundingClientRect()
    return event.clientX >= rect.left && event.clientX <= rect.right
      && event.clientY >= rect.top && event.clientY <= rect.bottom
  }) || null
}

window.openVideoDialog = (frame) => {
  window.removeVideoDialog()

  const dialog = document.createElement('div')
  dialog.id = 'editableVideo'
  dialog.setAttribute('role', 'dialog')
  dialog.setAttribute('aria-label', t('Video URL', 'Video URL'))

  const label = document.createElement('label')
  label.innerText = t('Video URL', 'Video URL')

  const input = document.createElement('input')
  input.type = 'text'
  input.value = frame.getAttribute('src') || ''
  input.oninput = (event) => {
    frame.setAttribute('src', event.target.value)
  }

  const remove = document.createElement('button')
  remove.type = 'button'
  remove.innerText = t('Remove', 'Remove')
  remove.onclick = () => {
    frame.remove()
    dialog.remove()
  }

  const close = document.createElement('button')
  close.type = 'button'
  close.innerText = t('Close', 'Close')
  close.onclick = () => dialog.remove()

  dialog.append(label, input, remove, close)
  document.body.appendChild(dialog)
  input.focus()
}


const cssString = (value) => JSON.stringify(String(value))

const localizeQuillChrome = () => {
  let style = document.getElementById('jacoQuillTranslations')
  if (!style) {
    style = document.createElement('style')
    style.id = 'jacoQuillTranslations'
    document.head.appendChild(style)
  }
  style.textContent = `
.ql-snow .ql-tooltip::before { content: ${cssString(t('Visit URL:', 'Visit URL:'))}; }
.ql-snow .ql-tooltip[data-mode=link]::before { content: ${cssString(t('Enter link:', 'Enter link:'))}; }
.ql-snow .ql-tooltip[data-mode=video]::before { content: ${cssString(t('Enter video:', 'Enter video:'))}; }
.ql-snow .ql-tooltip a.ql-action::after { content: ${cssString(t('Edit', 'Edit'))}; }
.ql-snow .ql-tooltip.ql-editing a.ql-action::after { content: ${cssString(t('Save', 'Save'))}; }
.ql-snow .ql-tooltip a.ql-remove::before { content: ${cssString(t('Remove', 'Remove'))}; }
`

  document.querySelectorAll('.ql-picker.ql-header').forEach((picker) => {
    picker.querySelectorAll('.ql-picker-label, .ql-picker-item').forEach((item) => {
      const value = item.getAttribute('data-value')
      item.setAttribute('data-label',
        value ? t('Heading ' + value, 'Heading ' + value) : t('Normal', 'Normal'))
    })
  })
}

window.localizeQuillChrome = localizeQuillChrome

window.addClickHandler = () => {
  document.addEventListener('click', async (event) => {
    const video = findVideoAtPoint(event)
    if (video) {
      event.preventDefault()
      await editorMessagesReady
      window.openVideoDialog(video)
      return
    }

    if (event.target.closest('.ql-container, .ql-toolbar, [data-jaco-is-editing], #editableImageExtended, #editableLink, #editableMedia, #editableVideo, .jaco-content-add-button, .jaco-content-item-bar')) {
      //event.stopPropagation()
      return
    }

    if (event.target.closest('[data-jaco-image], [data-jaco-link]')) {
      await editorMessagesReady
    }
    removeRichTextEditorElements()
    removeExtendedImages()
    removeLinks()

    let editable = event.target.closest('[data-jaco-block]')
    if (editable) {
      editable.setAttribute('data-jaco-is-editing', '')
      // --tools decides what the toolbar offers; without it the block is plain
      const tools = toolsOf(editable, ['basic', 'full'])
      let toolbarOptions = tools === 'full'
        ? [
          [{'header': [1, 2, 3, 4, 5, 6, false]}],
          ['bold', 'italic', 'underline', 'strike'],
          ['blockquote', 'code-block'],
          [{'list': 'ordered'}, {'list': 'bullet'}],
          [{'indent': '-1'}, {'indent': '+1'}],
          ['link', 'video'],
          ['clean']
        ]
        : tools === 'basic'
          ? [['bold', 'italic', 'underline', 'strike'], ['link'], ['clean']]
          : []
      let editorOptions = {
        modules: {
          toolbar: toolbarOptions
        },
        theme: 'snow'
      }
      new Quill(editable, editorOptions)
      localizeQuillChrome()
      editorMessagesReady.then(localizeQuillChrome)
      handlePosition(editable)
      return
    }

    let editableLine = event.target.closest('[data-jaco-line]')
    if (editableLine) {
      editableLine.setAttribute('data-jaco-is-editing', '')
      const lineTools = toolsOf(editableLine, ['basic'])
      let toolbarLineOptions = lineTools === 'basic'
        ? [['bold', 'italic', 'underline', 'strike'], ['link'], ['clean']]
        : []
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
      localizeQuillChrome()
      editorMessagesReady.then(localizeQuillChrome)
      handlePosition(editableLine)
      return
    }

    // Every image opens the same dialog: alt text, a new file, the media library.
    // --mode decides which of those apply, not whether the dialog appears.
    let editableImageExtended = event.target.closest('[data-jaco-image]')
    if (editableImageExtended) {

      const [width, height] = detectImageSize(editableImageExtended)

      const divElement = document.createElement('div')
      divElement.id = 'editableImageExtended'

      // alt
      const label = document.createElement('label')
      label.innerText = t('Alt Text', 'Alt Text')
      divElement.appendChild(label)

      const inputAlt = document.createElement('input')
      inputAlt.type = 'text'
      inputAlt.value = editableImageExtended.getAttribute('alt')
      inputAlt.oninput = e => {
        editableImageExtended.setAttribute('alt', e.target.value)
      }
      divElement.appendChild(inputAlt)

      // the fit/fill switch exists for choice only: fill and fit decide on their
      // own, and an image without a mode is not scaled at all
      const choiceOffered = imageModeOf(editableImageExtended) === 'choice'
      if (width > 0 && height > 0 && choiceOffered) {
        // see https://www.bannerbear.com/help/articles/26-difference-between-fit-and-fill/
        const imageScalingLabel = document.createElement('label')
        imageScalingLabel.innerText = t('Image Scaling', 'Image Scaling') + ' '
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
        radioLabel1.innerText = t('Fit', 'Fit')
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
        radioLabel2.innerText = t('Fill', 'Fill')
        radioLabel2.htmlFor = 'radio2'
        divElement.appendChild(radioLabel2)
      }

      const button = document.createElement('button')
      button.innerText = t('Choose file', 'Choose file')
      button.onclick = () => {
        // within onclick handler because it doesn't work outside!
        const inputFile = document.createElement('input')
        inputFile.type = 'file'
        inputFile.onchange = e => {
          const file = e.target.files[0]
          const reader = new FileReader()
          reader.readAsDataURL(file)
          reader.onload = readerEvent => {
            const chosen = document.querySelector('input[name=type]:checked')?.value ?? null
            const scaleType = imageScaleType(editableImageExtended, chosen)
            if (scaleType === null) {
              editableImageExtended.src = readerEvent.target.result
            } else {
              resizeImage(readerEvent.target.result, file.type, width, height, scaleType, editableImageExtended)
            }
            editableImageExtended.setAttribute('data-jaco-editable-image-file', file.name)
          }
        }
        inputFile.click()
      }
      divElement.appendChild(button)

      const mediaButton = document.createElement('button')
      mediaButton.type = 'button'
      mediaButton.innerText = t('Choose from media', 'Choose from media')
      mediaButton.onclick = () => {
        // the picker is a library of files: it assigns an existing one, so the mode
        // does not apply to it - the file is used as it is
        window.removeExtendedImages()
        window.openMediaPicker(editableImageExtended)
      }
      divElement.appendChild(mediaButton)

      editableImageExtended.parentNode.insertBefore(divElement, editableImageExtended)

      // position element after inserting into DOM
      const rect = editableImageExtended.getBoundingClientRect()
      const top = rect.top +  window.scrollY - divElement.offsetHeight
      divElement.style.top = (top).toString() + 'px'
      divElement.style.minWidth = (rect.width - 2 * 8).toString() + 'px'
      return
    }

    const editableLink = event.target.closest('[data-jaco-link]')
    if (editableLink) {

      const divElement = document.createElement('div')
      divElement.id = 'editableLink'

      const labelHref = document.createElement('label')
      labelHref.innerText = t('URL', 'URL')
      divElement.appendChild(labelHref)

      const inputHref = document.createElement('input')
      inputHref.type = 'text'
      inputHref.value = editableLink.getAttribute('href')
      inputHref.oninput = (e) => {
        editableLink.setAttribute('href', e.target.value)
      }
      divElement.appendChild(inputHref)

      const labelTarget = document.createElement('label')
      labelTarget.innerText = t('Target', 'Target')
      divElement.appendChild(labelTarget)

      const selectTarget = document.createElement('select')

      const targetOptions = [
        ['', ''],
        ['_self', t('the current browsing context (self)')],
        ['_blank', t('a new tab or window (blank)')],
        ['_parent', t('the parent browsing context (parent)')],
        ['_top', t('the topmost browsing context (top)')],
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
  window.removeMediaPicker?.()
  window.removeVideoDialog?.()
  document.querySelectorAll('.ql-editor').forEach((element) => {
    element.parentElement.classList.remove('ql-container', 'ql-snow')
    // remove empty class attribute
    if (element.parentElement.classList.length === 0) {
      element.parentElement.removeAttribute('class')
    }
    element.parentElement.removeAttribute('data-jaco-is-editing')
    if (element.parentElement.hasAttribute('data-jaco-line')) {
      element.parentElement.innerHTML = removeBlockTags(element)
    } else if (element.parentElement.hasAttribute('data-jaco-block')) {
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
