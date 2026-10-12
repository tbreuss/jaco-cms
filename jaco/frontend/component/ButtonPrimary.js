export const ButtonPrimary = {
  view: ({attrs: {onclick, title, 'aria-label': ariaLabel}, children}) =>
    m('button.primary', {onclick: onclick, title: title, 'aria-label': ariaLabel}, children)
}
