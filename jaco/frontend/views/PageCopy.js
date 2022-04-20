import {
    addSuccess
} from '../state/actions.js'
import {
    iframe
} from '../lib/iframe.js'
import {
    t
} from '../models/i18n.js'

let fileName = ''
let pageTitle = ''

export const PageCopy = {
    oninit: function () {
        // i.e. if page was reloaded with given route
        if (!document.getElementById('iframe')) {
            m.route.set('/')
        }
        fileName = iframe.fileName()
        pageTitle = iframe.pageTitle()
    },
    view: function () {
        return m('.modal',
            m('.modal-inner',
                m('h2', t('Copy page')),
                m('fieldset',
                    m('legend', t('New page data')),
                    m('.row',
                        m('.col-25',
                            m('label', t('Filename')),
                        ),
                        m('.col-75',
                            m('input[type=text]', {
                                value: fileName,
                                oninput: (e) => {
                                    fileName = e.target.value
                                }
                            }),
                        )
                    ),
                    m('.row',
                        m('.col-25',
                            m('label', t('Page title')),
                        ),
                        m('.col-75',
                            m('input[type=text]', {
                                value: pageTitle,
                                oninput: (e) => {
                                    pageTitle = e.target.value
                                }
                            }),
                        ),
                    ),
                    m('.row',
                        m('button.submit', {
                            onclick: () => {
                                addSuccess(t('Page was successfully copied.'))
                                m.route.set('/')
                            }
                        }, t('OK')),
                        m('button.cancel', {
                            onclick: (e) => {
                                m.route.set('/')
                            }
                        }, t('Cancel'))
                    )
                ),
            )
        )
    }
}