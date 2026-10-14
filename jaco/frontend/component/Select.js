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
  // the selection can still get lost while options swap (blank field):
  // fall back to the first option, which is the prompt
  if (dom.selectedIndex < 0 && dom.options.length > 0) {
    dom.selectedIndex = 0
  }
}

const SelectElement = {
  view: ({attrs: {disabled, id, value, options, prompt, invalid, describedby, onchange}}) => m('select', {
    id: id,
    disabled: disabled,
    value: value,
    'aria-invalid': invalid ? 'true' : null,
    'aria-describedby': describedby || null,
    oncreate: syncValue,
    onupdate: syncValue,
    onchange: ({target}) => {
      const index = target.selectedIndex
      onchange(target[index].value)
    }
  }, selectOptions(getOptions(prompt, options)))
}

export const Select = {
  view: ({attrs: {disabled, wrap, label, id, value, options, prompt, error, onchange}}) => {
    const errorId = error && id ? id + '-error' : null
    return wrap
      ? m(wrap,
        label && m(Label, {id}, label),
        m(SelectElement, {disabled, id, value, options, prompt, invalid: !!error, describedby: errorId, onchange}),
        error && m('.field-error', {id: errorId}, error)
      )
      : [
        label && m(Label, {id}, label),
        m(SelectElement, {disabled, id, value, options, prompt, invalid: !!error, describedby: errorId, onchange}),
        error && m('.field-error', {id: errorId}, error)
      ]
  }
}
