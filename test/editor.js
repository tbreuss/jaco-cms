var initialized = false;

window.initEditors = function()
{
    if (initialized) {
        return
    }

    var editorOptions = {
        modules: { toolbar: true },
        theme: 'snow'
    };
    var editorElements = document.body.querySelectorAll('.jaco-editable');
    for(var i = 0; i < editorElements.length; i++) {            
        var editor2 = new Quill(editorElements[i], editorOptions);
    }
    initialized = true;
}
