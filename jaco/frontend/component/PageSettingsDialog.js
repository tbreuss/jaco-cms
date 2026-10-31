import {t} from '../model/i18n.js'
import {ButtonGroup} from './ButtonGroup.js'
import {ButtonPrimary} from './ButtonPrimary.js'
import {ButtonSecondary} from './ButtonSecondary.js'
import {Fieldset} from './Fieldset.js'
import {FormErrors, errorFor} from './FormErrors.js'
import {Icon} from './Icon.js'
import {InputText} from './InputText.js'
import {Select} from './Select.js'

const fieldPointers = ['pageTitle', 'metaDescription', 'robots']

export const PageSettingsDialog = {
  view: ({attrs: {page, errors, saving, onchange, onsave, onclose}}) => {
    const inner = page
      ? [
        m(Fieldset, {legend: t('Page settings')},
          m(InputText, {
            id: 'pageSettingsTitle',
            autofocus: true,
            wrap: 'div.row',
            label: t('Page title'),
            value: page.title ?? '',
            error: errorFor(errors, 'pageTitle'),
            oninput: (value) => onchange('title', value)
          }),
          m(InputText, {
            id: 'pageSettingsDescription',
            wrap: 'div.row',
            label: t('Meta description'),
            value: page.description ?? '',
            oninput: (value) => onchange('description', value)
          }),
          m(Select, {
            id: 'pageSettingsRobots',
            wrap: 'div.row',
            label: t('Search engines'),
            value: page.robots ?? '',
            options: (() => {
              const options = [
                {value: '', label: t('Not set')},
                {value: 'index,follow', label: t('Index, follow')},
                {value: 'noindex,nofollow', label: t('Noindex, nofollow')},
              ]
              if (page.robots && !options.some((option) => option.value === page.robots)) {
                options.push({value: page.robots, label: page.robots})
              }
              return options
            })(),
            error: errorFor(errors, 'robots'),
            onchange: (value) => onchange('robots', value)
          })
        ),
        m(FormErrors, {errors: errors.filter((error) => !fieldPointers.includes(error.source.pointer))}),
        m(ButtonGroup, {wrap: 'div.row.text-right'},
          m(ButtonSecondary, {onclick: onclose}, m(Icon, {name: 'clear'}), m('span', t('Cancel'))),
          m(ButtonPrimary, {onclick: onsave, disabled: saving}, m(Icon, {name: 'save'}), m('span', t('Save')))
        )
      ]
      : m('p', t('Loading') + '...')

    return m('.modal', {
      tabindex: '-1',
      oncreate: ({dom}) => {
        dom.addEventListener('keydown', (event) => {
          if (event.key === 'Escape') {
            event.preventDefault()
            onclose()
          }
        })
        const focusable = dom.querySelector('input:not([disabled]), select:not([disabled]), button:not([disabled])')
        focusable?.focus()
      },
      onclick: (event) => {
        if (event.target === event.currentTarget) {
          onclose()
        }
      }
    }, m('.modal-inner',
      m('h2', t('Page settings')),
      inner
    ))
  }
}
