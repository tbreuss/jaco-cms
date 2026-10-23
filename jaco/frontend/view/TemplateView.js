import {t} from '../model/i18n.js'
import {SiteTemplateModel} from '../model/SiteTemplateModel.js'
import {ButtonSecondary} from '../component/ButtonSecondary.js'
import {ButtonDanger} from '../component/ButtonDanger.js'
import {ButtonPrimary} from '../component/ButtonPrimary.js'
import {ButtonGroup} from '../component/ButtonGroup.js'
import {Fieldset} from '../component/Fieldset.js'
import {FormErrors, errorFor} from '../component/FormErrors.js'
import {Icon} from '../component/Icon.js'
import {InputText} from '../component/InputText.js'
import {Select} from '../component/Select.js'
import {addDanger, addSuccess} from '../state/actions.js'
import {app} from '../lib/app.js'
import {auth} from '../lib/auth.js'
import {bindSaveShortcut, saveAndCloseShortcutLabel, saveShortcutLabel} from '../lib/shortcuts.js'
import {aceThemeName, watchAceTheme} from '../lib/aceTheme.js'
import {dirty} from '../lib/dirty.js'

let editor = undefined
let unbindSaveShortcut = null
let unwatchAceTheme = null

export const state = {
  errors: [],
  file: '',
  savedContent: null,
  initialized: false,
  preview: null,
}

const isDirty = () => editor && state.savedContent !== null && editor.getValue() !== state.savedContent

const beforeUnloadHandler = (event) => {
  if (isDirty()) {
    event.preventDefault()
    event.returnValue = ''
  }
}

const ensureEditor = () => {
  if (editor || !document.getElementById('ace')) {
    return
  }
  const canEdit = auth.can('admin')
  editor = ace.edit('ace', {
    theme: aceThemeName(),
    mode: 'ace/mode/html',
    maxLines: Infinity,
    wrap: true,
    indentedSoftWrap: false,
    behavioursEnabled: false,
    showLineNumbers: false,
    readOnly: !canEdit,
  })
  editor.resize(false)
  if (SiteTemplateModel.item.content !== undefined) {
    editor.setValue(SiteTemplateModel.item.content, 1)
  }
  if (canEdit) {
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

const selectFile = (file) => {
  if (!file) {
    return
  }
  state.file = file
  SiteTemplateModel.load(file).then(() => {
    state.savedContent = SiteTemplateModel.item.content
    state.initialized = true
    if (editor) {
      editor.setValue(SiteTemplateModel.item.content, 1)
    }
    m.redraw()
  })
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
  state.initialized = false
  state.preview = null
  SiteTemplateModel.item = {}
  SiteTemplateModel.loadList().then(() => {
    if (SiteTemplateModel.list.length > 0) {
      selectFile(SiteTemplateModel.list[0].file)
    } else {
      state.initialized = true
    }
    m.redraw()
  })
  if (auth.can('admin')) {
    unbindSaveShortcut = bindSaveShortcut(document, {
      onSave: () => onSaveButtonClicked({redraw: false}),
      onSaveAndClose: () => onSaveAndCloseButtonClicked({redraw: false}),
    })
  }
}

const onCreateComponent = () => {
  app.makeAppScrollable()
  ensureEditor()
}

const onUpdateComponent = () => {
  if (SiteTemplateModel.list.length > 0 && SiteTemplateModel.item.file) {
    ensureEditor()
  }
}

const onRemoveComponent = () => {
  window.removeEventListener('beforeunload', beforeUnloadHandler)
  dirty.clear()
  if (unbindSaveShortcut) {
    unbindSaveShortcut()
    unbindSaveShortcut = null
  }
  if (editor) {
    editor.destroy()
    editor = undefined
  }
  if (unwatchAceTheme) {
    unwatchAceTheme()
    unwatchAceTheme = null
  }
  app.makeIframeScrollable()
}

const performSave = (applyToAll, closeAfterSave) => {
  if (!auth.can('admin') || !editor) {
    return Promise.resolve()
  }
  SiteTemplateModel.item.content = editor.getValue()
  return SiteTemplateModel.save(applyToAll)
    .then((data) => {
      state.errors = []
      state.savedContent = editor.getValue()
      state.preview = null
      if (applyToAll) {
        addSuccess(t('Template applied to {files} pages ({elements} elements).', {
          files: data.appliedFiles,
          elements: data.appliedElements,
        }))
        if (data.failed.length > 0) {
          addDanger(t('Some pages could not be updated: {files}', {files: data.failed.join(', ')}))
        }
      } else {
        addSuccess(t('Template was successfully saved.'))
      }
      if (closeAfterSave) {
        m.route.set('/')
      } else {
        m.redraw()
      }
      return data
    })
    .catch(({code, response}) => {
      if (code === 422) {
        state.errors = response
        m.redraw()
      }
      throw {code, response}
    })
}

const requestSave = (closeAfterSave) => {
  return performSave(false, closeAfterSave)
}

const requestApply = () => {
  if (!auth.can('admin') || !editor) {
    return Promise.resolve()
  }
  return SiteTemplateModel.preview()
    .then((preview) => {
      state.preview = preview
      m.redraw()
    })
    .catch(() => {
      addDanger(t('Could not preview the template.'))
      m.redraw()
    })
}

export const onSaveButtonClicked = (event) => {
  event.redraw = false
  return requestSave(false)
}

export const onApplyClicked = (event) => {
  event.redraw = false
  return requestApply()
}

const onSaveAndCloseButtonClicked = (event) => {
  event.redraw = false
  return requestSave(true)
}

const closePreview = () => {
  state.preview = null
  m.redraw()
}

const confirmApply = () => {
  state.preview = null
  return performSave(true, false)
}

const previewDialog = () => {
  if (!state.preview) {
    return null
  }
  const files = state.preview.files.length
  const elements = state.preview.elements
  return m('.modal', {
    tabindex: '-1',
    oncreate: ({dom}) => {
      dom.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          event.preventDefault()
          closePreview()
        }
      })
      dom.querySelector('button')?.focus()
    },
    onclick: (event) => {
      if (event.target === event.currentTarget) {
        closePreview()
      }
    }
  }, m('.modal-inner',
    m('h2', t('Apply template to all pages?')),
    files > 0
      ? m('div',
        m('p', t('{files} pages with {elements} elements will be replaced.', {
          files,
          elements,
        })),
        m('ul', state.preview.files.map((item) => m('li', item.file))),
      )
      : m('p', t('No elements with this marker were found.')),
    m('p', t('The current editor content is saved before applying.')),
    m('p', t('No backups are created when applying to all pages.')),
    m(ButtonGroup, {wrap: 'div.row.text-right'},
      m(ButtonSecondary, {onclick: closePreview}, m('span', t('Cancel'))),
      m(ButtonDanger, {onclick: confirmApply, disabled: files === 0}, m(Icon, {name: 'check'}), m('span', t('Apply to all')))
    )
  ))
}

