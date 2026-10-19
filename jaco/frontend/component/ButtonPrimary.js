export const ButtonPrimary = {
  view: ({attrs: {onclick, title, disabled, 'aria-label': ariaLabel}, children}) =>
    m('button.primary', {onclick: onclick, title: title, disabled: disabled, 'aria-label': ariaLabel}, children)
}
