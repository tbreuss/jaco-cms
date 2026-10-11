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
