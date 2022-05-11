export const ButtonSecondary = {
  view: ({ attrs: { onclick }, children }) =>
    m('button.secondary', { onclick: onclick }, children)
}
