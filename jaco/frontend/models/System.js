
import { api } from '../services/api.js'

export const System = {
  info: {},
  load: () => {
    return api.get('action=system')
      .then(({ data }) => {
        System.info = data
      })
  }
}