const onCancelButtonClicked = () => {
  if (isDirty() && !confirm(t('Discard unsaved changes?', null, 'Discard unsaved changes?'))) {
    return
  }
  dirty.clear()
  m.route.set('/')
}

export const TemplateView = {
  oninit: onInitComponent,
  oncreate: onCreateComponent,
  onupdate: onUpdateComponent,
  onremove: onRemoveComponent,
  view: () => {
    const canEdit = auth.can('admin')
    return m('div.main',
      m('.content',
        m('h1', t('Site templates')),
        SiteTemplateModel.isLoading && SiteTemplateModel.list.length === 0
          ? m('p', t('Loading') + '...')
          : SiteTemplateModel.list.length === 0
            ? m('p', t('No templates found. Create HTML files under jaco/_template/site/ to get started.'))
            : m(Fieldset, {legend: t('Template')},
              m(Select, {
                id: 'templateFile',
                wrap: 'div.row',
                label: t('Template'),
                value: state.file,
                options: SiteTemplateModel.list.map((item) => ({value: item.file, label: item.name})),
                onchange: selectFile,
              }),
              m(InputText, {
                id: 'templateName',
                wrap: 'div.row',
                label: t('Name'),
                value: SiteTemplateModel.item.name ?? '',
                disabled: true,
              }),
              m('p', t('The marker must match the template name: data-jaco-template="{name}".', {
                name: SiteTemplateModel.item.name ?? '',
              })),
              m('.row',
                m('label', {style: {width: '100%'}}, t('HTML')),
                m('div#ace', {style: {width: '100%'}})
              ),
              m('p', t('No backups are created when applying to all pages.')),
              m(FormErrors, {errors: state.errors}),
              m(ButtonGroup, {wrap: 'div.row.text-right'},
                m(ButtonSecondary, {onclick: onCancelButtonClicked}, m(Icon, {name: 'clear'}), m('span', t('Cancel'))),
                m(ButtonPrimary, {
                  onclick: onSaveButtonClicked,
                  disabled: !canEdit,
                  title: canEdit ? t('Save') + ' (' + saveShortcutLabel() + ')' : t('Save') + ' – ' + t('Your role does not allow this.'),
                }, m(Icon, {name: 'save'}), m('span', t('Save'))),
                m(ButtonPrimary, {
                  onclick: onSaveAndCloseButtonClicked,
                  disabled: !canEdit,
                  title: canEdit ? t('Save & Close') + ' (' + saveAndCloseShortcutLabel() + ')' : t('Save & Close') + ' – ' + t('Your role does not allow this.'),
                }, m(Icon, {name: 'check'}), m('span', t('Save & Close'))),
                m(ButtonDanger, {
                  onclick: onApplyClicked,
                  disabled: !canEdit,
                  title: canEdit ? t('Apply to all pages') + ' – ' + t('No backups are created when applying to all pages.') : t('Apply to all pages') + ' – ' + t('Your role does not allow this.'),
                }, m(Icon, {name: 'copy'}), m('span', t('Apply to all pages')))
              )
            )
      ),
      previewDialog()
    )
  }
}
