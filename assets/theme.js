// Theme switch for the site pages: light or dark, defaulting to auto (follow the OS).
// The site shares the storage key with the admin panel, so one choice covers both.
//
// The button is built here instead of sitting in the html: the CMS sanitizer removes
// <button> and <svg> whenever a page is saved, only the <script src> in the head
// survives. The script is loaded without defer, so the theme is applied before the
// first paint.
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

  const icons = {
    moon: '<svg class="icon-moon" viewBox="0 0 24 24" aria-hidden="true">'
      + '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>',
    sun: '<svg class="icon-sun" viewBox="0 0 24 24" aria-hidden="true">'
      + '<circle cx="12" cy="12" r="4"/>'
      + '<path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>',
  }

  const labels = {
    en: {toLight: 'Switch to the light theme', toDark: 'Switch to the dark theme'},
    de: {toLight: 'Zum hellen Design wechseln', toDark: 'Zum dunklen Design wechseln'},
  }

  document.addEventListener('DOMContentLoaded', () => {
    const navigation = document.querySelector('.navigation')
    if (!navigation) {
      return
    }

    // The language link lives in .nav-utility, the button joins it there so both stay
    // one group; a page without that item gets its own (see assets/site.css).
    let item = navigation.querySelector('.nav-utility')
    if (!item) {
      item = document.createElement('li')
      navigation.appendChild(item)
    }

    const button = document.createElement('button')
    button.type = 'button'
    button.className = 'theme-switch'
    button.innerHTML = icons.moon + icons.sun
    item.appendChild(button)

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
  })
})()
