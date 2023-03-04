import {api} from '../service/api.js'

export const PageEditModel = {
  loading: false,
  item: {},
  load: (file) => {
    PageEditModel.loading = true
    return api.get('action=page/view&file=' + file)
      .then(({data}) => {
        PageEditModel.item = data
        PageEditModel.loading = false
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
