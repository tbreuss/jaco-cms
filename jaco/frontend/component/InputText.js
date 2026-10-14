import {Label} from './Label.js'
import {Input} from './Input.js'

export const InputText = {
  view: ({attrs: {autofocus, disabled, id, label, value, placeholder, title, error, wrap, oninput}}) => {
    const errorId = error && id ? id + '-error' : null
    return wrap
      ? m(wrap,
        label && m(Label, {id}, label),
        m(Input, {type: 'text', autofocus, disabled, id, value, placeholder, title, invalid: !!error, describedby: errorId, oninput}),
        error && m('.field-error', {id: errorId}, error)
      )
      : [
        label && m(Label, {id}, label),
        m(Input, {type: 'text', autofocus, disabled, id, value, placeholder, title, invalid: !!error, describedby: errorId, oninput}),
        error && m('.field-error', {id: errorId}, error)
      ]
  }
}
