import {api} from '../service/api.js'

export const PhpLogModel = {
  isLoading: false,
  data: [],
  load: () => {
    PhpLogModel.isLoading = true
    return api.get('system/log')
      .then((data) => {
        PhpLogModel.data = data
        PhpLogModel.isLoading = false
      })
  }
}
