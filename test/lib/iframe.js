export const iframe = {
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
            return
        }

        // add editor script
        if (!iframe.contentDocument.getElementById('editorInstanceJavascript')) {
            var script = iframe.contentDocument.createElement('script');
            script.id = 'editorInstanceJavascript';
            script.type = 'text/javascript';
            script.src = 'test/editor.js';
            script.async = false;
            script.onload = function () {
                iframe.contentWindow.initEditors()
            };
            iframe.contentDocument.head.appendChild(script);
        } else {
            iframe.contentWindow.initEditors()
        }
    },
    removeEditors: function () {
        var iframe = document.getElementById('pageIframe')
        if (!iframe) {
            return
        }
        if (typeof iframe.contentWindow.removeEditors === 'function') {
            iframe.contentWindow.removeEditors()
        }
    },
    disableEditors: function () {
        var iframe = document.getElementById('pageIframe')
        if (!iframe) {
            return
        }
        if (typeof iframe.contentWindow.disableEditors === 'function') {
            iframe.contentWindow.disableEditors()
        }
    },
    reload: function () {
        var iframe = document.getElementById('pageIframe')
        if (!iframe) {
            return
        }
        iframe.contentWindow.location.reload();
    },
    getHTML: function () {
        var iframe = document.getElementById('pageIframe')
        if (!iframe) {
            throw error
        }
        iframe.contentWindow.removeEditorInstances()
        iframe.contentWindow.removeEditors()
        return iframe.contentWindow.document.documentElement.outerHTML;
    }
}
