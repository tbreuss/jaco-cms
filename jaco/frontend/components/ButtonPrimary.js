export const ButtonPrimary = {
  view: ({ attrs: { onclick }, children }) =>
    m('button.primary', { onclick: onclick }, children)
}
