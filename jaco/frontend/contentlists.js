// Recurring content blocks for the inline editor ("content lists").
// Runs inside the page iframe: no build step, no dependencies, vanilla DOM.
// Defines only functions - loading this file has no side effects, so the
// QUnit harness can load it too.
//
// Authoring:
//
//   <div data-jaco-content-list data-jaco-fragment="news.html"
//        data-jaco-title="Add news" data-jaco-pos="top">
//     <div data-jaco-content-item>…existing block…</div>
//   </div>
//
// data-jaco-pos is "top", "bottom" or "any" (anything else behaves like "any").
// The button label comes from data-jaco-title, so no translations are needed.
// Fragments are fetched from the template/content/view API, which serves files
// from jaco/_template/content. Item order lives in the DOM - save needs no
// changes, but editor chrome has to be stripped (removeContentListChrome)
// before the body is sent, see iframe.getHTML().
window.JacoContentLists = (() => {
  const LIST_ATTR = 'data-jaco-content-list'
  const ITEM_ATTR = 'data-jaco-content-item'
  const ADD_BUTTON_CLASS = 'jaco-content-add-button'
  const OVERLAY_CLASS = 'jaco-content-item-bar'

  const posOf = (list) => {
    const pos = (list.getAttribute('data-jaco-pos') || '').toLowerCase()
    return pos === 'top' || pos === 'bottom' ? pos : 'any'
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
    button.textContent = '+ ' + (list.getAttribute('data-jaco-title') || '')
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
    attachItemOverlay(item)
    if (pos === 'top') {
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
    const file = list.getAttribute('data-jaco-fragment') || ''
    return (fetcher || fetchFragment)(file).then((html) => insertItem(list, pos, html, refNode))
  }

  const markButtonFailed = (button) => {
    button.classList.add('is-error')
    setTimeout(() => button.classList.remove('is-error'), 3000)
  }

  const attachItemOverlay = (item) => {
    const overlay = document.createElement('div')
    overlay.className = OVERLAY_CLASS
    overlay.append(showDeleteButton(item))
    item.prepend(overlay)
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
    if (pos === 'top') {
      list.before(createAddButton(list, onAdd))
    } else if (pos === 'bottom') {
      list.after(createAddButton(list, onAdd))
    } else {
      list.querySelectorAll(':scope > [' + ITEM_ATTR + ']').forEach((item) => {
        item.before(createAddButton(list, onAdd))
        attachItemOverlay(item)
      })
      list.append(createAddButton(list, onAdd))
    }
    if (pos !== 'any') {
      list.querySelectorAll(':scope > [' + ITEM_ATTR + ']').forEach(attachItemOverlay)
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
      })
    },
    // exported for tests
    posOf,
    insertItem,
    attachItemOverlay,
  }
})()
