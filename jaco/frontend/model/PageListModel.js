import {api} from '../service/api.js'

export const PageListModel = {
  isLoading: false,
  items: [],
  sort: 'file',
  filter: '',
  load: () => {
    PageListModel.isLoading = true
    return api.get('page/list', {filter: PageListModel.filter, sort: PageListModel.sort}, {pageLoad: true})
      .then((data) => {
        PageListModel.isLoading = false
        PageListModel.items = data.items
      }, () => {
        PageListModel.isLoading = false
      })
  },
  // Jump to page needs every page, independent of the filter that is active in
  // the page manager; it must not overwrite that list.
  loadAll: () => api.get('page/list', {filter: '', sort: 'file'}, {pageLoad: true})
    .then((data) => data.items, () => [])
}
