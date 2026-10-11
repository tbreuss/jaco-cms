import {api} from '../service/api.js'

export const SystemModel = {
  info: {},
  load: () => {
    return api.get('system')
      .then((data) => {
        SystemModel.info = data
      })
  }
}
