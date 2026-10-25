import {api} from '../service/api.js'

export const PageEditModel = {
  isLoading: false,
  item: {},
  load: (file) => {
    PageEditModel.isLoading = true
    return api.get('page/view', {file}, {pageLoad: true})
      .then((data) => {
        PageEditModel.item = data
        PageEditModel.isLoading = false
      }, () => {
        PageEditModel.isLoading = false
      })
  },
  save: () => {
    return api.post('page/update', {}, {
      body: {
        fileName: PageEditModel.item.file,
        html: PageEditModel.item.content,
        title: PageEditModel.item.title,
        description: PageEditModel.item.description ?? '',
        robots: PageEditModel.item.robots ?? '',
      }
    })
  }
}
