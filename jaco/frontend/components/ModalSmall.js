export const ModalSmall = {
  view: ({ attrs: { title, cssClass }, children }) =>
    m('.modal',
      m('.modal-sm' + (cssClass ? '.' + cssClass : ''),
        m('h2', title),
        children
      )
    )
}
