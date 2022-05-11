import { Label } from "./Label.js"
import { Input } from "./Input.js"

export const InputText = {
  view: ({ attrs: { disabled, id, label, value, placeholder, wrap, oninput } }) => (
    wrap
      ? m(wrap,
        label && m(Label, { id }, label),
        m(Input, { type: 'text', disabled, id, value, placeholder, oninput })
      )
      : (
        label && m(Label, { id }, label),
        m(Input, { type: 'text', disabled, id, value, placeholder, oninput })
      )
  )
}
