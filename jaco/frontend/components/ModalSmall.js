export const ModalSmall = {
  view: ({ attrs: { title }, children }) =>
    m('.modal',
      m('.modal-sm',
        m('h2', title),
        children
      )
    )
}
