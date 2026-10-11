import {Label} from './Label.js'

const getOptions = (prompt, options) => {
  let els = []
  if (prompt) {
    els.push({
      label: prompt,
      value: '',
    })
  }
  return els.concat(options)
}

const selectOptions = (options) => {
  return options.map((option) => {
    return m('option', {
      value: option.value
    }, option.label)
  })
}

const syncValue = ({dom, attrs}) => {
  // mithril applies the value before new options arrive, so reapply it
  // after the children updated, otherwise the prompt stays selected
  if (attrs.value !== undefined && dom.value !== attrs.value) {
    dom.value = attrs.value
  }
}

const SelectElement = {
  view: ({attrs: {disabled, id, value, options, prompt, onchange}}) => m('select', {
    id: id,
    disabled: disabled,
    value: value,
    oncreate: syncValue,
    onupdate: syncValue,
    onchange: ({target}) => {
      const index = target.selectedIndex
      onchange(target[index].value)
    }
  }, selectOptions(getOptions(prompt, options)))
}

export const Select = {
  view: ({attrs: {disabled, wrap, label, id, value, options, prompt, onchange}}) =>
    wrap
      ? m(wrap,
        label && m(Label, {id}, label),
        m(SelectElement, {disabled, id, value, options, prompt, onchange})
      )
      : (
        label && m(Label, {id}, label),
          m(SelectElement, {disabled, id, value, options, prompt, onchange})
      )
}
