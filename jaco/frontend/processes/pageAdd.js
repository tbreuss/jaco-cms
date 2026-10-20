import {addSuccess} from '../state/actions.js'
import {iframe} from '../lib/iframe.js'
import {createPage, continueAfterCreate} from '../lib/pageCreate.js'
import {t} from '../model/i18n.js'
import {PageTemplateModel} from '../model/PageTemplateModel.js'
import {Fieldset} from '../component/Fieldset.js'
import {Select} from '../component/Select.js'
import {InputText} from '../component/InputText.js'
import {errorFor} from '../component/FormErrors.js'

const blank = (pointer, label) => [{
  source: {pointer},
  title: 'Validation Error',
  detail: t('{value} cannot be blank.', {value: label}),
}]

const templateStep = {
  id: 'template',
  titleKey: 'Page template',
  pointers: ['pageTemplate'],
  view: (data, set, state) => m(Fieldset, {legend: t('New page')},
    m(Select, {
      id: 'pageTemplate',
      wrap: 'div.row',
      label: t('Page template'),
      options: PageTemplateModel.list.map((item) => ({value: item.file, label: item.title})),
      prompt: t('Select an option'),
      value: data.pageTemplate,
      error: errorFor(state.errors, 'pageTemplate'),
      onchange: (value) => set('pageTemplate', value),
    })
  ),
  validate: (data) => data.pageTemplate.trim() === ''
    ? blank('pageTemplate', t('Page template'))
    : [],
}

const detailsStep = {
  id: 'details',
  titleKey: 'New page',
  pointers: ['fileName', 'pageTitle'],
  view: (data, set, state) => m(Fieldset, {legend: t('New page')},
    m(InputText, {
      id: 'fileName',
      wrap: 'div.row',
      label: t('Filename'),
      value: data.fileName,
      error: errorFor(state.errors, 'fileName'),
      oninput: (value) => set('fileName', value),
    }),
    m(InputText, {
      id: 'pageTitle',
      wrap: 'div.row',
      label: t('Page title'),
      value: data.pageTitle,
      error: errorFor(state.errors, 'pageTitle'),
      oninput: (value) => set('pageTitle', value),
    })
  ),
  validate: (data) => [
    ...(data.fileName.trim() === '' ? blank('fileName', t('Filename')) : []),
    ...(data.pageTitle.trim() === '' ? blank('pageTitle', t('Page title')) : []),
  ],
}

const reviewStep = {
  id: 'review',
  titleKey: 'Confirm',
  pointers: [],
  view: (data) => m(Fieldset, {legend: t('Confirm')},
    m('p', t('Page template') + ': ' + (data.pageTemplate || '—')),
    m('p', t('Filename') + ': ' + (data.fileName || '—')),
    m('p', t('Page title') + ': ' + (data.pageTitle || '—'))
  ),
}

export const pageAddProcess = {
  id: 'page.add',
  titleKey: 'Add page',
  finishKey: 'Add page',
  initialData: () => ({
    pageTemplate: '',
    fileName: iframe.hasElement() ? iframe.fileName() : '',
    pageTitle: iframe.hasElement() ? iframe.pageTitle() : '',
  }),
  init: () => PageTemplateModel.loadList().then(() => m.redraw()),
  steps: [templateStep, detailsStep, reviewStep],
  stepForErrors: (errors, fallback) => {
    const pointer = errors[0]?.source?.pointer
    const index = pointer === 'pageTemplate' ? 0 : pointer === 'fileName' || pointer === 'pageTitle' ? 1 : -1
    return index === -1 ? fallback : index
  },
  commit: (data) => createPage(data).then(() => {
    addSuccess(t('Page was successfully added.'))
    continueAfterCreate(data.fileName)
  }),
}
