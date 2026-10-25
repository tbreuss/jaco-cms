import {api} from '../service/api.js'

export const SystemModel = {
  info: {},
  load: () => {
    return api.get('system', {}, {pageLoad: true})
      .then((data) => {
        SystemModel.info = data
      }, () => {
        SystemModel.info = {}
      })
  }
}
