import {api} from '../service/api.js'

export const UploadListModel = {
  isLoading: false,
  items: [],
  sort: 'file',
  filter: '',
  load: () => {
    UploadListModel.isLoading = true
    return api.get('action=upload/list&filter=' + UploadListModel.filter + '&sort=' + UploadListModel.sort)
      .then(({data}) => {
        UploadListModel.isLoading = false
        UploadListModel.items = data.items
      })
  }
}
