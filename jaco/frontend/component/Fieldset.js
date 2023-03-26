export const Fieldset = {
  view: ({attrs: {legend}, children}) =>
    m('fieldset',
      m('legend', legend),
      children
    )
}
