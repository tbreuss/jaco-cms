export const ButtonSecondary = {
  view: ({attrs: {onclick, title, 'aria-label': ariaLabel}, children}) =>
    m('button.secondary', {onclick: onclick, title: title, 'aria-label': ariaLabel}, children)
}
