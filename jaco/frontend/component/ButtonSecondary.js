export const ButtonSecondary = {
  view: ({attrs: {onclick, title, 'aria-label': ariaLabel, type}, children}) =>
    m('button.secondary', {onclick: onclick, title: title, 'aria-label': ariaLabel, ...(type ? {type} : {})}, children)
}
