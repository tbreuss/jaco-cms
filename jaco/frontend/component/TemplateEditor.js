import {t} from '../model/i18n.js'
import {ButtonSecondary} from './ButtonSecondary.js'
import {ButtonPrimary} from './ButtonPrimary.js'
import {ButtonGroup} from './ButtonGroup.js'
import {Fieldset} from './Fieldset.js'
import {FormErrors, errorFor} from './FormErrors.js'
import {Icon} from './Icon.js'
import {InputText} from './InputText.js'
import {Select} from './Select.js'
import {addSuccess} from '../state/actions.js'
import {app} from '../lib/app.js'
import {bindSaveShortcut, saveAndCloseShortcutLabel, saveShortcutLabel} from '../lib/shortcuts.js'
import {aceThemeName, watchAceTheme} from '../lib/aceTheme.js'
import {dirty} from '../lib/dirty.js'

// One editor for all three kinds of template. A kind passes its model (list,
// load, save), the ACE mode and a hint above the editor; the chrome - select,
// editor, validation errors, save buttons, the unsaved-changes guard - is the
// same for every kind.
//
// The labels arrive as functions, not as text: the translations load after the
// module, and a t() call on the way in would freeze the "{Key}" placeholder for
// the whole life of the page.
//
// The markup belongs to the admin: everybody else gets a read-only editor and
// disabled save buttons, and the update routes answer 403 for them anyway.
export const createTemplateEditor = ({
  kind,
  model,
  mode,
  heading,
  editorLabel,
  hint = () => null,
  missingHint,
  canEdit,
  extraButtons,
}) => {
  let editor = undefined
  let unbindSaveShortcut = null
  let unwatchAceTheme = null

  const state = {
    errors: [],
    file: '',
    savedContent: null,
    loadedFile: null,
  }

  // The select shows file names, so that is the order it has to be in. The page
  // listing arrives sorted by title (the page manager shows titles there), which
  // reads as unsorted next to the file names. The first entry is the one that
  // opens, so it comes from the same order.
  const sortedList = () => [...model.list].sort((a, b) =>
    a.file.localeCompare(b.file, undefined, {numeric: true, sensitivity: 'base'}))

  const editorId = () => 'ace-' + kind

  const isDirty = () => editor && state.savedContent !== null && editor.getValue() !== state.savedContent

  const confirmDiscard = () => !isDirty() || confirm(t('Discard unsaved changes?', null, 'Discard unsaved changes?'))

  const beforeUnloadHandler = (event) => {
    if (isDirty()) {
      event.preventDefault()
      event.returnValue = ''
    }
  }

  // The editor lives in the view's DOM: returning to it drops the instance, so a
  // later visit does not reuse one that hangs off a detached node.
  const destroyEditor = () => {
    if (editor) {
      editor.destroy()
      editor = undefined
    }
    if (unwatchAceTheme) {
      unwatchAceTheme()
      unwatchAceTheme = null
    }
  }

  const ensureEditor = () => {
    if (editor || !document.getElementById(editorId())) {
      return
    }
    editor = ace.edit(editorId(), {
      theme: aceThemeName(),
      mode,
      maxLines: Infinity,
      wrap: true,
      indentedSoftWrap: false,
      behavioursEnabled: false,
      showLineNumbers: false,
      readOnly: !canEdit(),
    })
    editor.resize(false)
    if (canEdit()) {
      editor.commands.addCommand({
        name: 'save',
        bindKey: {win: 'Ctrl-S', mac: 'Command-S'},
        exec: () => onSaveButtonClicked({redraw: false}),
      })
      editor.commands.addCommand({
        name: 'saveAndClose',
        bindKey: {win: 'Ctrl-Shift-S', mac: 'Command-Shift-S'},
        exec: () => onSaveAndCloseButtonClicked({redraw: false}),
      })
    }
    unwatchAceTheme = watchAceTheme(editor)
  }

  const showLoadedFile = () => {
    if (!editor || !model.item.file || model.item.file !== state.file || model.item.file === state.loadedFile) {
      return
    }
    state.savedContent = model.item.content
    state.loadedFile = model.item.file
    editor.setValue(model.item.content, 1) // cursor at the end
  }

  const selectFile = (file) => {
    if (!file || file === state.file) {
      return Promise.resolve()
    }
    state.file = file
    state.loadedFile = null
    return model.load(file).then(showLoadedFile)
  }

  const onInitComponent = () => {
    window.addEventListener('beforeunload', beforeUnloadHandler)
    dirty.set(isDirty)
    if (!app.hasAuthToken()) {
      m.route.set('/logout')
      return
    }
    state.errors = []
    state.file = ''
    state.savedContent = null
    state.loadedFile = null
    model.item = {}
    const onListLoaded = () => {
      const first = sortedList()[0]
      if (first) {
        selectFile(first.file)
      }
      m.redraw()
    }
    model.loadList().then(onListLoaded, onListLoaded)
  }

  const onCreateComponent = () => {
    window.addEventListener('beforeunload', beforeUnloadHandler)
    dirty.set(isDirty)
    if (unbindSaveShortcut) {
      unbindSaveShortcut()
      unbindSaveShortcut = null
    }
    if (canEdit()) {
      unbindSaveShortcut = bindSaveShortcut(document, {
        onSave: () => onSaveButtonClicked({redraw: false}),
        onSaveAndClose: () => onSaveAndCloseButtonClicked({redraw: false}),
      })
    }
    app.makeAppScrollable()
    ensureEditor()
    showLoadedFile()
  }

  const onUpdateComponent = () => {
    ensureEditor()
    showLoadedFile()
  }

  const onRemoveComponent = () => {
    window.removeEventListener('beforeunload', beforeUnloadHandler)
    dirty.clear()
    if (unbindSaveShortcut) {
      unbindSaveShortcut()
      unbindSaveShortcut = null
    }
    destroyEditor()
    app.makeIframeScrollable()
  }

  const onCancelButtonClicked = () => {
    if (!confirmDiscard()) {
      return
    }
    dirty.clear()
    m.route.set('/')
  }

  const onSaveButtonClicked = (event) => {
    event.redraw = false
    if (!canEdit() || !editor) {
      return Promise.resolve()
    }
    model.item.content = editor.getValue()
    return model.save()
      .then(() => {
        state.errors = []
        state.savedContent = editor.getValue()
        addSuccess(t('Template was successfully saved.'))
        m.redraw()
      })
      .catch(({code, response}) => {
        if (code === 422) {
          state.errors = response
        }
        m.redraw()
        throw {code, response}
      })
  }

  const onSaveAndCloseButtonClicked = (event) => {
    event.redraw = false
    return onSaveButtonClicked(event)
      .then(() => {
        dirty.clear()
        m.route.set('/')
      })
      .catch(() => {
        // the errors stay on screen, the template stays open
      })
  }

  // Save without leaving the view: applying a site template writes the editor
  // first, so the rollout uses what the admin just typed.
  const saveCurrent = () => onSaveButtonClicked({redraw: false})

  const content = () => {
    if (model.isLoading && model.list.length === 0) {
      return m('p', t('Loading') + '...')
    }
    if (model.list.length === 0) {
      // the explanation stays what the kind is about: an empty directory is
      // exactly when the admin needs to know what belongs into it
      return [m('p', missingHint()), hint(model.item)]
    }
    return m(Fieldset, {legend: t('Template')},
      m(Select, {
        id: 'templateFile-' + kind,
        wrap: 'div.row',
        label: editorLabel(),
        value: state.file,
        options: sortedList().map((item) => ({value: item.file, label: item.file})),
        error: errorFor(state.errors, 'fileName'),
        onchange: (file) => {
          if (confirmDiscard()) {
            dirty.clear()
            selectFile(file).then(() => m.redraw())
          } else {
            m.redraw() // put the select back on the file that is still open
          }
        },
      }),
      // the page list carries the title, it is the one thing that is edited in
      // a place other than the document itself
      kind === 'page' && model.item.title
        ? m(InputText, {
          wrap: 'div.row',
          label: t('Title'),
          value: model.item.title,
          disabled: true,
        })
        : null,
      hint(model.item),
      m('.row',
        m('label', {style: {width: '100%'}}, t('HTML')),
        m('div', {id: editorId(), style: {width: '100%'}})
      )
    )
  }

  const view = () => {
    const editable = canEdit()
    return m('div.main',
      m('.content',
        m('h1', heading()),
        content(),
        m(FormErrors, {errors: state.errors.filter((error) => error.source.pointer !== 'fileName')}),
        m(ButtonGroup, {wrap: 'div.row.text-right'},
          m(ButtonSecondary, {onclick: onCancelButtonClicked},
            m(Icon, {name: editable ? 'clear' : 'back'}),
            m('span', t(editable ? 'Cancel' : 'Back'))),
          extraButtons
            ? extraButtons({isDirty, confirmDiscard, destroyEditor})
            : null,
          m(ButtonPrimary, {
            onclick: onSaveButtonClicked,
            disabled: !editable,
            title: editable ? t('Save') + ' (' + saveShortcutLabel() + ')' : t('Save') + ' – ' + t('Your role does not allow this.'),
          }, m(Icon, {name: 'save'}), m('span', t('Save'))),
          m(ButtonPrimary, {
            onclick: onSaveAndCloseButtonClicked,
            disabled: !editable,
            title: editable ? t('Save & Close') + ' (' + saveAndCloseShortcutLabel() + ')' : t('Save & Close') + ' – ' + t('Your role does not allow this.'),
          }, m(Icon, {name: 'check'}), m('span', t('Save & Close')))
        )
      )
    )
  }

  return {
    state,
    view,
    selectFile,
    confirmDiscard,
    isDirty,
    getEditor: () => editor,
    setEditor: (value) => { editor = value },
    onSaveButtonClicked,
    onSaveAndCloseButtonClicked,
    saveCurrent,
    onCancelButtonClicked,
    onInitComponent,
    onCreateComponent,
    onUpdateComponent,
    onRemoveComponent,
  }
}
