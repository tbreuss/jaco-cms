
import { api } from '../service/api.js'

export const BackupView = {
  loading: false,
  item: {},
  load: (file) => {
    BackupView.loading = true
    return api.get('action=backup/view&file=' + file)
      .then(({ data }) => {
        BackupView.item = data
        BackupView.loading = false
      })
  }
}
