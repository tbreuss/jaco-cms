import {api} from '../service/api.js'

// The About page is available to every signed-in role, but its data endpoint is
// protected because it exposes the PHP version. The public index stays at name
// and CMS version only.
export const AboutModel = {
  info: {},
  load: () => {
    return api.get('about')
      .then((data) => {
        AboutModel.info = data
      })
  },
}
