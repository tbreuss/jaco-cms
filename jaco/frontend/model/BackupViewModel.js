import {api} from '../service/api.js'

export const BackupViewModel = {
  isLoading: false,
  item: {},
  load: (file) => {
    BackupViewModel.isLoading = true
    return api.get('backup/view', {file})
      .then((data) => {
        BackupViewModel.item = data
        BackupViewModel.isLoading = false
      })
  }
}
