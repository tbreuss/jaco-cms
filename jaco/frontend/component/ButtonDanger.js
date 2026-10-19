// See ButtonPrimary: aria-disabled instead of native disabled keeps the button
// focusable, so the reason in its title/label is reachable.
export const ButtonDanger = {
  view: ({attrs: {onclick, title, disabled, 'aria-label': ariaLabel}, children}) =>
    m('button.danger', {
      onclick: disabled
        ? (event) => {
            event.preventDefault()
            event.stopPropagation()
          }
        : onclick,
      title: title,
      'aria-disabled': disabled ? 'true' : undefined,
      'aria-label': disabled && title ? title : ariaLabel,
    }, children)
}
