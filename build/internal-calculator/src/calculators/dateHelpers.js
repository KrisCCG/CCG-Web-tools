// ── Date helpers ─────────────────────────────────────────────────────────────

/**
 * Add n working days to today, skipping Saturdays (6) and Sundays (0).
 */
export function addWorkingDays(days) {
  const date = new Date()
  let added = 0
  while (added < days) {
    date.setDate(date.getDate() + 1)
    const dow = date.getDay()   // 0 = Sun, 6 = Sat
    if (dow !== 0 && dow !== 6) added++
  }
  return date
}

/**
 * Format a Date as "Monday 23 June"
 */
export function formatDate(date) {
  return date.toLocaleDateString('en-GB', {
    weekday: 'long',
    day:     'numeric',
    month:   'long',
  })
}
