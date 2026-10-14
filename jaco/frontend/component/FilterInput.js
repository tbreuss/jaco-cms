import {Icon} from './Icon.js'

// Text filter with a clear button inside the field. The button only
// shows while a filter is active; clicking it clears the value and
// reloads the full list through onclear.
export const FilterInput = {
  view: ({attrs: {placeholder, value, oninput, onclear, clearLabel}}) => (
    m('.list-filter',
      m('.list-filter-input',
        m('input[type=text]', {
          placeholder,
          value: value ?? '',
          'aria-label': placeholder,
          oninput,
        }),
        !!value && m('button.list-filter-clear', {
          type: 'button',
          title: clearLabel,
          'aria-label': clearLabel,
          onclick: (e) => {
            e.redraw = false
            onclear()
            e.target.closest('.list-filter-input')?.querySelector('input')?.focus()
          },
        }, m(Icon, {name: 'clear'}))
      )
    )
  )
}
