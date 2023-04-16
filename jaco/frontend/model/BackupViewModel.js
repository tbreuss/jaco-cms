import {api} from '../service/api.js'

export const BackupViewModel = {
  isLoading: false,
  item: {},
  load: (file) => {
    BackupViewModel.isLoading = true
    return api.get('action=backup/view&file=' + file)
      .then((data) => {
        BackupViewModel.item = data
        BackupViewModel.isLoading = false
      })
  }
}
