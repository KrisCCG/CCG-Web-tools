import T from './translations.js'

const DAYS_EN   = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday']
const MONTHS_EN = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']

export function addWorkingDays(n) {
  const d = new Date()
  let added = 0
  while (added < n) {
    d.setDate(d.getDate() + 1)
    const day = d.getDay()
    if (day !== 0 && day !== 6) added++
  }
  return d
}

export function formatDate(d, lang) {
  if (!lang || !T.days) {
    return `${DAYS_EN[d.getDay()]} ${d.getDate()} ${MONTHS_EN[d.getMonth()]} ${d.getFullYear()}`
  }
  const days = T.days[lang] || T.days.en || DAYS_EN
  const months = T.months[lang] || T.months.en || MONTHS_EN
  // Months in translations are full names; use first 3 chars for short format
  const monthShort = months[d.getMonth()].slice(0, 3)
  return `${days[d.getDay()]} ${d.getDate()} ${monthShort} ${d.getFullYear()}`
}
