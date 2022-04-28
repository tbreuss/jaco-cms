import { System as SystemModel } from '../models/System.js'

const constants = (object) => object
  ? m('div',
    m('h2', 'Constants'),
    m('.constants', Object.entries(object).map(function (constant) {
      return m('.user-list-item', constant)
    }))
  )
  : ''

const functions = (array) => array
  ? m('div',
    m('h2', 'Functions'),
    m('.constants', array.map(function (constant) {
      return m('.user-list-item', constant)
    }))
  )
  : ''

export const System = {
  oninit: SystemModel.load,
  oncreate: () => {
    document.getElementById('app').classList.add('max-height')
  },
  onremove: () => {
    document.getElementById('app').classList.remove('max-height')
  },
  view: () => m('div.main',
    m('.content',
      m('h1', 'System'),
      constants(SystemModel.info.constants),
      functions(SystemModel.info.functions),
    )
  )
}
