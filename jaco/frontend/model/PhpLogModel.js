
import { api } from '../service/api.js'

export const PhpLogModel = {
  loading: false,
  data: [],
  load: () => {
    PhpLogModel.loading = true
    return api.get('action=system/log')
      .then(({ data }) => {
        PhpLogModel.data = data
        PhpLogModel.loading = false
      })
  }
}
