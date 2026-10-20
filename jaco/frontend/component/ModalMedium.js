let modalRef = undefined

const focusInitial = (modal) => {
  const inner = modal.querySelector('.modal-inner')
  if (!inner) {
    modal.focus()
    return
  }
  const autofocus = inner.querySelector('[autofocus]')
  if (autofocus && typeof autofocus.focus === 'function') {
    autofocus.focus()
    return
  }
  const focusable = inner.querySelector(
    'input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled])'
  )
  if (focusable && typeof focusable.focus === 'function') {
    focusable.focus()
    return
  }
  modal.focus()
}

export const ModalMedium = {
  view: ({attrs: {title, closeRoute = '/'}, children}) => {
    const close = () => m.route.set(closeRoute)

    return m('.modal', {
      oncreate: ({dom}) => {
        modalRef = dom
        dom.addEventListener('keydown', (e) => {
          if (e.key === 'Escape') {
            e.preventDefault()
            close()
          }
        })
        focusInitial(dom)
      },
      onremove: () => {
        modalRef = undefined
      },
      tabindex: '-1',
      onclick: (e) => {
        if (e.target === modalRef) {
          e.redraw = false
          close()
        }
      }
    },
      m('.modal-inner',
        m('h2', title),
        children
      )
    )
  }
}
