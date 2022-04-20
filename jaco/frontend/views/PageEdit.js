import { iframe } from '../lib/iframe.js'
import { addSuccess } from '../state/actions.js'

export const PageEdit = {
    oninit: function () {
        // i.e. if page was reloaded with given route
        if (!document.getElementById('iframe')) {
            iframe.reload()
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
                    addSuccess('Page was successfully saved.')
                    m.route.set('/')
                }
            }, 'Save'),
            m('a[href=#]', {
                onclick: function (e) {
                    e.preventDefault()
                    iframe.reload()
                    m.route.set('/')
                }
            }, 'Cancel'),
        )
    }
}