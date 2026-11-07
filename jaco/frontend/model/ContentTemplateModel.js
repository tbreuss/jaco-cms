import {api} from '../service/api.js'

// Content templates are read through the fragment endpoints the inline editor
// uses, so an editor reaches the listing and one fragment. Only the admin may
// change one: the update route answers 403 for anyone else, and the view keeps
// the buttons disabled for that role.

export const ContentTemplateEditModel = {
  isLoading: false,
  list: [],
  item: {},
  loadList: () => {
    ContentTemplateEditModel.isLoading = true
    return api.get('template/content', {}, {pageLoad: true})
      .then((data) => {
        ContentTemplateEditModel.list = data
        ContentTemplateEditModel.isLoading = false
      }, () => {
        ContentTemplateEditModel.isLoading = false
      })
  },
  load: (file) => {
    ContentTemplateEditModel.isLoading = true
    return api.get('template/content/view', {file}, {pageLoad: true})
      .then((data) => {
        ContentTemplateEditModel.item = data
        ContentTemplateEditModel.isLoading = false
      }, () => {
        ContentTemplateEditModel.isLoading = false
      })
  },
  save: () => {
    return api.post('template/content/update', {}, {
      body: {
        fileName: ContentTemplateEditModel.item.file,
        content: ContentTemplateEditModel.item.content,
      }
    })
  }
}
