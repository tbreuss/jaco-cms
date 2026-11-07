import {api} from '../service/api.js'

// Page templates. Listing and reading are the editor's: the page manager shows
// the templates when a page is created, and page/create reads the same file.
// Only the admin may change one - the update route answers 403 for anyone else,
// and the view keeps the buttons disabled for that role.
//
// The listing carries the title only, so the document is read file by file:
// unlike a content fragment there is no list entry that could hold it.

export const PageTemplateEditModel = {
  isLoading: false,
  list: [],
  item: {},
  loadList: () => {
    PageTemplateEditModel.isLoading = true
    return api.get('template/pages', {}, {pageLoad: true})
      .then((data) => {
        PageTemplateEditModel.list = data
        PageTemplateEditModel.isLoading = false
      }, () => {
        PageTemplateEditModel.isLoading = false
      })
  },
  load: (file) => {
    PageTemplateEditModel.isLoading = true
    return api.get('template/page/view', {file}, {pageLoad: true})
      .then((data) => {
        PageTemplateEditModel.item = data
        PageTemplateEditModel.isLoading = false
      }, () => {
        PageTemplateEditModel.isLoading = false
      })
  },
  save: () => {
    return api.post('template/page/update', {}, {
      body: {
        fileName: PageTemplateEditModel.item.file,
        content: PageTemplateEditModel.item.content,
      }
    })
  }
}
