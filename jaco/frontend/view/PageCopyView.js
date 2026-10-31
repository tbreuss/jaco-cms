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
import {FormErrors, errorFor} from '../component/FormErrors.js'
import {app} from '../lib/app.js'
import {auth} from '../lib/auth.js'
import {PageCopySource} from '../model/PageCopySource.js'

const state = {
  srcFileName: '',
  srcPageTitle: '',
  dstFileName: '',
  dstPageTitle: '',
  errors: [],
  returnTo: '/'
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
      m.route.set(state.returnTo)
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
  m.route.set(state.returnTo)
}

export const PageCopyView = {
  oninit: (vnode) => {
    // consume the row selection even when the view redirects, so a stale source
    // cannot leak into the next copy attempt
    const pending = PageCopySource.take()

    if (!app.hasAuthToken()) {
      m.route.set('/logout')
      return
    }
    // copying writes a new page: editor only
    if (!auth.can('editor')) {
      m.route.set('/')
      return
    }
    // i.e. if page was reloaded with given route
    if (!iframe.hasElement()) {
      m.route.set('/')
      return
    }
    // a row action hands the source over in memory; the context menu falls back
    // to the page currently shown in the preview
    const sourceFile = pending?.file || vnode.attrs.file || iframe.fileName()
    const sourceTitle = pending?.title || vnode.attrs.title || iframe.pageTitle()
    state.srcFileName = sourceFile
    state.srcPageTitle = sourceTitle
    // the copy needs its own name and title; pre-filling the source would only
    // invite an accidental collision, so start empty like the add-page dialog
    state.dstFileName = ''
    state.dstPageTitle = ''
    state.returnTo = pending?.returnTo || '/'
    state.errors = []
  },
  view: () => {
    return m(ModalMedium, {title: t('Copy page'), closeRoute: state.returnTo},
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
          placeholder: t('e.g. contact.html'),
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
          placeholder: t('e.g. Contact'),
          value: state.dstPageTitle,
          error: errorFor(state.errors, 'dstPageTitle'),
          oninput: (value) => {
            state.dstPageTitle = value
          }
        })
      ),
      m(FormErrors, {errors: state.errors.filter((error) => !fieldPointers.includes(error.source.pointer))}),
      m(ButtonGroup, {wrap: 'div.row.text-right'},
        m(ButtonSecondary, {onclick: cancel}, m(Icon, {name: 'clear'}), m('span', t('Cancel'))),
        m(ButtonPrimary, {onclick: save}, m(Icon, {name: 'check'}), m('span', t('Copy page')))
      )
    )
  }
}
