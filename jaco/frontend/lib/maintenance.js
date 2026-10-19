// Whether writing is currently blocked. The flag lives in settings.php, so the
// frontend cannot ask for it on demand: every response carries an
// X-Maintenance-Mode header (see api.php), and the api layer reads it. The
// banner is the only thing that reads this.

let active = false

export const maintenance = {
  isActive: () => active,
  set: (on) => {
    active = on === true
  },
  // reads the header that every response carries (see api.php); no-op when
  // the header is absent, so a proxy or error response never guesses a state
  readFrom: (xhr) => {
    const value = xhr.getResponseHeader('X-Maintenance-Mode')
    if (value !== null) {
      active = value === 'true'
    }
  },
  // for tests
  reset: () => {
    active = false
  },
}