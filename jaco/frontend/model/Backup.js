
import { api } from '../service/api.js'

export const Backup = {
  loading: false,
  items: [],
  sort: '-backedUp',
  filter: '',
  loadList: () => {
    Backup.loading = true
    return api.get('action=backup/list&filter=' + Backup.filter + '&sort=' + Backup.sort)
      .then(({ data }) => {
        Backup.items = data.items
        Backup.loading = false
      })
  }
}
