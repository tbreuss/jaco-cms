let modalRef = undefined

export const ModalMedium = {
  view: ({attrs: {title}, children}) =>
    m('.modal', {
        oncreate: ({dom}) => {
          modalRef = dom
          dom.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
              e.preventDefault()
              m.route.set('/')
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
            m.route.set('/')
          }
        }
      },
      m('.modal-inner',
        m('h2', title),
        children
      )
    )
}
