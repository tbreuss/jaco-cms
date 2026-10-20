import {api} from '../service/api.js'
import {addSuccess} from '../state/actions.js'
import {iframe} from '../lib/iframe.js'
import {t} from '../model/i18n.js'
import {ModalMedium} from '../component/ModalMedium.js'
import {Fieldset} from '../component/Fieldset.js'
import {ButtonGroup} from '../component/ButtonGroup.js'
import {ButtonPrimary} from '../component/ButtonPrimary.js'
import {ButtonSecondary} from '../component/ButtonSecondary.js'
import {Icon} from '../component/Icon.js'
import {InputText} from '../component/InputText.js'
import {Select} from '../component/Select.js'
import {FormErrors, errorFor} from '../component/FormErrors.js'
import {PageTemplateModel} from '../model/PageTemplateModel.js'
import {app} from '../lib/app.js'
import {auth} from '../lib/auth.js'

const state = {
  fileName: '',
  pageTitle: '',
  pageTemplate: '',
  errors: []
}

const fieldPointers = ['fileName', 'pageTemplate', 'pageTitle']

const onSaveButtonClicked = (e) => {
  e.redraw = false
  api.post('page/create', {}, {
    background: true, // no redraw
    body: {
      fileName: state.fileName,
      pageTitle: state.pageTitle,
      pageTemplate: state.pageTemplate,
    },
    withCredentials: false
  })
    .then(() => {
      addSuccess(t('Page was successfully added.'))
      // continue the "add page" process directly in the inline editor instead
      // of sending the user back to the pages list
      let frame = null
      try {
        if (!iframe.hasElement()) {
          m.route.set('/pages')
          return
        }
        frame = iframe.getElement()
      } catch (e) {
        m.route.set('/pages')
        return
      }
      frame.onload = () => {
        frame.onload = null
        frame.onerror = null
        m.route.set('/page-edit')
      }
      frame.onerror = () => {
        frame.onload = null
        frame.onerror = null
        m.route.set('/pages')
      }
      iframe.load('/' + state.fileName)
    })
    .catch(({code, response}) => {
      if (code === 422) {
        state.errors = response
      }
      m.redraw()
    })
}

const onCancelButtonClicked = (e) => {
  e.redraw = false
  m.route.set('/pages')
}

export const PageAddView = {
  oninit: () => {
    if (!app.hasAuthToken()) {
      m.route.set('/logout')
      return
    }
    // creating a page needs editor; a stale link from a viewer goes back to the site
    if (!auth.can('editor')) {
      m.route.set('/')
      return
    }
    // i.e. if page was reloaded with given route
    if (!iframe.hasElement()) {
      m.route.set('/')
      return
    }
    // a new page starts empty: the name and the title of the page in the
    // preview belong to that page, not to the one about to be created
    state.fileName = ''
    state.pageTitle = ''
    state.pageTemplate = ''
    state.errors = []

    PageTemplateModel.loadList()
  },
  view: () => m(ModalMedium, {title: t('Add page'), closeRoute: '/pages'},
    m(Fieldset, {legend: t('New page')},
      m(Select, {
        id: 'pageTemplate',
        wrap: 'div.row',
        label: t('Page template'),
        options: PageTemplateModel.list.map((item) => {
          return {value: item.file, label: item.title}
        }),
        prompt: t('Select an option'),
        error: errorFor(state.errors, 'pageTemplate'),
        onchange: (selectedItem) => {
          state.pageTemplate = selectedItem
        }
      }),
      m(InputText, {
        id: 'fileName',
        wrap: 'div.row',
        label: t('Filename'),
        placeholder: t('e.g. contact.html'),
        value: state.fileName,
        error: errorFor(state.errors, 'fileName'),
        oninput: (value) => {
          state.fileName = value
        }
      }),
      m(InputText, {
        id: 'pageTitle',
        wrap: 'div.row',
        label: t('Page title'),
        placeholder: t('e.g. Contact'),
        value: state.pageTitle,
        error: errorFor(state.errors, 'pageTitle'),
        oninput: (value) => {
          state.pageTitle = value
        }
      }),
      m(ButtonGroup, {wrap: 'div.row.text-right'},
        m(ButtonSecondary, {onclick: onCancelButtonClicked}, m(Icon, {name: 'clear'}), m('span', t('Cancel'))),
        m(ButtonPrimary, {onclick: onSaveButtonClicked}, m(Icon, {name: 'check'}), m('span', t('Add page')))
      ),
    ),
    m(FormErrors, {errors: state.errors.filter((error) => !fieldPointers.includes(error.source.pointer))})
  )
}
