import {t} from '../../model/i18n.js'
import {PageEditModel} from '../../model/PageEditModel.js'
import {ButtonSecondary} from '../../component/ButtonSecondary.js'
import {Fieldset} from '../../component/Fieldset.js'
import {InputText} from '../../component/InputText.js'
import {ButtonGroup} from '../../component/ButtonGroup.js'
import {addSuccess} from '../../state/actions.js'
import {ButtonPrimary} from '../../component/ButtonPrimary.js'
import {FormErrors} from '../../component/FormErrors.js'
import {app} from '../../lib/app.js'
import {bindSaveShortcut, saveAndCloseShortcutLabel, saveShortcutLabel} from '../../lib/shortcuts.js'

let editor = undefined
let unbindSaveShortcut = null

const state = {
  errors: [],
  initialized: false
}

const onCancelButtonClicked = () => {
  m.route.set('/pages')
}

const onCreateComponent = () => {
  app.makeAppScrollable()

  editor = ace.edit('ace', {
    theme: 'ace/theme/github',
    mode: 'ace/mode/html',
    maxLines: Infinity,  // this is going to be very slow on large documents
    wrap: true,   // wrap text to view
    indentedSoftWrap: false,
    behavioursEnabled: false, // disable autopairing of brackets and tags
    showLineNumbers: false, // hide the gutter
    readOnly: false
  })
  editor.resize(false)
  // Ace swallows keydown inside its textarea, so the document listener
  // alone would miss shortcuts with the focus in the editor
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

const onInitComponent = (vnode) => {
  if (!app.hasAuthToken()) {
    m.route.set('/logout')
  }
  PageEditModel.load(vnode.attrs.file)
  state.errors = []
  state.initialized = false
  if (unbindSaveShortcut) {
    unbindSaveShortcut()
  }
  unbindSaveShortcut = bindSaveShortcut(document, {
    onSave: () => onSaveButtonClicked({redraw: false}),
    onSaveAndClose: () => onSaveAndCloseButtonClicked({redraw: false}),
  })
}

const onSaveButtonClicked = (e) => {
  e.redraw = false
  PageEditModel.item.content = editor.getValue()
  return PageEditModel.save()
    .then(() => {
      addSuccess(t('Page was successfully saved.'))
    })
    .catch(({code, response}) => {
      if (code === 422) {
        state.errors = response
        m.redraw()
      }
    })
}

const onSaveAndCloseButtonClicked = (e) => {
  onSaveButtonClicked(e)
    .then(() => {
      m.route.set('/pages')
    })
}

const onRemoveComponent = () => {
  if (unbindSaveShortcut) {
    unbindSaveShortcut()
    unbindSaveShortcut = null
  }
  app.makeIframeScrollable()
}

const onUpdateComponent = () => {
  if (!state.initialized) {
    editor.setValue(PageEditModel.item.content, 1) // set cursor at the end
    state.initialized = true
  }
}

export const PageEditView = {
  oninit: onInitComponent,
  oncreate: onCreateComponent,
  onupdate: onUpdateComponent,
  onremove: onRemoveComponent,
  view: () => m('div.main',
    m('.content',
      m('h1', t('Edit page'), ': ', PageEditModel.item.title ?? ''),
      m(Fieldset, {legend: t('Current page')},
        m(InputText, {
          wrap: 'div.row',
          label: t('Filename'),
          value: PageEditModel.item.file ?? '',
          disabled: true
        }),
        m(InputText, {
          wrap: 'div.row',
          label: t('Page title'),
          value: PageEditModel.item.title ?? '',
          disabled: true
        }),
        m(InputText, {
          wrap: 'div.row',
          label: t('Created at'),
          value: PageEditModel.item.created ? PageEditModel.item.created.toDateTime() : '',
          disabled: true
        }),
        m('.row',
          m('label', {style: {width: '100%'}}, t('HTML')),
          m('div#ace', {style: {width: '100%'}})
        ),
        m(ButtonGroup, {wrap: 'div.row.text-right'},
          m(ButtonPrimary, {onclick: onSaveButtonClicked, title: t('Save') + ' (' + saveShortcutLabel() + ')'}, t('Save')),
          m(ButtonPrimary, {onclick: onSaveAndCloseButtonClicked, title: t('Save & Close') + ' (' + saveAndCloseShortcutLabel() + ')'}, t('Save & Close')),
          m(ButtonSecondary, {onclick: onCancelButtonClicked}, t('Cancel'))
        )
      ),
      m(FormErrors, {errors: state.errors}),
    )
  )
}
