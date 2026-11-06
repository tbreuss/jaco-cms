import {api} from '../service/api.js'

// Page templates are read with the editor's rights (the page manager lists them
// when a page is created), but only the admin may change one: the update route
// answers 403 for anyone else, the view keeps the buttons disabled.

const loadDocument = (file) => api.get('template/pages', {}, {pageLoad: true})
  .then((data) => data.find((entry) => entry.file === file) ?? {})

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
    return loadDocument(file)
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

// Content templates are read through the fragment endpoints the inline editor
// uses. An editor inserts them into pages, so the listing is read-only there.

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
