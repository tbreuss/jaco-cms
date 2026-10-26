// Recurring content blocks for the inline editor ("content lists").
// Runs inside the page iframe: no build step, no dependencies, vanilla DOM.
// Defines only functions - loading this file has no side effects, so the
// QUnit harness can load it too.
//
// Authoring:
//
//   <div data-jaco-repeater data-jaco-repeater--fragment="news.html"
//        data-jaco-repeater--label="Add news" data-jaco-repeater--add="top">
//     <div data-jaco-repeater-item>…existing block…</div>
//   </div>
//
// data-jaco-repeater--add is "top", "bottom" or "any" (anything else behaves like
// "any"); data-jaco-repeater--sortable adds the grip. The button label comes from
// data-jaco-repeater--label, so no translations are needed.
// Fragments are fetched from the template/content/view API, which serves files
// from jaco-data/templates/content. Item order lives in the DOM - save needs no
// changes, but editor chrome has to be stripped (removeContentListChrome)
// before the body is sent, see iframe.getHTML().
window.JacoContentLists = (() => {
  const LIST_ATTR = 'data-jaco-repeater'
  const ITEM_ATTR = 'data-jaco-repeater-item'
  const ADD_BUTTON_CLASS = 'jaco-content-add-button'
  const OVERLAY_CLASS = 'jaco-content-item-bar'
  const GRIP_CLASS = 'jaco-content-grip'
  const DRAGGING_CLASS = 'is-dragging'

  const posOf = (list) => {
    const ADD = 'data-jaco-repeater--add'
    const SORTABLE = 'data-jaco-repeater--sortable'
    const add = (list.getAttribute(ADD) || '').toLowerCase()
    const sortable = list.hasAttribute(SORTABLE)
    // "draggable" was a position and a capability at once; it is now add="top" plus
    // the sortable property, and the old value stays readable while the pages migrate
    const pos = add === 'draggable' ? 'draggable' : add
    return pos === 'top' || pos === 'bottom' || pos === 'draggable' ? pos : 'any'
  }

  const fetchFragment = (file) => {
    const token = window.sessionStorage.getItem('AUTHORIZATION_TOKEN')
    const headers = token ? {Authorization: 'Bearer ' + token} : {}
    return fetch('/jaco/api.php?action=template/content/view&file=' + encodeURIComponent(file), {headers})
      .then((response) => {
        if (!response.ok) {
          throw new Error('Could not load fragment: ' + file)
        }
        return response.json()
      })
      .then((data) => data.content)
  }

  const createAddButton = (list, onAdd) => {
    const button = document.createElement('div')
    button.className = ADD_BUTTON_CLASS
    const title = list.getAttribute('data-jaco-repeater--label') || ''
    const setLabel = () => {
      button.textContent = '+ ' + (window.jacoEditorT
        ? window.jacoEditorT(title, title)
        : title)
    }
    setLabel()
    if (window.jacoEditorReady) {
      window.jacoEditorReady.then(setLabel)
    }
    button.addEventListener('click', (event) => {
      event.stopPropagation()
      onAdd(button)
    })
    return button
  }

  const insertItem = (list, pos, html, refNode) => {
    const item = document.createElement('div')
    item.setAttribute(ITEM_ATTR, '')
    item.innerHTML = html
    attachItemOverlay(item, pos === 'draggable')
    if (pos === 'top' || pos === 'draggable') {
      list.prepend(item)
    } else if (pos === 'bottom' || !refNode) {
      list.append(item)
    } else {
      refNode.after(item)
    }
    if (typeof window.initImages === 'function') {
      window.initImages(item)
    }
    return item
  }

  const loadAndInsert = (list, pos, refNode, fetcher) => {
    const file = list.getAttribute('data-jaco-repeater--fragment') || ''
    return (fetcher || fetchFragment)(file).then((html) => insertItem(list, pos, html, refNode))
  }

  const markButtonFailed = (button) => {
    button.classList.add('is-error')
    setTimeout(() => button.classList.remove('is-error'), 3000)
  }

  const attachItemOverlay = (item, draggable) => {
    const overlay = document.createElement('div')
    overlay.className = OVERLAY_CLASS
    if (draggable) {
      overlay.append(showGrip())
    }
    overlay.append(showDeleteButton(item))
    item.prepend(overlay)
  }

  const showGrip = () => {
    const grip = document.createElement('span')
    grip.className = GRIP_CLASS
    grip.textContent = '⠿'
    const setTitle = () => {
      grip.setAttribute('title', window.jacoEditorT
        ? window.jacoEditorT('drag to reorder', 'drag to reorder')
        : 'drag to reorder')
    }
    setTitle()
    if (window.jacoEditorReady) {
      window.jacoEditorReady.then(setTitle)
    }
    return grip
  }

  // Pointer-based drag reorder, delegated on the list so dynamically added
  // blocks work too. Only the grip starts a drag - whole blocks stay
  // selectable for the Quill editor. Native HTML5 drag & drop is deliberately
  // not used: it does not auto-scroll inside the editor iframe and starts
  // unreliably there. Order lives in the DOM, save needs no changes.
  const makeSortable = (list) => {
    let dragged = null
    let startY = 0
    let active = false
    let scrollDir = 0
    let scrollRaf = 0

    const cleanup = () => {
      dragged = null
      active = false
      scrollDir = 0
      if (scrollRaf) {
        cancelAnimationFrame(scrollRaf)
        scrollRaf = 0
      }
      list.querySelectorAll('.' + DRAGGING_CLASS).forEach((node) => node.classList.remove(DRAGGING_CLASS))
    }

    const elementAfter = (clientY) => {
      const items = [...list.querySelectorAll(':scope > [' + ITEM_ATTR + ']')]
        .filter((item) => item !== dragged)
      return items.find((item) => {
        const rect = item.getBoundingClientRect()
        return clientY < rect.top + rect.height / 2
      }) || null
    }

    const moveTo = (clientY) => {
      const after = elementAfter(clientY)
      if (after) {
        after.before(dragged)
      } else {
        list.append(dragged)
      }
    }

    const autoScroll = () => {
      if (!active) {
        return
      }
      if (scrollDir !== 0) {
        const scroller = document.scrollingElement || document.documentElement
        scroller.scrollTop += scrollDir * 14
      }
      scrollRaf = requestAnimationFrame(autoScroll)
    }

    list.addEventListener('pointerdown', (event) => {
      const grip = event.target.closest('.' + GRIP_CLASS)
      if (!grip || (event.pointerType === 'mouse' && event.button !== 0)) {
        return
      }
      dragged = grip.closest('[' + ITEM_ATTR + ']')
      if (!dragged) {
        return
      }
      startY = event.clientY
      active = false
      try {
        grip.setPointerCapture(event.pointerId)
      } catch {
        // older browsers keep bubbling without capture - delegation still works
      }
    })

    list.addEventListener('pointermove', (event) => {
      if (!dragged) {
        return
      }
      if (!active) {
        if (Math.abs(event.clientY - startY) < 6) {
          return
        }
        active = true
        if (document.activeElement) {
          document.activeElement.blur()
        }
        dragged.classList.add(DRAGGING_CLASS)
        scrollRaf = requestAnimationFrame(autoScroll)
      }
      event.preventDefault()
      const edge = 80
      const height = window.innerHeight || document.documentElement.clientHeight
      scrollDir = event.clientY < edge ? -1 : event.clientY > height - edge ? 1 : 0
      moveTo(event.clientY)
    })

    list.addEventListener('pointerup', cleanup)
    list.addEventListener('pointercancel', cleanup)
  }

  const showDeleteButton = (item) => {
    const button = document.createElement('button')
    button.type = 'button'
    button.textContent = '×'
    button.addEventListener('click', (event) => {
      event.stopPropagation()
      const overlay = item.querySelector(':scope > .' + OVERLAY_CLASS)
      overlay.replaceChildren(showConfirmButton(item), showCancelButton(item, overlay))
    })
    return button
  }

  const showConfirmButton = (item) => {
    const button = document.createElement('button')
    button.type = 'button'
    button.textContent = '✓'
    button.addEventListener('click', (event) => {
      event.stopPropagation()
      item.remove()
    })
    return button
  }

  const showCancelButton = (item, overlay) => {
    const button = document.createElement('button')
    button.type = 'button'
    button.textContent = '↩'
    button.addEventListener('click', (event) => {
      event.stopPropagation()
      overlay.replaceChildren(showDeleteButton(item))
    })
    return button
  }

  const addButtonsForList = (list, fetcher) => {
    if (list._jacoContentListsInit) {
      return
    }
    list._jacoContentListsInit = true
    const pos = posOf(list)
    const onAdd = (button) => loadAndInsert(list, pos, pos === 'any' ? button : null, fetcher)
      .catch(() => markButtonFailed(button))
    if (pos === 'top' || pos === 'draggable') {
      list.before(createAddButton(list, onAdd))
    } else if (pos === 'bottom') {
      list.after(createAddButton(list, onAdd))
    } else {
      list.querySelectorAll(':scope > [' + ITEM_ATTR + ']').forEach((item) => {
        item.before(createAddButton(list, onAdd))
        attachItemOverlay(item, pos === 'draggable')
      })
      list.append(createAddButton(list, onAdd))
    }
    if (pos !== 'any') {
      list.querySelectorAll(':scope > [' + ITEM_ATTR + ']').forEach((item) => attachItemOverlay(item, pos === 'draggable'))
    }
    if (pos === 'draggable') {
      makeSortable(list)
    }
  }

  return {
    initContentLists: (root, fetcher) => {
      (root || document).querySelectorAll('[' + LIST_ATTR + ']').forEach((list) => {
        addButtonsForList(list, fetcher)
      })
    },
    removeContentListChrome: (root) => {
      (root || document).querySelectorAll('.' + ADD_BUTTON_CLASS + ', .' + OVERLAY_CLASS).forEach((element) => {
        element.remove()
      });
      (root || document).querySelectorAll('[' + LIST_ATTR + ']').forEach((list) => {
        delete list._jacoContentListsInit
      });
      (root || document).querySelectorAll('.' + DRAGGING_CLASS).forEach((element) => {
        element.classList.remove(DRAGGING_CLASS)
      })
    },
    // exported for tests
    posOf,
    insertItem,
    attachItemOverlay,
  }
})()
