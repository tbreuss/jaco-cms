// Demo only: the gallery examples on the elements pages open the full image in a
// dialog. Jaco's own lightbox links the files; a modal would be the site's job.
//
// The file lives outside the page on purpose: the page manager removes <script>
// from the body on every save, so an inline demo would be gone the first time
// someone edits the page. Loaded from the <head> it survives.
//
// It waits for the DOM because a script in the <head> runs before the images exist.
document.addEventListener('DOMContentLoaded', () => {
  if (typeof HTMLDialogElement === 'undefined') {
    return
  }

  document.querySelectorAll('.elements-gallery[data-demo-full]').forEach((image) => {
    image.style.cursor = 'zoom-in'
    image.addEventListener('click', (event) => {
      // Only in the public view: the editor loads its scripts into this document
      // and owns the click, so the dialog stays out of the way. `initRichTextEditor`
      // is defined by the editor script and by nothing else, which makes it the
      // marker for "is being edited".
      if (typeof window.initRichTextEditor === 'function') {
        return
      }

      event.preventDefault()
      event.stopPropagation()

      const dialog = document.createElement('dialog')
      dialog.className = 'elements-modal'
      const full = document.createElement('img')
      full.src = image.dataset.demoFull
      full.alt = image.alt
      const close = document.createElement('button')
      close.type = 'button'
      close.textContent = image.dataset.demoClose || 'Close'
      close.addEventListener('click', () => dialog.close())
      dialog.addEventListener('close', () => dialog.remove())
      dialog.append(full, close)
      document.body.appendChild(dialog)
      dialog.showModal()
    }, true)
  })
})
