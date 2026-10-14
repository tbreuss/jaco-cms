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
  },
  clear: () => {
    return api.post('system/log/clear', {}, {
      background: true, // no redraw
      body: {},
      withCredentials: false
    })
      .then(() => {
        PhpLogModel.load()
      })
  }
}
