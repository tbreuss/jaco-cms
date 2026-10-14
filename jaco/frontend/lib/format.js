const pad2 = (n) => String(n).padStart(2, '0')

const toDate = (timestamp) => new Date(timestamp * 1000)

// Full timestamp for tooltips: 12.10.2026 20:04:43
export const formatDateTimeFull = (timestamp) => {
  if (timestamp == null) {
    return ''
  }
  const date = toDate(timestamp)
  return `${pad2(date.getDate())}.${pad2(date.getMonth() + 1)}.${date.getFullYear()} ${pad2(date.getHours())}:${pad2(date.getMinutes())}:${pad2(date.getSeconds())}`
}

// Compact timestamp for lists: 12.10.26 20:04
export const formatDateTimeCompact = (timestamp) => {
  if (timestamp == null) {
    return ''
  }
  const date = toDate(timestamp)
  return `${pad2(date.getDate())}.${pad2(date.getMonth() + 1)}.${String(date.getFullYear()).slice(-2)} ${pad2(date.getHours())}:${pad2(date.getMinutes())}`
}
