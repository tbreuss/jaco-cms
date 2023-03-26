let modalRef = undefined

export const ModalSmall = {
  view: ({attrs: {title, cssClass}, children}) =>
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
      m('.modal-sm' + (cssClass ? '.' + cssClass : ''),
        m('h2', title),
        children
      )
    )
}
