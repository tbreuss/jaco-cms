import {api} from '../service/api.js'

export const BackupListModel = {
  isLoading: false,
  items: [],
  sort: '-backedUp',
  filter: '',
  loadList: () => {
    BackupListModel.isLoading = true
    return api.get('action=backup/list&filter=' + BackupListModel.filter + '&sort=' + BackupListModel.sort)
      .then(({data}) => {
        BackupListModel.items = data.items
        BackupListModel.isLoading = false
      })
  }
}
