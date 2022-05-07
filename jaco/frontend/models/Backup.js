
import { api } from '../services/api.js'

export const Backup = {
  loading: false,
  list: [],
  loadList: () => {
    Backup.loading = true
    return api.get('action=backup/list')
      .then(({ data }) => {
        Backup.list = data
        Backup.loading = false
      })
  }
}
