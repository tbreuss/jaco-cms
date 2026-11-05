// Mobile navigation: below 40rem the links move behind a hamburger button.
//
// The button sits in the html, the script only wires it up. The open state lives
// as a class on <html>. Without java script the plain navigation row stays visible
// and the toggle stays hidden (see assets/site.css).
(() => {
  document.documentElement.classList.add('js')

  const labels = {
    en: {open: 'Open the navigation', close: 'Close the navigation'},
    de: {open: 'Navigation öffnen', close: 'Navigation schließen'},
  }

  const burger = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>'
  const cross = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>'

  const wire = () => {
    const button = document.querySelector('.nav-toggle')
    if (!button) {
      return
    }

    const text = labels[(document.documentElement.lang || 'en').slice(0, 2)] || labels.en

    const setOpen = (open) => {
      document.documentElement.classList.toggle('nav-open', open)
      button.innerHTML = open ? cross : burger
      button.setAttribute('aria-expanded', open ? 'true' : 'false')
      button.setAttribute('aria-label', open ? text.close : text.open)
      button.setAttribute('title', open ? text.close : text.open)
    }

    button.addEventListener('click', () => {
      setOpen(!document.documentElement.classList.contains('nav-open'))
    })

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        setOpen(false)
      }
    })

    setOpen(false)
  }

  // The script runs in the head, the button exists only after parsing:
  // wiring on DOMContentLoaded changes no layout, the button keeps its size.
  if (document.querySelector('.nav-toggle')) {
    wire()
  } else {
    document.addEventListener('DOMContentLoaded', wire)
  }

  // The language switch always goes to the other language's start page.
  const setLanguageTarget = () => {
    const link = document.querySelector('.nav-utility a')
    if (!link) {
      return
    }
    const isGerman = (document.documentElement.lang || 'en').toLowerCase().startsWith('de')
    link.setAttribute('href', isGerman ? '/index.html' : '/de/index.html')
  }

  // The active menu item is derived from the current URL, so every page can
  // ship the same navigation markup.
  const setActive = () => {
    const path = window.location.pathname.endsWith('/')
      ? window.location.pathname + 'index.html'
      : window.location.pathname
    document.querySelectorAll('.navigation a[href]').forEach((link) => {
      const target = new URL(link.getAttribute('href'), document.baseURI).pathname
      const active = target === path
      link.parentElement.classList.toggle('active', active)
      if (active) {
        link.setAttribute('aria-current', 'page')
      } else {
        link.removeAttribute('aria-current')
      }
    })
  }

  const initNavigation = () => {
    setLanguageTarget()
    setActive()
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNavigation)
  } else {
    initNavigation()
  }
})()
