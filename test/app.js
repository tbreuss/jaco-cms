var enableEditor = false

var iframe = {
    pageTitle: function () {
        var iframe = document.getElementById('pageIframe')
        if (!iframe) {
            return ''
        }
        return iframe.contentDocument.title
    },
    fileName: function () {
        var iframe = document.getElementById('pageIframe')
        if (!iframe) {
            return ''
        }
        var pathname = iframe.contentDocument.location.pathname
        return pathname === '/' ? '/index.html' : pathname
    },
    makeEditable: function () {

        var iframe = document.getElementById('pageIframe')
        if (!iframe) {
            return []
        }
        var editorOptions = {
            modules: { toolbar: true },
            theme: 'snow'
          };

        // in parent
        var el = document.getElementById('editor')
        //var editor1 = new Quill(el, editorOptions);

        // add editor styles
        if (!iframe.contentDocument.getElementById('editorStylesheet')) {
            var cssLink = iframe.contentDocument.createElement("link");
            cssLink.href = "https://cdn.quilljs.com/1.3.6/quill.snow.css"; 
            cssLink.id = 'editorStylesheet';
            cssLink.rel = "stylesheet"; 
            cssLink.type = "text/css"; 
            cssLink.async = false;
            cssLink.onload = function () {
                console.log('editorStylesheet loaded')
            };            
            iframe.contentDocument.head.appendChild(cssLink);
        }

        // add editor script
        if (!iframe.contentDocument.getElementById('editorJavascript')) {
            var script = iframe.contentDocument.createElement('script');
            script.id = 'editorJavascript';
            script.type = 'text/javascript';
            script.src = 'https://cdn.quilljs.com/1.3.6/quill.js';
            script.async = false;
            script.onload = function () {
                console.log('editorJavascript loaded')
            };            
            iframe.contentDocument.head.appendChild(script);
        }
        
        // add editor instance styles
        if (!iframe.contentDocument.getElementById('editorInstanceStylesheet')) {
            var cssLink = iframe.contentDocument.createElement("link");
            cssLink.href = "test/editor.css"; 
            cssLink.id = 'editorStylesheet';
            cssLink.rel = "stylesheet"; 
            cssLink.type = "text/css"; 
            cssLink.async = false;
            cssLink.onload = function () {
                console.log('editorInstanceStylesheet loaded')
            };            
            iframe.contentDocument.head.appendChild(cssLink);
        }

        // add editor script
        if (!iframe.contentDocument.getElementById('editorInstanceJavascript')) {
            var script = iframe.contentDocument.createElement('script');
            script.id = 'editorInstanceJavascript';
            script.type = 'text/javascript';
            script.src = 'test/editor.js';
            script.async = false;
            script.onload = function () {
                console.log('editorInstanceJavascript loaded')
                // iframe.contentWindow.initEditors()
            };               
            iframe.contentDocument.head.appendChild(script);
        }
    }
}

// Layout.js
var Layout = {
    view: function (vnode) {
        // see https://www.w3schools.com/howto/howto_css_dropdown_navbar.asp
        return [
            m(".toolbar",
            window.sessionStorage.getItem('authenticated') ? m(".navbar", [
                m(m.route.Link, {
                    class: 'title',
                    href: '/',
                }, 'JACO'),
                m('.dropdown',
                    m('button.dropbtn', 'Pages'),
                    m('.dropdown-content',
                        m(m.route.Link, {
                            href: '/page-edit'
                        }, "Edit page"),
                        m(m.route.Link, {
                            href: '/page-copy'
                        }, "Copy page"),
                        m(m.route.Link, {
                            href: '/page-delete'
                        }, "Delete page"),
                        m('.ruler'),
                        m(m.route.Link, {
                            href: '/page-list'
                        }, "List pages"),
                        m(m.route.Link, {
                            href: '/page-add'
                        }, "Add page"),
                    )
                ),
                m('a[href=#]', 'Uploads'),
                m('a[href=#]', 'Backups'),
                m('a[href=#]', 'CSS'),
                m('a[href=#]', 'HTML'),
                m(m.route.Link, {
                    class: 'logout',
                    href: '/logout'
                }, "Logout"),
            ]) : m(".navbar", [
                m(m.route.Link, {
                    class: 'title',
                    href: '/',
                }, 'JACO'),
                m(m.route.Link, {
                    class: 'login',
                    href: '/login'
                }, "Login")
            ]),
        ),
        vnode.children
    ]
    }
}

// Start
var Start = {
    view: function () {}
}

// Login
var Login = {
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
                        oninput: function(e) {
                            Login.username = e.target.value
                        }
                    })
                ),
                m('div', 
                    m('input[type=password]', {
                        value: Login.password,
                        placeholder: 'Password',
                        oninput: function(e) {
                            Login.password = e.target.value
                        }
                    })  
                ),
                m('button', {
                    onclick: function(e) {                    
                        window.sessionStorage.setItem('authenticated', true)
                        m.route.set('/')
                    }
                }, 'Login')
            )
        )
    }
}

// Logout
var Logout = {
    view: function () {
        return m('.modal', 
            m('.modal-inner',
                m('h1', 'Logged out'),
                m('ul.unstyled', 
                    m('li', m('a', {href: '..'}, 'Go to homepage')),
                    m('li', m(m.route.Link, {
                        class: 'login',
                        href: '/login'
                    }, "Login again"))
                )
            )
        )
    }
}

// PageEdit
var PageEdit = {
    view: function () {
        return m(".dialog",
            m('a[href=#]', 'Speichern'),
            m('a[href=#]', 'Abbrechen'),
        )
    }
}

// PageCopy
var PageCopy = {
    view: function () {
        return m('.modal',
            m('.modal-inner',
                m('h2', 'Page Copy'),
                m('button', {
                    onclick: () => m.route.set('/')
                }, 'OK')
            )
        )
    }
}

// PageDelete
var PageDelete = {
    view: function () {
        return m('.modal',
            m('.modal-inner',
                m('h2', 'Delete page'),
                m('p', 'Do you really want to delete the page: ' + iframe.fileName() + '?'),
                m('div',
                    m('button', {
                        onclick: () => m.route.set('/')
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

m.route(document.body, "/", {
    "/": {
        render: function () {
            return m(Layout, m(Start))
        }
    },
    "/page-edit": {
        render: function () {
            iframe.makeEditable()
            return m(Layout, m(PageEdit))
        }
    },
    "/page-copy": {
        render: function () {
            return m(Layout, m(PageCopy))
        }
    },
    "/page-delete": {
        render: function () {
            return m(Layout, m(PageDelete))
        }
    },
    "/login": {
        render: function () {
            return m(Layout, m(Login))
        }
    },
    "/logout": {
        render: function () {
            window.sessionStorage.removeItem('authenticated')
            return m(Layout, m(Logout))
        }
    }
})