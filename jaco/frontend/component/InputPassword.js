import {Label} from './Label.js'
import {Input} from './Input.js'

export const InputPassword = {
  view: ({attrs: {id, label, value, placeholder, wrap, oninput}}) => (
    wrap
      ? m(wrap,
        label && m(Label, {id}, label),
        m(Input, {type: 'password', id, value, placeholder, oninput})
      )
      : [
        label && m(Label, {id}, label),
          m(Input, {type: 'password', id, value, placeholder, oninput})
      ]
  )
}
