import {t} from '../model/i18n.js'
import {ContentTemplateEditModel} from '../model/PageContentTemplateModel.js'
import {createTemplateEditor} from '../component/TemplateEditor.js'
import {auth} from '../lib/auth.js'

// A content template is one block: the editor inserts it into a page through a
// repeater or a slot, so it needs exactly one root element and the data-jaco
// names of the contract. The server checks both on save.
const editor = createTemplateEditor({
  kind: 'content',
  model: ContentTemplateEditModel,
  mode: 'ace/mode/html',
  heading: () => t('Content templates'),
  editorLabel: () => t('Content template'),
  missingHint: () => t('No content templates found. Create HTML fragments under jaco-data/templates/content/ to get started.'),
  canEdit: () => auth.can('admin'),
  hint: () => m('p', t('A content template is one block with exactly one root element, inserted through repeater or slot.')),
})

export const ContentTemplateView = {
  oninit: editor.onInitComponent,
  oncreate: editor.onCreateComponent,
  onupdate: editor.onUpdateComponent,
  onremove: editor.onRemoveComponent,
  view: editor.view,
}
