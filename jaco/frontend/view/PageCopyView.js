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
import {FormErrors, errorFor} from '../component/FormErrors.js'
import {app} from '../lib/app.js'

const state = {
  srcFileName: '',
  srcPageTitle: '',
  dstFileName: '',
  dstPageTitle: '',
  errors: []
}

const fieldPointers = ['srcFileName', 'dstFileName', 'dstPageTitle']

const save = (e) => {
  e.redraw = false
  api.post('page/copy', {}, {
    background: true, // no redraw
    body: {
      srcFileName: state.srcFileName,
      dstFileName: state.dstFileName,
      dstPageTitle: state.dstPageTitle,
    },
    withCredentials: false
  })
    .then(() => {
      addSuccess(t('Page was successfully copied.'))
      m.route.set('/')
    })
    .catch(({code, response}) => {
      if (code === 422) {
        state.errors = response
      }
      m.redraw()
    })
}

const cancel = (e) => {
  e.redraw = false
  m.route.set('/')
}

export const PageCopyView = {
  oninit: () => {
    if (!app.hasAuthToken()) {
      m.route.set('/logout')
    }
    // i.e. if page was reloaded with given route
    if (!iframe.hasElement()) {
      m.route.set('/')
    }
    state.srcFileName = state.dstFileName = iframe.fileName()
    state.srcPageTitle = state.dstPageTitle = iframe.pageTitle()
    state.errors = []
  },
  view: () => {
    return m(ModalMedium, {title: t('Copy page')},
      m(Fieldset, {legend: t('Current page')},
        m(InputText, {
          id: 'srcFileName',
          wrap: 'div.row',
          label: t('Filename'),
          value: state.srcFileName,
          error: errorFor(state.errors, 'srcFileName'),
          disabled: true
        }),
        m(InputText, {
          wrap: 'div.row',
          label: t('Page title'),
          value: state.srcPageTitle,
          disabled: true
        })
      ),
      m(Fieldset, {legend: t('New page')},
        m(InputText, {
          id: 'dstFileName',
          wrap: 'div.row',
          label: t('Filename'),
          value: state.dstFileName,
          error: errorFor(state.errors, 'dstFileName'),
          oninput: (value) => {
            state.dstFileName = value
          }
        }),
        m(InputText, {
          id: 'dstPageTitle',
          wrap: 'div.row',
          label: t('Page title'),
          value: state.dstPageTitle,
          error: errorFor(state.errors, 'dstPageTitle'),
          oninput: (value) => {
            state.dstPageTitle = value
          }
        }),
        m(ButtonGroup, {wrap: 'div.row.text-right'},
          m(ButtonSecondary, {onclick: cancel}, t('Cancel')),
          m(ButtonPrimary, {onclick: save}, t('OK'))
        )
      ),
      m(FormErrors, {errors: state.errors.filter((error) => !fieldPointers.includes(error.source.pointer))})
    )
  }
}
