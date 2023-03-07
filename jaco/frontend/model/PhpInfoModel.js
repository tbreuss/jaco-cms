import { api } from '../service/api.js'
export const PhpInfoModel = {
  loading: false,
  data: [],
  load: () => {
    PhpInfoModel.loading = true
    return api.get('action=system/php-info')
      .then(({ data }) => {
        PhpInfoModel.data = data
        PhpInfoModel.loading = false
      })
  }
}
