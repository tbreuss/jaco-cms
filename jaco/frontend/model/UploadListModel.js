
import { api } from '../service/api.js'

export const UploadListModel = {
  loading: false,
  items: [],
  sort: 'file',
  filter: '',
  load: () => {
    UploadListModel.loading = true
    return api.get('action=upload/list&filter=' + UploadListModel.filter + '&sort=' + UploadListModel.sort)
      .then(({ data }) => {
        UploadListModel.loading = false
        UploadListModel.items = data.items
      })
  }
}
