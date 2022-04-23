import { Label } from "./Label.js"

const getOptions = (prompt, options) => {
  let els = [];
  if (prompt) {
    els.push({
      label: prompt,
      value: '',
    })
  }
  return els.concat(options)
}

const selectOptions = (options) => {
  return options.map(function (option) {
    return m('option', {
      value: option.value
    }, option.label)
  })
}

const SelectElement = {
  view: ({ attrs: { disabled, id, options, prompt, onchange } }) => m('select', {
    id: id,
    disabled: disabled,
    onchange: ({ target }) => {
      const index = target.selectedIndex
      onchange(target[index].value)
    }
  }, selectOptions(getOptions(prompt, options)))
}

export const Select = {
  view: ({ attrs: { disabled, wrap, label, id, options, prompt, onchange } }) =>
    wrap
      ? m(wrap,
        label && m(Label, { id }, label),
        m(SelectElement, { disabled, id, options, prompt, onchange })
      )
      : (
        label && m(Label, { id }, label),
        m(SelectElement, { disabled, id, options, prompt, onchange })
      )
}
