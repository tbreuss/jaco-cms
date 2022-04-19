export const Logout = {
    view: function () {
        return m('.modal',
            m('.modal-inner',
                m('h1', 'Logged out'),
                m('ul.unstyled',
                    m('li', m('a', {
                        href: '..'
                    }, 'Go to homepage')),
                    m('li', m(m.route.Link, {
                        class: 'login',
                        href: '/login'
                    }, "Login again"))
                )
            )
        )
    }
}