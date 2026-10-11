import {t} from '../model/i18n.js'
import {CssEditModel} from '../model/CssEditModel.js'
import {ButtonSecondary} from '../component/ButtonSecondary.js'
import {Fieldset} from '../component/Fieldset.js'
import {InputText} from '../component/InputText.js'
import {Select} from '../component/Select.js'
import {ButtonGroup} from '../component/ButtonGroup.js'
import {addSuccess} from '../state/actions.js'
import {ButtonPrimary} from '../component/ButtonPrimary.js'
import {FormErrors} from '../component/FormErrors.js'
import {app} from '../lib/app.js'

let editor = undefined

const state = {
  errors: [],
  loadedFile: null,
  file: ''
}

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
}

const onInitComponent = () => {
  if (!app.hasAuthToken()) {
    m.route.set('/logout')
  }
  state.errors = []
  state.loadedFile = null
  state.file = ''
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
      m('h1', t('Edit CSS'), ': ', CssEditModel.item.file ?? ''),
      m(Fieldset, {legend: t('Current stylesheet')},
        m(Select, {
          id: 'cssFile',
          wrap: 'div.row',
          label: t('CSS file'),
          value: state.file,
          options: CssEditModel.list.map((item) => {
            return {value: item.file, label: item.file}
          }),
          onchange: (selectedItem) => {
            selectFile(selectedItem)
          }
        }),
        m(InputText, {
          wrap: 'div.row',
          label: t('Created at'),
          value: CssEditModel.item.created ? CssEditModel.item.created.toDateTime() : '',
          disabled: true
        }),
        m('.row',
          m('label', {style: {width: '100%'}}, t('CSS')),
          m('div#ace', {style: {width: '100%'}})
        ),
        m(ButtonGroup, {wrap: 'div.row.text-right'},
          m(ButtonPrimary, {onclick: onSaveButtonClicked}, t('Save')),
          m(ButtonPrimary, {onclick: onSaveAndCloseButtonClicked}, t('Save & Close')),
          m(ButtonSecondary, {onclick: onCancelButtonClicked}, t('Cancel'))
        )
      ),
      m(FormErrors, {errors: state.errors}),
    )
  )
}
