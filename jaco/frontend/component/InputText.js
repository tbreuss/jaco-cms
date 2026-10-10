import {Label} from './Label.js'
import {Input} from './Input.js'

export const InputText = {
  view: ({attrs: {autofocus, disabled, id, label, value, placeholder, wrap, oninput}}) => (
    wrap
      ? m(wrap,
        label && m(Label, {id}, label),
        m(Input, {type: 'text', autofocus, disabled, id, value, placeholder, oninput})
      )
      : [
        label && m(Label, {id}, label),
        m(Input, {type: 'text', autofocus, disabled, id, value, placeholder, oninput})
      ]
  )
}
