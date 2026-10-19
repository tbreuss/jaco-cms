// A disabled button stays focusable and announced: native `disabled` removes it
// from the tab order, so a keyboard or screen-reader user never learns that the
// action exists. aria-disabled plus a prevented click keeps it discoverable.
export const ButtonPrimary = {
  view: ({attrs: {onclick, title, disabled, 'aria-label': ariaLabel}, children}) =>
    m('button.primary', {
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
