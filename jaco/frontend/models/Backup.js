
import { api } from '../services/api.js'

export const Backup = {
  loading: false,
  list: [],
  sort: '-created',
  filter: '',
  loadList: () => {
    Backup.loading = true
    return api.get('action=backup/list&filter=' + Backup.filter + '&sort=' + Backup.sort)
      .then(({ data }) => {
        Backup.list = data.list
        Backup.loading = false
      })
  }
}
