// Ace editor theme follows the admin theme: the github theme (light) is the
// default, tomorrow_night (dark, vendored in frontend/vendor/ace-builds) matches the dark
// design tokens. Editors watch the data-theme attribute so switching the theme
// while editing applies immediately; the returned function stops watching and
// belongs into the view's remove handler next to the editor itself.
export const aceThemeName = () => document.documentElement.getAttribute('data-theme') === 'dark'
  ? 'ace/theme/tomorrow_night'
  : 'ace/theme/github'

export const watchAceTheme = (editor) => {
  const observer = new MutationObserver(() => editor.setTheme(aceThemeName()))
  observer.observe(document.documentElement, {attributes: true, attributeFilter: ['data-theme']})
  return () => observer.disconnect()
}
