import {t} from '../model/i18n.js'
import {SiteTemplateModel} from '../model/SiteTemplateModel.js'
import {createTemplateEditor} from '../component/TemplateEditor.js'
import {ButtonDanger} from '../component/ButtonDanger.js'
import {ButtonSecondary} from '../component/ButtonSecondary.js'
import {ButtonGroup} from '../component/ButtonGroup.js'
import {Icon} from '../component/Icon.js'
import {addDanger, addSuccess} from '../state/actions.js'
import {auth} from '../lib/auth.js'

// The site template view is the shared template editor plus the two things only
// a site template has: the marker hint and the preview-and-apply flow.

const state = {
  preview: null,
}

const closePreview = () => {
  state.preview = null
  m.redraw()
}

const editor = createTemplateEditor({
  kind: 'site',
  model: SiteTemplateModel,
  mode: 'ace/mode/html',
  heading: () => t('Shared content'),
  editorLabel: () => t('Template'),
  missingHint: () => t('No templates found. Create HTML files under jaco-data/templates/site/ to get started.'),
  canEdit: () => auth.can('admin'),
  hint: (item) => m('p', t('The marker must match the template file: data-jaco-template="{file}".', {
    file: item.file ?? '',
  })),
  extraButtons: () => m(ButtonDanger, {
    onclick: onApplyClicked,
    title: t('Preview & apply') + ' – ' + t('Every page that is rewritten is backed up first.'),
  }, m(Icon, {name: 'copy'}), m('span', t('Preview & apply'))),
})

const confirmApply = () => {
  state.preview = null
  // an admin saves the editor first, so the rollout uses the new markup; an
  // editor applies the stored template and may not write anyway
  const saveFirst = auth.can('admin') && editor.isDirty()
    ? editor.saveCurrent()
    : Promise.resolve()
  return saveFirst
    .then(() => SiteTemplateModel.apply())
    .then((data) => {
      addSuccess(t('Template applied to {files} pages ({elements} elements).', {
        files: data.appliedFiles,
        elements: data.appliedElements,
      }))
      if (data.failed.length > 0) {
        addDanger(t('Some pages could not be updated: {files}', {files: data.failed.join(', ')}))
      }
      m.redraw()
      return data
    })
    .catch(() => {
      m.redraw()
    })
}

const onApplyClicked = () => {
  SiteTemplateModel.preview()
    .then((preview) => {
      state.preview = preview
      m.redraw()
    })
    .catch(() => {
      addDanger(t('Could not preview the template.'))
      m.redraw()
    })
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
    auth.can('admin')
      ? m('p', t('The current editor content is saved before applying.'))
      : m('p', t('The template is applied as it is stored.')),
    m('p', t('Every page that is rewritten is backed up first.')),
    m(ButtonGroup, {wrap: 'div.row.text-right'},
      m(ButtonSecondary, {onclick: closePreview}, m(Icon, {name: 'clear'}), m('span', t('Cancel'))),
      m(ButtonDanger, {onclick: confirmApply, disabled: files === 0}, m(Icon, {name: 'check'}), m('span', t('Save & apply to all pages')))
    )
  ))
}

export const state_ = state

export const TemplateView = {
  oninit: editor.onInitComponent,
  oncreate: editor.onCreateComponent,
  onupdate: editor.onUpdateComponent,
  onremove: editor.onRemoveComponent,
  view: () => [
    editor.view(),
    previewDialog(),
  ],
}

export {editor as siteTemplateEditor}
