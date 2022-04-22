import { iframe } from '../lib/iframe.js'
import { addSuccess } from '../state/actions.js'
import { t } from '../models/i18n.js'

export const PageEdit = {
  oninit: function () {
    if (!window.sessionStorage.getItem('AUTHORIZATION_TOKEN')) {
      m.route.set('/logout')
    }
    // i.e. if page was reloaded with given route
    if (!document.getElementById('iframe')) {
      m.route.set('/')
    }
    iframe.makeEditable()
  },
  view: function () {
    return m('.dialog',
      m('a[href=#]', {
        onclick: function (e) {
          e.preventDefault()
          const fileName = iframe.fileName()
          const html = iframe.getHTML()
          iframe.reload()
          console.log(fileName, html)
          addSuccess(t('Page was successfully saved.'))
          m.route.set('/')
        }
      }, t('Save')),
      m('a[href=#]', {
        onclick: function (e) {
          e.preventDefault()
          iframe.reload()
          m.route.set('/')
        }
      }, t('Cancel')),
    )
  }
}
