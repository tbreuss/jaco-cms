// See ButtonPrimary: aria-disabled instead of native disabled keeps the button
// focusable, so the reason in its title/label is reachable.
export const ButtonSecondary = {
  view: ({attrs: {onclick, title, disabled, 'aria-label': ariaLabel, type}, children}) =>
    m('button.secondary', {
      onclick: disabled
        ? (event) => {
            event.preventDefault()
            event.stopPropagation()
          }
        : onclick,
      title: title,
      'aria-disabled': disabled ? 'true' : undefined,
      'aria-label': disabled && title ? title : ariaLabel,
      ...(type ? {type} : {}),
    }, children)
}
