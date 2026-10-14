import {api} from '../service/api.js'
import {addSuccess} from '../state/actions.js'
import {iframe} from '../lib/iframe.js'
import {t} from '../model/i18n.js'
import {ModalMedium} from '../component/ModalMedium.js'
import {ButtonGroup} from '../component/ButtonGroup.js'
import {ButtonPrimary} from '../component/ButtonPrimary.js'
import {ButtonSecondary} from '../component/ButtonSecondary.js'
import {Icon} from '../component/Icon.js'
import {Fieldset} from '../component/Fieldset.js'
import {FormErrors} from '../component/FormErrors.js'
import {app} from '../lib/app.js'

const state = {
  fileName: '',
  errors: []
}

const save = (e) => {
  e.redraw = false
  api.post('page/delete', {}, {
    background: true, // no redraw
    body: {fileName: state.fileName},
    withCredentials: false
  })
    .then(() => {
      addSuccess(t('Page was successfully deleted.'))
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

export const PageDeleteView = {
  oninit: () => {
    if (!app.hasAuthToken()) {
      m.route.set('/logout')
    }
    state.fileName = iframe.fileName()
    state.errors = []
  },
  view: () => m(ModalMedium, {title: t('Delete page')},
    m(Fieldset, {legend: t('Confirm')},
      m('p', t('Do you want to delete the page?', {
        name: state.fileName
      })),
      m(ButtonGroup, {wrap: 'div.row.text-right'},
        m(ButtonSecondary, {onclick: cancel}, m(Icon, {name: 'clear'}), m('span', t('No'))),
        m(ButtonPrimary, {onclick: save}, m(Icon, {name: 'check'}), m('span', t('Yes')))
      )
    ),
    m(FormErrors, {errors: state.errors})
  )
}
