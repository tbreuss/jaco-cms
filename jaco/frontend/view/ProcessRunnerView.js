import {t} from '../model/i18n.js'
import {processState, nextProcess, backProcess, closeProcess} from '../lib/process.js'
import {ModalMedium} from '../component/ModalMedium.js'
import {ButtonGroup} from '../component/ButtonGroup.js'
import {ButtonPrimary} from '../component/ButtonPrimary.js'
import {ButtonSecondary} from '../component/ButtonSecondary.js'
import {Icon} from '../component/Icon.js'
import {FormErrors} from '../component/FormErrors.js'

export const ProcessRunner = {
  view: () => {
    const definition = processState.definition
    if (!processState.open || !definition) {
      return null
    }

    const step = definition.steps[processState.step]
    const last = processState.step === definition.steps.length - 1
    const set = (key, value) => {
      processState.data[key] = value
      processState.errors = []
      m.redraw()
    }
    const errors = processState.errors.filter((error) =>
      !step.pointers || !error.source || !step.pointers.includes(error.source.pointer))

    return m(ModalMedium, {
      title: t(definition.titleKey),
      onclose: closeProcess,
      cssClass: 'process-modal',
    },
    m('.process-progress', processState.loading
      ? t('Loading') + '...'
      : (processState.step + 1) + ' / ' + definition.steps.length),
    m('h3', t(step.titleKey)),
    step.view(processState.data, set, processState),
    m(FormErrors, {errors}),
    m(ButtonGroup, {wrap: 'div.row.text-right'},
      m(ButtonSecondary, {
        onclick: closeProcess,
        disabled: processState.busy,
      }, m(Icon, {name: 'clear'}), m('span', t('Cancel'))),
      processState.step > 0
        ? m(ButtonSecondary, {
          onclick: backProcess,
          disabled: processState.busy || processState.loading,
        }, m(Icon, {name: 'back'}), m('span', t('Back')))
        : null,
      m(ButtonPrimary, {
        onclick: nextProcess,
        disabled: processState.busy || processState.loading,
      }, m(Icon, {name: 'check'}), m('span', last ? t(definition.finishKey ?? 'Confirm') : t('Next')))
    ))
  },
}
