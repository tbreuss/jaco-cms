import {t} from '../../model/i18n.js'
import {BackupView as BackupViewModel} from '../../model/BackupView.js'
import {ButtonSecondary} from '../../component/ButtonSecondary.js'
import {Fieldset} from '../../component/Fieldset.js'
import {InputText} from '../../component/InputText.js'
import {ButtonGroup} from "../../component/ButtonGroup.js"

export const BackupView = {
  oninit: (vnode) => {
    if (!window.sessionStorage.getItem('AUTHORIZATION_TOKEN')) {
      m.route.set('/logout')
    }
    BackupViewModel.load(vnode.attrs.file)
  },
  oncreate: () => {
    document.getElementById('app').classList.add('max-height')
    document.documentElement.style.visibility = 'visible'
  },
  onupdate: () => {
    const editor = ace.edit("ace", {
      theme: "ace/theme/github",
      mode: "ace/mode/html",
      maxLines: Infinity,  // this is going to be very slow on large documents
      wrap: true,   // wrap text to view
      indentedSoftWrap: false,
      behavioursEnabled: false, // disable autopairing of brackets and tags
      showLineNumbers: false, // hide the gutter
      readOnly: true
    })
    editor.setValue(BackupViewModel.item.content, 1) // set cursor at the end
  },
  onremove: () => {
    document.getElementById('app').classList.remove('max-height')
  },
  view: () => m('div.main',
    m('.content',
      m('h1', t('View backup'), ': ', BackupViewModel.item.title ?? ''),
      m(Fieldset, {legend: t('Current page')},
        m(InputText, {
          wrap: 'div.row',
          label: t('Filename'),
          value: BackupViewModel.item.name ?? '',
          disabled: true
        }),
        m(InputText, {
          wrap: 'div.row',
          label: t('Page title'),
          value: BackupViewModel.item.title ?? '',
          disabled: true
        }),
        m(InputText, {
          wrap: 'div.row',
          label: t('Backed up'),
          value: BackupViewModel.item.created ? BackupViewModel.item.created.toDateTime() : '',
          disabled: true
        }),
        m('.row',
          m('label', {style: {width: '100%'}}, t('HTML')),
          m('div#ace', {style: {width: '100%'}})
        ),
        m(ButtonGroup, {wrap: 'div.row.text-right'},
          m(ButtonSecondary, {
            onclick: () => {
              m.route.set('/backups')
            }
          }, t('Back'))
        )
      )
    )
  )
}
