import {api} from '../service/api.js'

export const PhpInfoModel = {
  isLoading: false,
  data: [],
  load: () => {
    PhpInfoModel.isLoading = true
    return api.get('system/php-info', {}, {pageLoad: true})
      .then((data) => {
        PhpInfoModel.data = data
        PhpInfoModel.isLoading = false
      }, () => {
        PhpInfoModel.isLoading = false
      })
  }
}
