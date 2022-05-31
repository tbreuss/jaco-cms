export function guid() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    let r = Math.random() * 16 | 0,
      v = c == 'x' ? r : (r & 0x3 | 0x8)
    return v.toString(16)
  })
}

export function closeDropdown() {
  document.querySelectorAll('.dropdown-content').forEach((el) => {
    el.style.display = 'none'
    setTimeout(() => {
      el.style.display = null
    }, 1)
  })
}
