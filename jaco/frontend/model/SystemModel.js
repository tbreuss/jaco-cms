import {api} from '../service/api.js'

export const SystemModel = {
  info: {},
  update: {},
  load: () => {
    return api.get('system', {}, {pageLoad: true})
      .then((data) => {
        SystemModel.info = data
      }, () => {
        SystemModel.info = {}
      })
  },
  checkUpdate: () => {
    return api.get('system/check-update')
      .then((data) => {
        SystemModel.update = data
        return data
      }, (err) => {
        SystemModel.update = {error: err}
        throw err
      })
  },
  performUpdate: (downloadUrl = '', version = '') => {
    return api.post('system/update', {downloadUrl, version})
      .then((data) => {
        SystemModel.update = {...SystemModel.update, result: data}
        return data
      }, (err) => {
        SystemModel.update = {...SystemModel.update, error: err}
        throw err
      })
  }
}
