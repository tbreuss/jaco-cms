import {Icon} from './Icon.js'

// Text filter with a clear button inside the field. The button only
// shows while a filter is active; clicking it clears the value and
// reloads the full list through onclear.
export const FilterInput = {
  view: ({attrs: {placeholder, value, oninput, onclear, clearLabel, autofocus, onkeydown}}) => (
    m('.list-filter',
      m('.list-filter-input',
        m('input[type=text]', {
          placeholder,
          value: value ?? '',
          autofocus: autofocus,
          'aria-label': placeholder,
          oninput: oninput,
          onkeydown: onkeydown,
        }),
        !!value && m('button.list-filter-clear', {
          type: 'button',
          title: clearLabel,
          'aria-label': clearLabel,
          onclick: (e) => {
            onclear()
            e.target.closest('.list-filter-input')?.querySelector('input')?.focus()
          },
        }, m(Icon, {name: 'clear'}))
      )
    )
  )
}
