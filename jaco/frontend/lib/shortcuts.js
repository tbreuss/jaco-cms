// Keyboard shortcuts for the edit views. Kept in one place so the behaviour
// (and its tests) stay consistent: Cmd/Ctrl+S saves, Cmd/Ctrl+Shift+S saves
// and closes. Alt combinations are left alone (dead keys, special chars).

export const isSaveShortcut = (e) =>
  (e.metaKey || e.ctrlKey) && !e.altKey && !e.shiftKey && (e.key === 's' || e.key === 'S')

export const isSaveAndCloseShortcut = (e) =>
  (e.metaKey || e.ctrlKey) && !e.altKey && e.shiftKey && (e.key === 's' || e.key === 'S')

// Labels adapt to the platform: ⌘ on macOS, Ctrl everywhere else.
// Language-neutral, so no translation keys are needed.
const isMacOs = () => /mac/i.test(
  (navigator.userAgentData && navigator.userAgentData.platform) || navigator.platform || ''
)

export const saveShortcutLabel = () => isMacOs() ? '⌘S' : 'Ctrl+S'

export const saveAndCloseShortcutLabel = () => isMacOs() ? '⌘⇧S' : 'Ctrl+Shift+S'

// Attaches a keydown listener that triggers the given callbacks and swallows
// the browser's own save dialog. Returns an unbind function for onremove.
export const bindSaveShortcut = (target, {onSave, onSaveAndClose}) => {
  const handler = (e) => {
    if (onSaveAndClose && isSaveAndCloseShortcut(e)) {
      e.preventDefault()
      onSaveAndClose()
    } else if (onSave && isSaveShortcut(e)) {
      e.preventDefault()
      onSave()
    }
  }
  target.addEventListener('keydown', handler)
  return () => target.removeEventListener('keydown', handler)
}
