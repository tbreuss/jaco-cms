// see https://medium.com/i18n-and-l10n-resources-for-developers/localizing-mithril-applications-728fca63eaf3

import {api} from '../service/api.js'

const defaultLocale = 'en'
const messageUrl = 'action=translations&language={locale}'

const loadAndSetLocale = (newLocale) => {
  if (i18n.currentLocale === newLocale) {
    return
  }

  i18n.status = 'loading'

  fetchMessages(newLocale, (data) => {
    i18n.messages = data
    i18n.currentLocale = newLocale
    i18n.status = 'idle'
  })
}

const fetchMessages = (locale, onComplete) => {
  api.get(messageUrl.replace('{locale}', locale)).then(
    onComplete,
  )
}

export const i18n = {
  defaultLocale,
  currentLocale: '',
  messages: {}, // loadAndSetLocale() populates these
  status: 'loading',
  t,
  loadAndSetLocale,
}

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
