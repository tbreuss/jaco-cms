import {api} from '../service/api.js'

export const CssEditModel = {
  isLoading: false,
  list: [],
  item: {},
  loadList: () => {
    return api.get('css/list')
      .then((data) => {
        CssEditModel.list = data
      })
  },
  load: (file) => {
    CssEditModel.isLoading = true
    return api.get('css/view', {file})
      .then((data) => {
        CssEditModel.item = data
        CssEditModel.isLoading = false
      })
  },
  save: () => {
    return api.post('css/update', {}, {
      body: {
        fileName: CssEditModel.item.file,
        css: CssEditModel.item.content
      }
    })
  }
}
