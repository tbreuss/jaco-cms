// Explicit theme choice: light, dark or auto (follow the OS). Persisted in
// localStorage, applied as data-theme on <html> so the CSS needs no media
// query. initTheme() also follows OS changes while auto is selected.
const STORAGE_KEY = 'jaco-theme'

const prefersDark = () => window.matchMedia('(prefers-color-scheme: dark)').matches

export const getTheme = () => {
  try {
    return localStorage.getItem(STORAGE_KEY) || 'auto'
  } catch {
    return 'auto'
  }
}

export const applyTheme = (theme) => {
  const dark = theme === 'dark' || (theme !== 'light' && prefersDark())
  document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light')
}

export const setTheme = (theme) => {
  try {
    localStorage.setItem(STORAGE_KEY, theme)
  } catch {
    // private mode etc: apply for this session only
  }
  applyTheme(theme)
}

export const initTheme = () => {
  applyTheme(getTheme())
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (getTheme() === 'auto') {
      applyTheme('auto')
    }
  })
}
