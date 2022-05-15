
import { api } from '../service/api.js'

export const PageList = {
  loading: false,
  items: [],
  load: () => {
    PageList.loading = true
    return api.get('action=page/list')
      .then(({ data }) => {
        PageList.loading = false
        PageList.items = data
      })
  }
}
