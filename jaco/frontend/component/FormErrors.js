import {t, tError} from '../model/i18n.js'

const selectLabel = (label) => {
  const el = (document.querySelector('[for=' + label + ']'))
  if (el) {
    return el.textContent + ': '
  }
  return ''
}

export const FormErrors = {
  view: ({attrs: {errors}}) =>
    errors.length > 0 && m('fieldset.errors',
      m('legend', t('The following errors occurred')),
      m('ul', errors.map((error) =>
        m('li', error.source.pointer ? selectLabel(error.source.pointer) + tError(error.detail) : tError(error.detail))
      ))
    )
}

// Translated detail of the first error for the given field pointer,
// or undefined when no error belongs to that field.
export const errorFor = (errors, pointer) => {
  const found = (errors ?? []).find((error) => error.source.pointer === pointer)
  return found ? tError(found.detail) : undefined
}
