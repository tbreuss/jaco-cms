// Mobile navigation: below 30rem the links move behind a hamburger button.
//
// The button is built here because the CMS sanitizer drops <button> and <svg> from a page
// body, and the open state lives as a class on <html>, which no save touches. Without
// java script the plain navigation row stays visible.
(() => {
  document.documentElement.classList.add('js')

  const labels = {
    en: {open: 'Open the navigation', close: 'Close the navigation'},
    de: {open: 'Navigation öffnen', close: 'Navigation schließen'},
  }

  const burger = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>'
  const cross = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>'

  document.addEventListener('DOMContentLoaded', () => {
    const navigation = document.querySelector('.navigation')
    if (!navigation) {
      return
    }

    const text = labels[(document.documentElement.lang || 'en').slice(0, 2)] || labels.en

    const item = document.createElement('li')
    item.className = 'nav-toggle-item'

    const button = document.createElement('button')
    button.type = 'button'
    button.className = 'nav-toggle'
    item.appendChild(button)
    navigation.prepend(item)

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
  })
})()
