export const ButtonPrimary = {
  view: ({attrs: {onclick, title}, children}) =>
    m('button.primary', {onclick: onclick, title: title}, children)
}
