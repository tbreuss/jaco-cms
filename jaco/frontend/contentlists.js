// Recurring content blocks for the inline editor ("content lists").
// Runs inside the page iframe: no build step, no dependencies, vanilla DOM.
// Defines only functions - loading this file has no side effects, so the
// QUnit harness can load it too.
//
// Authoring:
//
//   <div data-jaco-repeater="news.html"
//        data-jaco-repeater--label="Add news" data-jaco-repeater--position="top">
//     <div>…existing block…</div>
//   </div>
//
// The value of data-jaco-repeater is the fragment; every direct child is one block.
// data-jaco-repeater--position is "top", "bottom" or "any"; --sortable adds the
// grip. The button label comes from --label, so no translations are needed.
// Fragments are fetched from the template/content/view API, which serves files
// from jaco-data/templates/content. Item order lives in the DOM - save needs no
// changes, but editor chrome has to be stripped (removeContentListChrome)
// before the body is sent, see iframe.getHTML().
window.JacoContentLists = (() => {
  const LIST_ATTR = 'data-jaco-repeater'
  const ADD_BUTTON_CLASS = 'jaco-content-add-button'
  const OVERLAY_CLASS = 'jaco-content-item-bar'
  const GRIP_CLASS = 'jaco-content-grip'
  const DRAGGING_CLASS = 'is-dragging'

  const POSITION_ATTR = 'data-jaco-repeater--position'
  const SORTABLE_ATTR = 'data-jaco-repeater--sortable'

  // Where the add button goes. Anything but top and bottom means "any".
  const posOf = (list) => {
    const pos = (list.getAttribute(POSITION_ATTR) || '').toLowerCase()
    return pos === 'top' || pos === 'bottom' ? pos : 'any'
  }

  // Whether the blocks can be reordered. A separate question, answered by a
  // separate attribute: the position says where a button goes, this says whether
  // blocks move.
  const isSortable = (list) => list.hasAttribute(SORTABLE_ATTR)

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
    // the fragment has exactly one root element, and that root becomes the block
    const holder = document.createElement('div')
    holder.innerHTML = html.trim()
    const item = holder.firstElementChild || document.createElement('div')
    attachItemOverlay(item, isSortable(list))
    if (pos === 'top') {
      list.prepend(item)
    } else if (pos === 'bottom' || !refNode) {
      list.append(item)
    } else if (refNode.parentElement === list) {
      refNode.before(item)
    } else {
      // the any-buttons stand before their block, one level up from the list
      const next = refNode.nextElementSibling
      if (next) {
        next.before(item)
      } else {
        list.append(item)
      }
    }
    if (typeof window.initImages === 'function') {
      window.initImages(item)
    }
    return item
  }

  const loadAndInsert = (list, pos, refNode, fetcher) => {
    const file = list.getAttribute(LIST_ATTR) || ''
    return (fetcher || fetchFragment)(file).then((html) => insertItem(list, pos, html, refNode))
  }

  const markButtonFailed = (button) => {
    button.classList.add('is-error')
    setTimeout(() => button.classList.remove('is-error'), 3000)
  }

  const attachItemOverlay = (item, sortable) => {
    const overlay = document.createElement('div')
    overlay.className = OVERLAY_CLASS
    if (sortable) {
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
      const items = [...list.children].filter((item) => item !== dragged)
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
      // the grip sits in the overlay, the overlay in the block
      dragged = grip.closest('.' + OVERLAY_CLASS)?.parentElement ?? null
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
    const sortable = isSortable(list)
    const onAdd = (button) => loadAndInsert(list, pos, pos === 'any' ? button : null, fetcher)
      .catch(() => markButtonFailed(button))
    if (pos === 'top') {
      list.before(createAddButton(list, onAdd))
    } else if (pos === 'bottom') {
      list.after(createAddButton(list, onAdd))
    } else {
      ;[...list.children].forEach((item) => {
        item.before(createAddButton(list, onAdd))
        attachItemOverlay(item, sortable)
      })
      list.append(createAddButton(list, onAdd))
    }
    if (pos !== 'any') {
      ;[...list.children].forEach((item) => attachItemOverlay(item, sortable))
    }
    if (sortable) {
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
