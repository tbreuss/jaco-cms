import {api} from '../service/api.js'

export const PageEditModel = {
  isLoading: false,
  item: {},
  load: (file) => {
    PageEditModel.isLoading = true
    return api.get('action=page/view&file=' + file)
      .then(({data}) => {
        PageEditModel.item = data
        PageEditModel.isLoading = false
      })
  },
  save: () => {
    return api.post('action=page/update', {
      body: {
        fileName: PageEditModel.item.file,
        html: PageEditModel.item.content
      }
    })
  }
}
