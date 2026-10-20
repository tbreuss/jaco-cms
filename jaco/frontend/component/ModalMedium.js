let modalRef = undefined

export const ModalMedium = {
  view: ({attrs: {title, closeRoute = '/', onclose, cssClass}, children}) => {
    const close = onclose ?? (() => m.route.set(closeRoute))

    return m('.modal' + (cssClass ? '.' + cssClass : ''), {
      oncreate: ({dom}) => {
        modalRef = dom
        dom.addEventListener('keydown', (e) => {
          if (e.key === 'Escape') {
            e.preventDefault()
            close()
          }
        })
        dom.focus()
      },
      onremove: () => {
        modalRef = undefined
      },
      tabindex: '-1',
      onclick: (e) => {
        e.redraw = false
        if (e.target === modalRef) {
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
