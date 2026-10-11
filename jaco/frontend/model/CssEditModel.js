import {api} from '../service/api.js'

export const CssEditModel = {
  isLoading: false,
  list: [],
  item: {},
  loadList: () => {
    return api.get('action=css/list')
      .then((data) => {
        CssEditModel.list = data
      })
  },
  load: (file) => {
    CssEditModel.isLoading = true
    return api.get('action=css/view&file=' + encodeURIComponent(file))
      .then((data) => {
        CssEditModel.item = data
        CssEditModel.isLoading = false
      })
  },
  save: () => {
    return api.post('action=css/update', {
      body: {
        fileName: CssEditModel.item.file,
        css: CssEditModel.item.content
      }
    })
  }
}
