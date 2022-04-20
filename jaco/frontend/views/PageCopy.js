import { addSuccess } from '../state/actions.js'
import { t } from '../models/i18n.js'

export const PageCopy = {
    view: function () {
        return m('.modal',
            m('.modal-inner',
                m('h2', t('Copy page')),
                m('button', {
                    onclick: () => {
                        addSuccess(t('Page was successfully copied.'))
                        m.route.set('/')
                    }
                }, t('OK')),
                m('a', {
                    href: '#',
                    click: (e) => {
                        e.preventDefault();
                    }
                }, t('Cancel'))
            )
        )
    }
}