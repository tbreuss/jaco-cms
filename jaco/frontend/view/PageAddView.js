import {api} from '../service/api.js'
import {addSuccess} from '../state/actions.js'
import {iframe} from '../lib/iframe.js'
import {t} from '../model/i18n.js'
import {ModalMedium} from '../component/ModalMedium.js'
import {Fieldset} from '../component/Fieldset.js'
import {ButtonGroup} from '../component/ButtonGroup.js'
import {ButtonPrimary} from '../component/ButtonPrimary.js'
import {ButtonSecondary} from '../component/ButtonSecondary.js'
import {InputText} from '../component/InputText.js'
import {Select} from '../component/Select.js'
import {FormErrors} from '../component/FormErrors.js'
import {PageTemplateModel} from '../model/PageTemplateModel.js'
import {app} from '../lib/app.js'

const state = {
  fileName: '',
  pageTitle: '',
  pageTemplate: '',
  errors: []
}

const onSaveButtonClicked = (e) => {
  e.redraw = false
  api.post('action=page/create', {
    background: true, // no redraw
    body: state,
    withCredentials: false
  })
    .then(() => {
      addSuccess(t('Page was successfully added.'))
      m.route.set('/')
    })
    .catch(({code, response}) => {
      if (code === 422) {
        state.errors = response.errors
      }
      m.redraw()
    })
}

const onCancelButtonClicked = (e) => {
  e.redraw = false
  m.route.set('/')
}

export const PageAddView = {
  oninit: () => {
    if (!app.hasAuthToken()) {
      m.route.set('/logout')
    }
    // i.e. if page was reloaded with given route
    if (!iframe.hasElement()) {
      m.route.set('/')
    }
    state.fileName = iframe.fileName()
    state.pageTitle = iframe.pageTitle()
    state.pageTemplate = ''
    state.errors = []

    PageTemplateModel.loadList()
  },
  view: () => m(ModalMedium, {title: t('Add page')},
    m(Fieldset, {legend: t('New page')},
      m(Select, {
        id: 'pageTemplate',
        wrap: 'div.row',
        label: t('Page template'),
        options: PageTemplateModel.list.map((item) => {
          return {value: item.file, label: item.title}
        }),
        prompt: t('Select an option'),
        onchange: (selectedItem) => {
          state.pageTemplate = selectedItem
        }
      }),
      m(InputText, {
        id: 'fileName',
        wrap: 'div.row',
        label: t('Filename'),
        value: state.fileName,
        oninput: (value) => {
          state.fileName = value
        }
      }),
      m(InputText, {
        id: 'pageTitle',
        wrap: 'div.row',
        label: t('Page title'),
        value: state.pageTitle,
        oninput: (value) => {
          state.pageTitle = value
        }
      }),
      m(ButtonGroup, {wrap: 'div.row.text-right'},
        m(ButtonSecondary, {onclick: onCancelButtonClicked}, t('Cancel')),
        m(ButtonPrimary, {onclick: onSaveButtonClicked}, t('OK'))
      ),
    ),
    m(FormErrors, {errors: state.errors})
  )
}
