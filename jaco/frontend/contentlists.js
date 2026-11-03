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
  const LIST_ATTRS = ['data-jaco-repeater', 'data-jaco-slot']
  const ADD_BUTTON_CLASS = 'jaco-content-add-button'
  const OVERLAY_CLASS = 'jaco-content-item-bar'
  const GRIP_CLASS = 'jaco-content-grip'
  const CHOOSER_CLASS = 'jaco-slot-chooser'
  const DRAGGING_CLASS = 'is-dragging'

  const configOf = (list) => {
    if (list.hasAttribute('data-jaco-slot')) {
      return {
        listAttr: 'data-jaco-slot',
        positionAttr: 'data-jaco-slot--position',
        sortableAttr: 'data-jaco-slot--sortable',
        labelAttr: 'data-jaco-slot--label',
      }
    }
    return {
      listAttr: 'data-jaco-repeater',
      positionAttr: 'data-jaco-repeater--position',
      sortableAttr: 'data-jaco-repeater--sortable',
      labelAttr: 'data-jaco-repeater--label',
    }
  }

  const isContainer = (element) => LIST_ATTRS.some((attr) => element.hasAttribute(attr))

  // Whether editor chrome belongs to `root` (descendant of it, or the root
  // itself) and may go with it. Chrome of a list nested inside `root` does not:
  // it either belongs to a block that stays, or the block it sits in is removed
  // anyway and takes it along.
  const isChromeOf = (element, root) => {
    if (root === element || root.contains(element)) {
      return true
    }
    // a button stands next to its list, not inside it: the list decides its
    // fate, not the button's own position. A button whose list leaves with the
    // block has to leave too, or it is left pointing at a list that is gone.
    const list = element.classList.contains(ADD_BUTTON_CLASS) ? element._jacoList : null
    return Boolean(list && (root === list || root.contains(list)))
  }

  const listAttrOf = (list) => configOf(list).listAttr

  // The one definition of "a block of this list": every direct child except the
  // editor's own chrome. It is the same question in reconcile and in the mount,
  // so it is asked in one place - and it has to know the chooser, which stands
  // in the list too: counting it as a block would hang an overlay inside it and
  // that overlay would end up in the saved page.
  const CHROME_CLASSES = [ADD_BUTTON_CLASS, OVERLAY_CLASS, CHOOSER_CLASS]
  const isChrome = (child) => CHROME_CLASSES.some((name) => child.classList.contains(name))
  const blocksOfList = (list) => [...list.children].filter((child) => !isChrome(child))

  // Where the add button goes. Anything but top and bottom means "any".
  const posOf = (list) => {
    const cfg = configOf(list)
    const pos = (list.getAttribute(cfg.positionAttr) || '').toLowerCase()
    return pos === 'top' || pos === 'bottom' ? pos : 'any'
  }

  // Whether the blocks can be reordered. A separate question, answered by a
  // separate attribute: the position says where a button goes, this says whether
  // blocks move.
  const isSortable = (list) => list.hasAttribute(configOf(list).sortableAttr)

  const resolveFragments = async (list) => {
    const cfg = configOf(list)
    const value = (list.getAttribute(cfg.listAttr) || '').trim()

    // an empty slot offers every content fragment
    if (value === '') {
      if (cfg.listAttr !== 'data-jaco-slot') {
        return []
      }
      const all = await fetchAllFragments()
      return all.map((item) => item.file)
    }

    // several explicit fragments
    if (/[\s,]/.test(value)) {
      return value.split(/[\s,]+/).map((item) => item.trim()).filter(Boolean)
    }

    // a repeater has exactly one fragment
    if (cfg.listAttr !== 'data-jaco-slot') {
      return [value]
    }

    // an explicit fragment file
    if (value.endsWith('.html')) {
      return [value]
    }

    // otherwise the value is a prefix or directory filter
    const all = await fetchAllFragments()
    return all
      .map((item) => item.file)
      .filter((file) => {
        if (file === value) return true
        if (!file.startsWith(value)) return false
        const next = file.charAt(value.length)
        // a prefix only matches a complete path segment or name part, not
        // an unrelated file that merely starts with the same letters
        return next === '' || next === '/' || next === '-' || next === '_' || next === '.'
      })
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

  let allFragmentsPromise = null
  const fetchAllFragments = () => {
    if (allFragmentsPromise) return allFragmentsPromise
    const token = window.sessionStorage.getItem('AUTHORIZATION_TOKEN')
    const headers = token ? {Authorization: 'Bearer ' + token} : {}
    allFragmentsPromise = fetch('/jaco/api.php?action=template/content', {headers})
      .then((response) => {
        if (!response.ok) {
          throw new Error('Could not load fragments')
        }
        return response.json()
      })
      .then((data) => Array.isArray(data) ? data : [])
    return allFragmentsPromise
  }

  const createAddButton = (list, onAdd) => {
    const button = document.createElement('div')
    button.className = ADD_BUTTON_CLASS
    button.classList.add(
      configOf(list).listAttr === 'data-jaco-slot'
        ? 'jaco-content-add-button--slot'
        : 'jaco-content-add-button--repeater',
    )
    button._jacoList = list
    const title = list.getAttribute(configOf(list).labelAttr) || ''
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

  const insertItem = (list, pos, html, refNode, fetcher) => {
    // the fragment has exactly one root element, and that root becomes the block.
    // Anything else is a broken fragment: an empty holder would otherwise be
    // appended as a blank block that only disappears when the page is saved
    const holder = document.createElement('div')
    holder.innerHTML = (html || '').trim()
    const item = holder.firstElementChild
    if (!item || holder.children.length !== 1) {
      throw new Error('A content fragment needs exactly one root element')
    }
    attachItemOverlay(item, isSortable(list))
    if (pos === 'top') {
      list.prepend(item)
    } else if (pos === 'bottom') {
      list.append(item)
    } else if (refNode) {
      // the button stands before its block: the new block goes before that one,
      // whether the button sits inside the list or just in front of it
      const next = refNode.nextElementSibling
      if (next) {
        next.before(item)
      } else {
        list.append(item)
      }
    } else {
      // "any" without a button to anchor at: the trailing button of an empty
      // list has none behind it, so the block must not go after it -
      // reconcileAddButtons() replays the buttons around it
      const trailing = [...list.children].reverse().find((child) =>
        child.classList.contains(ADD_BUTTON_CLASS),
      )
      if (trailing) {
        trailing.before(item)
      } else {
        list.append(item)
      }
    }
    // the buttons follow the blocks in an "any" list: without this the new block
    // stood behind the trailing button (B K instead of B K B)
    if (pos === 'any') {
      reconcileAddButtons(list, list._jacoOnAdd)
    }
    if (typeof window.initImages === 'function') {
      window.initImages(item)
    }
    // fragments may contain content lists of their own; mount their controls
    // without waiting for a page reload
    if (window.JacoContentLists && typeof window.JacoContentLists.initContentLists === 'function') {
      window.JacoContentLists.initContentLists(item, fetcher)
    }
    return item
  }

  const loadAndInsert = (list, pos, refNode, fetcher, file) => {
    const resolveFile = file
      ? Promise.resolve(file)
      : resolveFragments(list).then((fragments) => fragments[0] || '')
    return resolveFile.then((targetFile) =>
      (fetcher || fetchFragment)(targetFile).then((html) => insertItem(list, pos, html, refNode, fetcher)),
    )
  }

  const markButtonFailed = (button) => {
    button.classList.add('is-error')
    setTimeout(() => button.classList.remove('is-error'), 3000)
  }

  const attachItemOverlay = (item, sortable) => {
    // make the helper idempotent: a block that is initialised twice (for
    // example when a content list is mounted on an already decorated block)
    // must not end up with stacked delete bars
    item.querySelectorAll(':scope > .' + OVERLAY_CLASS).forEach((existing) => existing.remove())
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
      // moving the block carries its button (see buttonOf), but a drop is the
      // moment the order has to be right: restack once instead of trusting
      // that every move kept the pairing intact
      if (posOf(list) === 'any') {
        reconcileAddButtons(list, list._jacoOnAdd)
      }
    }

    const elementAfter = (clientY) => {
      // add buttons are drop targets too: skipping them would drop the block on
      // the other side of its neighbour's button (and the trailing one)
      const items = [...list.children].filter((item) => item !== dragged)
      return items.find((item) => {
        const rect = item.getBoundingClientRect()
        return clientY < rect.top + rect.height / 2
      }) || null
    }

    // In an "any" list a block and its add button are one unit: the button
    // stands right in front of the block, so moving only the block leaves the
    // buttons shuffled (B K B K became B K K B) until the next page load.
    // Carry the button along whenever the block moves.
    const buttonOf = (block) => {
      const previous = block.previousElementSibling
      return previous &&
        previous.classList.contains(ADD_BUTTON_CLASS) &&
        previous._jacoList === list
        ? previous
        : null
    }

    const placeBefore = (block, reference) => {
      const button = buttonOf(block)
      if (button) {
        reference.before(button)
      }
      reference.before(block)
    }

    const moveTo = (clientY) => {
      const after = elementAfter(clientY)
      if (after) {
        placeBefore(dragged, after)
      } else {
        // "any" lists keep their last add button at the end
        const trailing = [...list.children].reverse().find((child) =>
          child.classList.contains(ADD_BUTTON_CLASS),
        )
        if (trailing) {
          placeBefore(dragged, trailing)
        } else {
          list.append(dragged)
        }
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
      // the block may sit in a repeater or a slot; remember the list before the
      // block leaves the DOM
      const parentList = item.parentElement
      const owningList = parentList && isContainer(parentList) ? parentList : null
      // tear down the controls the block carries with it (its own overlay, the
      // buttons of lists nested inside it, an open chooser)
      if (window.JacoContentLists && typeof window.JacoContentLists.removeContentListChrome === 'function') {
        window.JacoContentLists.removeContentListChrome(item)
      }
      if (owningList) {
        // In an "any" list a block owns exactly one button, standing right in
        // front of it. That button is not inside the block, so
        // removeContentListChrome() above leaves it behind - name it here.
        // A "top"/"bottom" button belongs to the list, not to this block, and
        // stays.
        if (posOf(owningList) === 'any') {
          const previous = item.previousElementSibling
          if (previous && previous.classList.contains(ADD_BUTTON_CLASS) && previous._jacoList === owningList) {
            previous.remove()
          }
        }
      }
      item.remove()
      // restack the surrounding list: dropping the block also dropped its button,
      // and in an "any" list the block after it would keep standing without one
      if (owningList) {
        delete owningList._jacoContentListsInit
        addButtonsForList(owningList)
      }
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

  const showFragmentChooser = (list, button, fragments, onChoose) => {
    const existing = list.querySelector('.' + CHOOSER_CLASS)
    if (existing) {
      existing.remove()
      return
    }
    const chooser = document.createElement('div')
    chooser.className = CHOOSER_CLASS
    fragments.forEach((file) => {
      const option = document.createElement('button')
      option.type = 'button'
      option.textContent = file
      option.addEventListener('click', (event) => {
        event.stopPropagation()
        chooser.remove()
        onChoose(file)
      })
      chooser.append(option)
    })
    button.after(chooser)
  }

  // Position "any" puts a button before every block plus one at the end, so it
  // holds as long as every block has its button in front of it. After a deletion
  // the order is rewritten here instead of only touching the neighbours: a
  // deleted block takes its own button with it, and the block after it would
  // then stand without one until the next page load.
  const reconcileAddButtons = (list, onAdd) => {
    const blocks = blocksOfList(list)
    // only the buttons that stand in the list: this restacks the inside, and a
    // "top"/"bottom" neighbour list in the same parent is none of its business
    const buttons = [...list.children].filter((child) =>
      child.classList.contains(ADD_BUTTON_CLASS) && child._jacoList === list,
    )
    // one button in front of every block, the rest is spare
    const sortable = isSortable(list)
    blocks.forEach((block, index) => {
      list.insertBefore(buttons[index] || createAddButton(list, onAdd), block)
      attachItemOverlay(block, sortable)
    })
    // plus exactly one at the end
    const trailing = buttons[blocks.length] || createAddButton(list, onAdd)
    buttons.slice(blocks.length + 1).forEach((button) => button.remove())
    list.append(trailing)
  }

  const addButtonsForList = (list, fetcher) => {
    // The handler is bound once and reads the current state of the list, so
    // re-mounting the buttons never stacks a second listener on the same list:
    // the init flag may be cleared any number of times without a click ending
    // up inserting the fragment once per binding.
    if (!list._jacoOnAdd) {
      list._jacoOnAdd = async (button) => {
        let fragments
        try {
          fragments = await resolveFragments(list)
        } catch {
          markButtonFailed(button)
          return
        }
        const insert = (file) => loadAndInsert(list, posOf(list), posOf(list) === 'any' ? button : null, fetcher, file)
          .catch(() => markButtonFailed(button))
        if (fragments.length <= 1) {
          insert(fragments[0])
        } else {
          showFragmentChooser(list, button, fragments, insert)
        }
      }
    }
    const onAdd = list._jacoOnAdd
    const pos = posOf(list)
    const blocks = blocksOfList(list)
    if (pos === 'any') {
      // the stacked order is the state: it is read from the DOM, so a missing
      // button is repaired by any mount, whether or not the flag is set
      reconcileAddButtons(list, onAdd)
    } else {
      if (list._jacoContentListsInit) {
        return
      }
      // top and bottom keep their button outside the list, so a stale button
      // from an earlier mount must not be left behind next to the new one
      list._jacoAddButton?.remove()
      blocks.forEach((item) => attachItemOverlay(item, isSortable(list)))
      const button = createAddButton(list, onAdd)
      list._jacoAddButton = button
      // `list.before(button)` cannot be used here: without a parent it falls
      // back to the document, and a repeater nested in a wrapper would push its
      // button out of that wrapper - into the surrounding repeater or slot,
      // which then mistakes it for one of its own. Stay next to the list inside
      // the list's own parent.
      if (list.parentNode) {
        if (pos === 'top') {
          list.parentNode.insertBefore(button, list)
        } else {
          list.parentNode.insertBefore(button, list.nextSibling)
        }
      }
    }
    list._jacoContentListsInit = true
    if (isSortable(list) && !list._jacoSortableInit) {
      list._jacoSortableInit = true
      makeSortable(list)
    }
  }

  return {
    initContentLists: (root, fetcher) => {
      const selector = LIST_ATTRS.map((attr) => '[' + attr + ']').join(', ')
      const scope = root || document
      if (root && typeof root.hasAttribute === 'function' && LIST_ATTRS.some((attr) => root.hasAttribute(attr))) {
        addButtonsForList(root, fetcher)
      }
      scope.querySelectorAll(selector).forEach((list) => {
        addButtonsForList(list, fetcher)
      })
    },
    removeContentListChrome: (root) => {
      // An open fragment chooser is editor chrome too: left behind, it survives
      // the save and would end up in the page.
      // Buttons stand next to their list, so this walks the document and keeps
      // the chrome of lists nested inside `root`: deleting an outer block must
      // not strip the controls of a repeater inside a block that stays.
      document
        .querySelectorAll('.' + ADD_BUTTON_CLASS + ', .' + OVERLAY_CLASS + ', .' + CHOOSER_CLASS)
        .forEach((element) => {
          if (!root || isChromeOf(element, root)) {
            element.remove()
          }
        });
      (root || document).querySelectorAll(LIST_ATTRS.map((attr) => '[' + attr + ']').join(', ')).forEach((list) => {
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
