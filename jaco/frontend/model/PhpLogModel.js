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
      .catch(() => {
        // the log is not worth an error toast: the banner already says that
        // writing is blocked, and a failed read is not a failed save
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
