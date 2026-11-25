import * as XLSX from 'xlsx'
import { XLSX_FILE, applySettings } from './constants.js'

export async function loadExcelData() {
  const response = await fetch(XLSX_FILE)
  if (!response.ok) throw new Error(`HTTP ${response.status}`)
  const ab = await response.arrayBuffer()
  return parseExcel(ab)
}

function parseExcel(ab) {
  const wb     = XLSX.read(ab, { type: 'array' })
  const result = { paperData: null, creative: null, settings: null, paperTypes: null }

  // CMYK Papers sheet
  const ws1 = wb.Sheets['CMYK Papers']
  if (ws1) {
    const rows = XLSX.utils.sheet_to_json(ws1)
    const pd   = {}
    rows.forEach(r => {
      const { paper_type: pt, gsm, cuts_per_mother: cuts, raw_cost: raw } = r
      if (!pt || !gsm || !cuts || raw == null) return
      if (!pd[pt]) pd[pt] = {}
      pd[pt][String(gsm)] = { s: cuts, cost: raw }
    })
    if (Object.keys(pd).length > 0) result.paperData = pd
  }

  // Coloured Papers sheet (fallback: Creative Papers for backwards compat)
  const ws2 = wb.Sheets['Coloured Papers'] || wb.Sheets['Creative Papers']
  if (ws2) {
    const rows = XLSX.utils.sheet_to_json(ws2)
    const cp   = rows
      .map(r => ({
        id:        r.paper_id,
        name:      r.name,
        brand:     r.brand,
        gsm:       r.gsm,
        cuts:      r.cuts_per_mother,
        rawCost:   r.raw_cost,
        colorCost: r.color_cost || 0,
        hex:       '#' + (r.hex || '').replace('#', ''),
      }))
      .filter(r => r.id && r.name)
    if (cp.length > 0) result.creative = cp
  }

  // Settings sheet
  const ws3 = wb.Sheets['Settings']
  if (ws3) {
    const rows = XLSX.utils.sheet_to_json(ws3)
    const s    = {}
    rows.forEach(r => { if (r.setting && r.value != null) s[r.setting] = r.value })
    if (Object.keys(s).length > 0) {
      result.settings = s
      applySettings(s)   // ← mutates SETTINGS in constants.js immediately
    }
  }

  // Paper Types sheet (display names)
  const ws4 = wb.Sheets['Paper Types']
  if (ws4) {
    const rows = XLSX.utils.sheet_to_json(ws4)
    const pt   = {}
    rows.forEach(r => { if (r.paper_type && r.display_name) pt[r.paper_type] = r.display_name })
    if (Object.keys(pt).length > 0) result.paperTypes = pt
  }

  return result
}
