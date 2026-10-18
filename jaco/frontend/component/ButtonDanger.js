export const ButtonDanger = {
  view: ({attrs: {onclick, title, 'aria-label': ariaLabel}, children}) =>
    m('button.danger', {onclick: onclick, title: title, 'aria-label': ariaLabel}, children)
}
