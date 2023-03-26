import {api} from '../service/api.js'

export const PageListModel = {
  isLoading: false,
  items: [],
  sort: 'file',
  filter: '',
  load: () => {
    PageListModel.isLoading = true
    return api.get('action=page/list&filter=' + PageListModel.filter + '&sort=' + PageListModel.sort)
      .then(({data}) => {
        PageListModel.isLoading = false
        PageListModel.items = data.items
      })
  }
}
