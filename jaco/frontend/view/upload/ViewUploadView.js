import {t} from '../../model/i18n.js'
import {UploadListModel} from '../../model/UploadListModel.js'
import {ButtonSecondary} from '../../component/ButtonSecondary.js'
import {Icon} from '../../component/Icon.js'
import {Fieldset} from '../../component/Fieldset.js'
import {InputText} from '../../component/InputText.js'
import {ButtonGroup} from '../../component/ButtonGroup.js'
import {app} from '../../lib/app.js'
import {formatDateTimeCompact, formatDateTimeFull} from '../../lib/format.js'

const isImage = (extension) => ['png', 'gif', 'jpg', 'jpeg', 'svg'].includes((extension ?? '').toLowerCase())

const formatSize = (size) => Number(size).toLocaleString('de-CH')

const state = {
  file: ''
}

export const ViewUploadView = {
  oninit: (vnode) => {
    if (!app.hasAuthToken()) {
      m.route.set('/logout')
    }
    state.file = vnode.attrs.file ?? ''
    UploadListModel.load()
  },
  oncreate: () => {
    app.makeAppScrollable()
  },
  onremove: () => {
    app.makeIframeScrollable()
  },
  view: () => {
    const item = UploadListModel.items.find((entry) => entry.file === state.file) ?? {}
    const url = '/' + encodeURI(state.file)
    return m('div.main',
      m('.content',
        m('h1', t('View upload'), ': ', item.file ?? ''),
        m(Fieldset, {legend: t('Current upload')},
          m(InputText, {
            wrap: 'div.row',
            label: t('Filename'),
            value: item.file ?? '',
            disabled: true
          }),
          m(InputText, {
            wrap: 'div.row',
            label: t('Type'),
            value: item.extension ?? '',
            disabled: true
          }),
          m(InputText, {
            wrap: 'div.row',
            label: t('Size'),
            value: item.size == null ? '' : formatSize(item.size),
            disabled: true
          }),
          m(InputText, {
            wrap: 'div.row',
            label: t('Created at'),
            value: formatDateTimeCompact(item.created),
            title: formatDateTimeFull(item.created),
            disabled: true
          }),
          m('.row',
            m('label', {style: {width: '100%'}}, t('Preview')),
            isImage(item.extension)
              ? m('img', {src: url, style: {maxWidth: '100%'}})
              : (item.extension ?? '').toLowerCase() === 'pdf'
                ? m('embed', {src: url, type: 'application/pdf', style: {width: '100%', height: '600px'}})
                : m('a', {href: url, target: '_blank'}, url)
          )
        ),
        m(ButtonGroup, {wrap: 'div.row.text-right'},
          m(ButtonSecondary, {
            onclick: () => {
              m.route.set('/uploads')
            }
          }, m(Icon, {name: 'back'}), m('span', t('Back')))
        )
      )
    )
  }
}
