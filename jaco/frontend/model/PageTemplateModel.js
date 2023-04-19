import {api} from '../service/api.js'

export const PageTemplateModel = {
  list: [],
  loadList: () => {
    return api.get('action=template/pages')
      .then((data) => {
        PageTemplateModel.list = data
      })
  }
}
