import {api} from '../service/api.js'

const escapeTitle = (title) => title
  .replaceAll('&', '&amp;')
  .replaceAll('<', '&lt;')
  .replaceAll('>', '&gt;')

// the document is the source of truth, the title field only patches it when it was used
const withTitle = (html, title) => html.replace(/<title>.*?<\/title>/is, '<title>' + escapeTitle(title) + '</title>')

export const PageEditModel = {
  isLoading: false,
  item: {},
  load: (file) => {
    PageEditModel.isLoading = true
    return api.get('action=page/view&file=' + file)
      .then((data) => {
        PageEditModel.item = data
        PageEditModel.item.loadedTitle = data.title ?? ''
        PageEditModel.isLoading = false
      })
  },
  save: () => {
    const title = PageEditModel.item.title ?? ''
    const html = title === (PageEditModel.item.loadedTitle ?? '')
      ? PageEditModel.item.content
      : withTitle(PageEditModel.item.content, title)

    return api.post('action=page/update', {
      body: {
        fileName: PageEditModel.item.file,
        html: html
      }
    })
  }
}
