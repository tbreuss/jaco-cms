import { addSuccess } from '../state/actions.js'
import { iframe } from '../lib/iframe.js'

export const PageDelete = {
    view: function () {
        return m('.modal',
            m('.modal-inner',
                m('h2', 'Delete page'),
                m('p', 'Do you really want to delete the page: ' + iframe.fileName() + '?'),
                m('div',
                    m('button', {
                        onclick: () => {
                            addSuccess('Page was successfully deleted.')
                            m.route.set('/')
                        }
                    }, 'Yes'),
                    m('a', {
                        href: '#',
                        onclick: function (e) {
                            e.preventDefault()
                            m.route.set('/')
                        }
                    }, 'No')
                )
            )
        )
    }
}