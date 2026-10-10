export const Input = {
  oncreate: ({dom, attrs: {autofocus}}) => {
    if (!autofocus) {
      return
    }

    dom.focus()

    // the app is hidden until the window "load" event and elements of a hidden
    // document cannot take the focus, so focus again as soon as it is visible
    if (document.activeElement !== dom) {
      window.addEventListener('load', () => dom.focus(), {once: true})
    }
  },
  view: ({attrs: {disabled, type, id, value, placeholder, oninput}}) =>
    m('input', {
      type: type,
      id: id,
      value: value,
      placeholder: placeholder,
      disabled: disabled,
      oninput: ({target: {value}}) => oninput(value),
    })
}
