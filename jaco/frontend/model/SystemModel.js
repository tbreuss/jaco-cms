import {api} from '../service/api.js'

export const SystemModel = {
  info: {},
  load: () => {
    return api.get('action=system')
      .then((data) => {
        SystemModel.info = data
      })
  }
}
