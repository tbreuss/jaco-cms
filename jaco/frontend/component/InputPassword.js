import {Label} from './Label.js'
import {Input} from './Input.js'

export const InputPassword = {
  view: ({attrs: {id, label, value, placeholder, error, wrap, oninput}}) => {
    const errorId = error && id ? id + '-error' : null
    return wrap
      ? m(wrap,
        label && m(Label, {id}, label),
        m(Input, {type: 'password', id, value, placeholder, invalid: !!error, describedby: errorId, oninput}),
        error && m('.field-error', {id: errorId}, error)
      )
      : [
        label && m(Label, {id}, label),
          m(Input, {type: 'password', id, value, placeholder, invalid: !!error, describedby: errorId, oninput}),
          error && m('.field-error', {id: errorId}, error)
      ]
  }
}
