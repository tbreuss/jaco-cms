
import { api } from '../service/api.js'

export const Page = {
  list: [],
  loadList: () => {
    return api.get('action=page/list')
      .then(({ data }) => {
        Page.list = data
      })
  }
}
