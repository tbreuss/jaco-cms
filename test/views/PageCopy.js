import { addSuccess } from '../state/actions.js'

export const PageCopy = {
    view: function () {
        return m('.modal',
            m('.modal-inner',
                m('h2', 'Copy page'),
                m('button', {
                    onclick: () => {
                        addSuccess('Page was successfully copied.')
                        m.route.set('/')
                    }
                }, 'OK'),
                m('a', {
                    href: '#',
                    click: (e) => {
                        e.preventDefault();
                    }
                }, 'Cancel')
            )
        )
    }
}