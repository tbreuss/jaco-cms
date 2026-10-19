export const Fieldset = {
  view: ({attrs: {legend}, children}) =>
    m('fieldset',
      legend ? m('legend', legend) : null,
      children
    )
}
