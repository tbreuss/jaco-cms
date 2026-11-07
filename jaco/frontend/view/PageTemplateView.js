import {t} from '../model/i18n.js'
import {PageTemplateEditModel} from '../model/PageContentTemplateModel.js'
import {createTemplateEditor} from '../component/TemplateEditor.js'
import {auth} from '../lib/auth.js'

// Page templates are whole documents. The page manager creates pages from them
// and fills the title, so the listing shows it - read-only, because the title
// is edited in the document itself.
const editor = createTemplateEditor({
  kind: 'page',
  model: PageTemplateEditModel,
  mode: 'ace/mode/html',
  heading: () => t('Page templates'),
  editorLabel: () => t('Page template'),
  missingHint: () => t('No page templates found. Create HTML documents under jaco-data/templates/page/ to get started.'),
  canEdit: () => auth.can('admin'),
  hint: () => m('p', t('A page template is a complete HTML document; a new page is created from it.')),
})

export const PageTemplateView = {
  oninit: editor.onInitComponent,
  oncreate: editor.onCreateComponent,
  onupdate: editor.onUpdateComponent,
  onremove: editor.onRemoveComponent,
  view: editor.view,
}
