import {api} from '../service/api.js'

export const JsEditModel = {
  isLoading: false,
  list: [],
  item: {},
  loadList: () => {
    return api.get('js/list')
      .then((data) => {
        JsEditModel.list = data
      })
  },
  load: (file) => {
    JsEditModel.isLoading = true
    return api.get('js/view', {file})
      .then((data) => {
        JsEditModel.item = data
        JsEditModel.isLoading = false
      })
  },
  save: () => {
    return api.post('js/update', {}, {
      body: {
        fileName: JsEditModel.item.file,
        js: JsEditModel.item.content
      }
    })
  }
}
