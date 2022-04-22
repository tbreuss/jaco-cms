import { addSuccess } from '../state/actions.js'
import { iframe } from '../lib/iframe.js'
import { t } from '../models/i18n.js'

export const PageDelete = {
  view: function () {
    return m('.modal',
      m('.modal-inner',
        m('h2', t('Delete page')),
        m('fieldset',
          m('p', t('Do you really want to delete the page', {
            name: iframe.fileName()
          })),
          m('div',
            m('button.submit', {
              onclick: () => {
                addSuccess(t('Page was successfully deleted.'))
                m.route.set('/')
              }
            }, t('Yes')),
            m('button.cancel', {
              onclick: function (e) {
                m.route.set('/')
              }
            }, t('No'))
          )
        )
      )
    )
  }
}
