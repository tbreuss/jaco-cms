import {api} from '../service/api.js'

export const PhpInfoModel = {
  isLoading: false,
  data: [],
  load: () => {
    PhpInfoModel.isLoading = true
    return api.get('action=system/php-info')
      .then((data) => {
        PhpInfoModel.data = data
        PhpInfoModel.isLoading = false
      })
  }
}
