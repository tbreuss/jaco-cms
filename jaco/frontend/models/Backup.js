
import { api } from '../services/api.js'

export const Backup = {
  list: [],
  loadList: () => {
    return api.get('action=backup/list')
      .then(({ data }) => {
        Page.list = data
      })
  }
}
