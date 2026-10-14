import {tError} from '../model/i18n.js'

const selectLabel = (label) => {
  const el = (document.querySelector('[for=' + label + ']'))
  if (el) {
    return el.textContent + ': '
  }
  return ''
}

const errorText = (error) =>
  error.source.pointer ? selectLabel(error.source.pointer) + tError(error.detail) : tError(error.detail)

export const FormErrors = {
  view: ({attrs: {errors}}) =>
    errors.length > 0 && (errors.length === 1
      ? m('.form-errors', errorText(errors[0]))
      : m('ul.form-errors', errors.map((error) => m('li', errorText(error)))))
}

// Translated detail of the first error for the given field pointer,
// or undefined when no error belongs to that field.
export const errorFor = (errors, pointer) => {
  const found = (errors ?? []).find((error) => error.source.pointer === pointer)
  return found ? tError(found.detail) : undefined
}
