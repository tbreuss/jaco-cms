import {t} from '../model/i18n.js'
import {CssEditModel} from '../model/CssEditModel.js'
import {ButtonSecondary} from '../component/ButtonSecondary.js'
import {Fieldset} from '../component/Fieldset.js'
import {InputText} from '../component/InputText.js'
import {Select} from '../component/Select.js'
import {ButtonGroup} from '../component/ButtonGroup.js'
import {Icon} from '../component/Icon.js'
import {addSuccess} from '../state/actions.js'
import {ButtonPrimary} from '../component/ButtonPrimary.js'
import {FormErrors, errorFor} from '../component/FormErrors.js'
import {app} from '../lib/app.js'
import {bindSaveShortcut, saveAndCloseShortcutLabel, saveShortcutLabel} from '../lib/shortcuts.js'

let editor = undefined
let unbindSaveShortcut = null

const state = {
  errors: [],
  loadedFile: null,
  file: ''
}

const fieldPointers = ['fileName']

const onCancelButtonClicked = () => {
  m.route.set('/')
}

const onCreateComponent = () => {
  app.makeAppScrollable()

  editor = ace.edit('ace', {
    theme: 'ace/theme/github',
    mode: 'ace/mode/css',
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

const onInitComponent = () => {
  if (!app.hasAuthToken()) {
    m.route.set('/logout')
  }
  state.errors = []
  state.loadedFile = null
  state.file = ''
  if (unbindSaveShortcut) {
    unbindSaveShortcut()
  }
  unbindSaveShortcut = bindSaveShortcut(document, {
    onSave: () => onSaveButtonClicked({redraw: false}),
    onSaveAndClose: () => onSaveAndCloseButtonClicked({redraw: false}),
  })
  CssEditModel.loadList().then(() => {
    if (CssEditModel.list.length > 0) {
      selectFile(CssEditModel.list[0].file)
    }
    m.redraw()
  })
}

const selectFile = (file) => {
  if (!file) {
    return
  }
  state.file = file
  CssEditModel.load(file)
}

const onSaveButtonClicked = (e) => {
  e.redraw = false
  CssEditModel.item.content = editor.getValue()
  return CssEditModel.save()
    .then(() => {
      addSuccess(t('CSS was successfully saved.'))
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
      m.route.set('/')
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
  const loaded = CssEditModel.item.file
  // render only the selected file, late responses of earlier selections are ignored
  if (loaded && loaded !== state.loadedFile && loaded === state.file) {
    editor.setValue(CssEditModel.item.content, 1) // set cursor at the end
    state.loadedFile = loaded
  }
}

export const CssEditView = {
  oninit: onInitComponent,
  oncreate: onCreateComponent,
  onupdate: onUpdateComponent,
  onremove: onRemoveComponent,
  view: () => m('div.main',
    m('.content',
      m('h1', t('Edit CSS'), CssEditModel.item.file ? ': ' + CssEditModel.item.file : ''),
      CssEditModel.list.length === 0
        ? m('p', t('No stylesheets found.'), ' ', t('Stylesheets are .css files in the web root.'))
        : [m(Fieldset, {legend: t('Current stylesheet')},
          m(Select, {
            id: 'cssFile',
            wrap: 'div.row',
            label: t('CSS file'),
            value: state.file,
            options: CssEditModel.list.map((item) => {
              return {value: item.file, label: item.file}
            }),
            error: errorFor(state.errors, 'fileName'),
            onchange: (selectedItem) => {
              selectFile(selectedItem)
            }
          }),
          m(InputText, {
            wrap: 'div.row',
            label: t('Created at'),
            value: CssEditModel.item.created ? CssEditModel.item.created.toDateTime() : '',
            title: CssEditModel.item.created ? CssEditModel.item.created.toDateTimeFull() : '',
            disabled: true
          }),
          m('.row',
            m('label', {style: {width: '100%'}}, t('CSS')),
            m('div#ace', {style: {width: '100%'}})
          ),
          m(ButtonGroup, {wrap: 'div.row.text-right'},
            m(ButtonSecondary, {onclick: onCancelButtonClicked}, m(Icon, {name: 'clear'}), m('span', t('Cancel'))),
            m(ButtonPrimary, {onclick: onSaveButtonClicked, title: t('Save') + ' (' + saveShortcutLabel() + ')'}, m(Icon, {name: 'save'}), m('span', t('Save'))),
            m(ButtonPrimary, {onclick: onSaveAndCloseButtonClicked, title: t('Save & Close') + ' (' + saveAndCloseShortcutLabel() + ')'}, m(Icon, {name: 'check'}), m('span', t('Save & Close')))
          )
          ),
          m(FormErrors, {errors: state.errors.filter((error) => !fieldPointers.includes(error.source.pointer))}),
        ]
    )
  )
}
