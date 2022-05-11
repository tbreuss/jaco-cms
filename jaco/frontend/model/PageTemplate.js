
import { api } from '../service/api.js'

export const PageTemplate = {
  list: [],
  loadList: () => {
    return api.get('action=template/pages')
      .then(({ data }) => {
        PageTemplate.list = data
      })
  }
}
