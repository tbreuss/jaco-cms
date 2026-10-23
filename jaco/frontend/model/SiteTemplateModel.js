import {api} from '../service/api.js'

export const SiteTemplateModel = {
  isLoading: false,
  list: [],
  item: {},
  loadList: () => {
    SiteTemplateModel.isLoading = true
    return api.get('template/site/list')
      .then((data) => {
        SiteTemplateModel.list = data
        SiteTemplateModel.isLoading = false
      }, () => {
        SiteTemplateModel.isLoading = false
      })
  },
  load: (file) => {
    SiteTemplateModel.isLoading = true
    return api.get('template/site/view', {file})
      .then((data) => {
        SiteTemplateModel.item = data
        SiteTemplateModel.isLoading = false
      })
  },
  preview: () => {
    return api.post('template/site/preview', {}, {
      body: {fileName: SiteTemplateModel.item.file}
    })
  },
  save: (applyToAll) => {
    return api.post('template/site/update', {}, {
      body: {
        fileName: SiteTemplateModel.item.file,
        content: SiteTemplateModel.item.content,
        applyToAll,
      }
    })
  }
}
