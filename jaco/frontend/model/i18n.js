// see https://medium.com/i18n-and-l10n-resources-for-developers/localizing-mithril-applications-728fca63eaf3

import {api} from '../service/api.js'

const loadAndSetLocale = () => {
  i18n.status = 'loading'

  // no language parameter: the api serves the configured default language
  api.get('translations').then(
    (data) => {
      i18n.messages = data
      i18n.currentLocale = ''
      i18n.status = 'idle'
    },
  )
}

export const i18n = {
  currentLocale: '',
  messages: {}, // loadAndSetLocale() populates these
  status: 'loading',
  t,
  loadAndSetLocale,
}

// the app locale follows the ui language
loadAndSetLocale()

export function t(key, placeholders = null) {
  let t = i18n.messages[key] || '{' + key + '}'
  if (placeholders) {
    t = t.replace(/\{\w+\}/g, (all) => {
      let key = all.substring(1, all.length - 1)
      return placeholders[key] || all
    })
  }
  return t
}
