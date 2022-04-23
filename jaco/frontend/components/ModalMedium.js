export const ModalMedium = {
  view: ({ attrs: { title }, children }) =>
    m('.modal',
      m('.modal-inner',
        m('h2', title),
        children
      )
    )
}
