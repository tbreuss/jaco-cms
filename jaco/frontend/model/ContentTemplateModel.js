import {api} from '../service/api.js'

export const ContentTemplateModel = {
  list: [],
  loadList: () => {
    return api.get('action=template/content')
      .then((data) => {
        ContentTemplateModel.list = data
      })
  },
  loadOne: (file) => {
    return api.get('action=template/content/view&file=' + encodeURIComponent(file))
  }
}
