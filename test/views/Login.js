export const Login = {
    username: '',
    password: '',
    view: function () {
        return m('.modal',
            m('.modal-inner',
                m('h1', 'Login'),
                m('div',
                    m('input[type=text]', {
                        value: Login.username,
                        placeholder: 'Username',
                        oninput: function (e) {
                            Login.username = e.target.value
                        }
                    })
                ),
                m('div',
                    m('input[type=password]', {
                        value: Login.password,
                        placeholder: 'Password',
                        oninput: function (e) {
                            Login.password = e.target.value
                        }
                    })
                ),
                m('button', {
                    onclick: function (e) {
                        window.sessionStorage.setItem('authenticated', true)
                        m.route.set('/')
                    }
                }, 'Login')
            )
        )
    }
}