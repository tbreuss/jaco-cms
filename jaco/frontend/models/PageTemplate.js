
import { api } from '../services/api.js'

export const PageTemplate = {
  list: [],
  loadList: () => {
    return api.get('action=template/pages')
      .then(({ data }) => {
        PageTemplate.list = data
      })
  }
}
