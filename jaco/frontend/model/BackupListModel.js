import {api} from '../service/api.js'

export const BackupListModel = {
  isLoading: false,
  items: [],
  sort: '-backedUp',
  filter: '',
  loadList: () => {
    BackupListModel.isLoading = true
    return api.get('backup/list', {filter: BackupListModel.filter, sort: BackupListModel.sort}, {pageLoad: true})
      .then((data) => {
        BackupListModel.items = data.items
        BackupListModel.isLoading = false
      }, () => {
        // 403 on a page load: the layout explains it, no toast and no spinner
        BackupListModel.isLoading = false
      })
  }
}
