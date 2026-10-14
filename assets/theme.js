// Theme switch for the site pages: light or dark, defaulting to auto (follow the OS).
// The site shares the storage key with the admin panel, so one choice covers both.
//
// The button sits in the html, the script only wires it up. The theme itself is
// applied synchronously before the first paint, so there is no color flash and
// the menu keeps its size from the start.
(() => {
  const STORAGE_KEY = 'jaco-theme'
  const root = document.documentElement

  const stored = () => {
    try {
      return localStorage.getItem(STORAGE_KEY) || 'auto'
    } catch {
      return 'auto'
    }
  }

  const isDark = (theme) => theme === 'dark'
    || (theme !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches)

  const apply = (theme) => {
    root.setAttribute('data-theme', isDark(theme) ? 'dark' : 'light')
  }

  apply(stored())

  const labels = {
    en: {toLight: 'Switch to the light theme', toDark: 'Switch to the dark theme'},
    de: {toLight: 'Zum hellen Design wechseln', toDark: 'Zum dunklen Design wechseln'},
  }

  const wire = () => {
    const button = document.querySelector('.theme-switch')
    if (!button) {
      return
    }

    const text = labels[(root.lang || 'en').slice(0, 2)] || labels.en

    const update = () => {
      const dark = root.getAttribute('data-theme') === 'dark'
      button.setAttribute('aria-label', dark ? text.toLight : text.toDark)
      button.setAttribute('title', dark ? text.toLight : text.toDark)
    }

    button.addEventListener('click', () => {
      const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'
      try {
        localStorage.setItem(STORAGE_KEY, next)
      } catch {
        // private mode: the choice lasts for this page view only
      }
      apply(next)
      update()
    })

    update()
  }

  // The script runs in the head, the button exists only after parsing:
  // wiring on DOMContentLoaded changes no layout, the button keeps its size.
  if (document.querySelector('.theme-switch')) {
    wire()
  } else {
    document.addEventListener('DOMContentLoaded', wire)
  }
})()
