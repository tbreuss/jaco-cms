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

export function t(key, placeholders = null, fallback = null) {
  let text = i18n.messages[key] ?? fallback ?? '{' + key + '}'
  if (placeholders) {
    text = fillTemplate(text, placeholders)
  }
  return text
}

function fillTemplate(text, placeholders) {
  return text.replace(/\{\w+\}/g, (all) => {
    let key = all.substring(1, all.length - 1)
    return placeholders[key] ?? all
  })
}

// Validation and domain errors arrive in English (the backend has no
// per-request locale), so they are translated here. Static messages
// are looked up directly, dynamic ones are matched against their
// English template and re-rendered with the translated template.
const errorTemplates = [
  {key: '{value} must not be in the {dir} folder.', placeholders: ['value', 'dir']},
  {key: '{value} must be in the {dir} folder.', placeholders: ['value', 'dir']},
  {key: '{value} is too short, minimum is {min} characters.', placeholders: ['value', 'min'], numeric: ['min']},
  {key: '{value} is too long, maximum is {max} characters.', placeholders: ['value', 'max'], numeric: ['max']},
  {key: '{value} cannot be blank.', placeholders: ['value']},
  {key: '{value} must be a number.', placeholders: ['value']},
  {key: '{value} must be a string.', placeholders: ['value']},
  {key: '{value} is an invalid extension', placeholders: ['value']},
  {key: '{value} is an invalid filename', placeholders: ['value']},
  {key: '{value} is not writeable.', placeholders: ['value']},
  {key: '{value} does not exist.', placeholders: ['value']},
  {key: '{value} must not exist.', placeholders: ['value']},
  {key: '{value} is an invalid sort parameter.', placeholders: ['value']},
  {key: 'Too many attempts. Try again in {minutes} minute(s).', placeholders: ['minutes'], numeric: ['minutes']},
  {key: 'Could not decode image data: {file}', placeholders: ['file']},
  {key: 'Could not write file: {file}', placeholders: ['file']},
]

const templatePatterns = new Map()

function templatePattern(template) {
  let pattern = template.key
  if (!templatePatterns.has(template.key)) {
    pattern = template.key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
    for (const name of template.placeholders) {
      const group = (template.numeric ?? []).includes(name) ? '(\\d+)' : '(.+?)'
      pattern = pattern.replace('\\{' + name + '\\}', group)
    }
    // tolerate the former "a invalid" wording from older backends
    pattern = pattern.replace('an invalid sort parameter', 'an? invalid sort parameter')
    templatePatterns.set(template.key, new RegExp('^' + pattern + '$'))
  }
  return templatePatterns.get(template.key)
}

export function tError(detail) {
  if (typeof detail !== 'string' || detail === '') {
    return detail
  }
  if (i18n.messages[detail] != null) {
    return i18n.messages[detail]
  }
  for (const template of errorTemplates) {
    const match = detail.match(templatePattern(template))
    if (match) {
      const translated = i18n.messages[template.key]
      if (translated == null) {
        return detail
      }
      const values = {}
      template.placeholders.forEach((name, index) => {
        values[name] = match[index + 1]
      })
      return fillTemplate(translated, values)
    }
  }
  return detail
}
