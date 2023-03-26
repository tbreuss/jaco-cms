let modalRef = undefined

export const ModalMedium = {
  view: ({attrs: {title}, children}) =>
    m('.modal', {
        oncreate: ({dom}) => {
          modalRef = dom
        },
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
