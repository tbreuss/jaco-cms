
import { api } from '../service/api.js'

export const PageList = {
  loading: false,
  items: [],
  sort: 'file',
  filter: '',
  load: () => {
    PageList.loading = true
    return api.get('action=page/list&filter=' + PageList.filter + '&sort=' + PageList.sort)
      .then(({ data }) => {
        PageList.loading = false
        PageList.items = data.items
      })
  }
}
