import {SystemModel as SystemModel} from '../model/SystemModel.js'
import {ButtonGroup} from '../component/ButtonGroup.js'
import {ButtonPrimary} from '../component/ButtonPrimary.js'
import {Fieldset} from '../component/Fieldset.js'
import {FormErrors, errorFor} from '../component/FormErrors.js'
import {Icon} from '../component/Icon.js'
import {InputPassword} from '../component/InputPassword.js'
import {t} from '../model/i18n.js'
import {formatBytes} from '../lib/format.js'
import {app} from '../lib/app.js'
import {api} from '../service/api.js'
import {addSuccess} from '../state/actions.js'

const keyValueTable = (title, object) => object
  ? m('div',
    m('h2', title),
    m('table.table.table-system-view',
      m('colgroup',
        m('col.col-30'),
        m('col')
      ),
      m('thead', m('tr', m('th', t('Name')), m('th', t('Value')))),
      m('tbody', Object.entries(object).map(([key, value]) => m('tr',
          m('td', key),
          m('td', configValue(key, value)),
        ))
      )
    ))
  : ''

// maintenanceMode arrives as the string 'true'/'false' (display_config converts
// the bool). Rendered as a translated yes/no instead of a raw string, but like
// every other row: no special marking.
const configValue = (key, value) => {
  if (key === 'maintenanceMode') {
    return value === 'true' ? t('Yes') : t('No')
  }
  return Array.isArray(value) ? value.join(', ') : value
}

const checksTable = (title, checks) => checks
  ? m('div',
    m('h2', title),
    m('table.table.table-system-view',
      m('colgroup',
        m('col.col-30'),
        m('col'),
        m('col.col-shrink')
      ),
      m('thead', m('tr', m('th', t('Check')), m('th', t('Detail')), m('th.text-center', t('Status')))),
      m('tbody', checks.map((check) => m('tr',
          m('td.text-nowrap', check.label),
          m('td', check.detail),
          m('td.text-center', {class: check.ok ? 'check-ok' : 'check-fail'}, check.ok ? '✓' : '✗'),
        ))
      )
    ))
  : ''

export const SystemView = {
  oninit: () => {
    if (!app.hasAuthToken()) {
      m.route.set('/logout')
    }
    passwordFormState.errors = []
    SystemModel.load()
  },
  oncreate: () => {
    app.makeAppScrollable()
  },
  onremove: () => {
    app.makeIframeScrollable()
  },
  view: () => m('div.main',
    m('.content',
      m('h1', t('System environment')),
      checksTable(t('Requirements'), SystemModel.info.checks),
      overviewTable(SystemModel.info.overview),
      keyValueTable(t('Configuration'), SystemModel.info.config),
      passwordForm(),
    )
  )
}

// the current password is the proof: the endpoint verifies it like a login and counts
// a wrong one against the same throttle
const passwordFormState = {current: '', next: '', sending: false, errors: []}

const submitPassword = (event) => {
  event.preventDefault()
  passwordFormState.sending = true
  passwordFormState.errors = []

  // the passwords belong in the body, not in the query string: a url ends up in
  // server logs and browser history
  api.post('system/change-password', {}, {
    body: {
      withPassword: passwordFormState.current,
      withNewPassword: passwordFormState.next,
    },
  })
    .then(() => {
      passwordFormState.current = ''
      passwordFormState.next = ''
      addSuccess(t('The password was changed.'))
    })
    .catch(({code, response}) => {
      // validation errors belong under the fields; everything else is toasted by the
      // api layer already
      if (code === 422) {
        passwordFormState.errors = Array.isArray(response) ? response : []
      }
    })
    .finally(() => {
      passwordFormState.sending = false
      m.redraw()
    })
}

const passwordForm = () => m('div',
  m('h2', t('Change password')),
  m('form.form-narrow', {onsubmit: submitPassword},
    m(Fieldset, {},
      m(InputPassword, {
        id: 'currentPassword',
        wrap: 'div.row',
        label: t('Current password'),
        value: passwordFormState.current,
        error: errorFor(passwordFormState.errors, 'withPassword'),
        oninput: (value) => {
          passwordFormState.current = value
          passwordFormState.errors = []
        },
      }),
      m(InputPassword, {
        id: 'newPassword',
        wrap: 'div.row',
        label: t('New password'),
        value: passwordFormState.next,
        error: errorFor(passwordFormState.errors, 'withNewPassword'),
        oninput: (value) => {
          passwordFormState.next = value
          passwordFormState.errors = []
        },
      }),
      m(ButtonGroup, {wrap: 'div.row.text-right'},
        m(ButtonPrimary, {disabled: passwordFormState.sending},
          m(Icon, {name: 'check'}),
          m('span', t('Change password')),
        ),
      ),
      m(FormErrors, {
        errors: passwordFormState.errors.filter((error) => !['withPassword', 'withNewPassword'].includes(error.source.pointer)),
      }),
    ),
  ),
)

const overviewTable = (overview) => {
  if (overview == null) {
    return ''
  }
  const size = formatBytes(overview.projectSize)
  return keyValueTable(t('Overview'), {
    [t('Pages')]: String(overview.pages),
    [t('Uploads')]: String(overview.uploads),
    [t('Backups')]: String(overview.backups),
    [t('Project size')]: size,
    [t('PHP error log')]: `${formatBytes(overview.logSize)} / ${overview.logLines === 0 ? t('Empty') : overview.logLines.toLocaleString('de-CH')}`,
  })
}
