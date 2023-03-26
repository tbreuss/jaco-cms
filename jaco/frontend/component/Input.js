export const Input = {
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
