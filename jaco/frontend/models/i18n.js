// see https://medium.com/i18n-and-l10n-resources-for-developers/localizing-mithril-applications-728fca63eaf3

const defaultLocale = 'en';
const messageUrl = 'api.php?action=translations&lang={locale}';

export const i18n = {
  defaultLocale,
  currentLocale: '', 
  messages: {}, // loadAndSetLocale() populates these
  status: 'loading',
  t,
  loadAndSetLocale,
};

export function t(key, placeholders = null) {
  let t = i18n.messages[key] || '{' + key + '}'
  if (placeholders) {
    t = t.replace(/\{\w+\}/g, function(all) {
      let key = all.substring(1, all.length - 1)
      return placeholders[key] || all
    })    
  }
  return t;
}

function loadAndSetLocale(newLocale) {
  if (i18n.currentLocale === newLocale) {
    return;
  }

  i18n.status = 'loading';

  fetchMessages(newLocale, (messages) => {
    i18n.messages = messages;
    i18n.currentLocale = newLocale;
    i18n.status = 'idle';
  });
}

function fetchMessages(locale, onComplete) {
  m.request(messageUrl.replace('{locale}', locale)).then(
    onComplete,
  );
}
