export const Label = {
  view: ({attrs: {id}, children}) =>
    m('label', {
      for: id,
    }, children)
}
