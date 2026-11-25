import React, { useState, useEffect, useMemo, useCallback } from 'react'

const API_BASE = 'https://clickclick.be/calculator/api-dashboard.php'

// ── Belgian number formatting ────────────────────────────────────────────
const fmtEur = v => v.toLocaleString('de-BE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

// ── Style tokens (matches PrintCalculator Tiegel palette) ────────────────
const accent    = '#4200FF'
const dark      = '#000000'
const mid       = '#555555'
const borderClr = '#D8CFC9'
const green     = '#00C853'
const red       = '#FF3B30'
const orange    = '#FF9500'
const mono      = "'Courier New',monospace"

const ls = { fontSize: 10, fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.12em', color: mid, marginBottom: 6 }

// ── Deadline helpers ─────────────────────────────────────────────────────
function addWorkingDaysFromDate(startIso, days) {
  const d = new Date(startIso)
  let added = 0
  while (added < days) {
    d.setDate(d.getDate() + 1)
    const dow = d.getDay()
    if (dow !== 0 && dow !== 6) added++
  }
  return d
}

function getDeadline(order) {
  const start = order.paid_at || order.created_at
  if (!start) return null
  const specs = (order.specs || '').toLowerCase()
  const isExpress = specs.includes('express')
  const isCP = specs.includes('coloured') || specs.includes('colored')
  const days = isExpress ? 2 : isCP ? 11 : 5
  return addWorkingDaysFromDate(start, days)
}

function getDeadlineInfo(order) {
  const deadline = getDeadline(order)
  if (!deadline) return { label: '—', color: mid, urgent: false }
  const now = new Date()
  const diffMs = deadline - now
  const diffDays = diffMs / (1000 * 60 * 60 * 24)
  const specs = (order.specs || '').toLowerCase()
  const isExpress = specs.includes('express')

  let color = green
  if (diffDays < 0) color = red
  else if (diffDays < 1) color = red
  else if (diffDays < 2) color = orange

  const label = deadline.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' })
  return { label, color, urgent: isExpress, deadline }
}

// ── Short product label from specs ───────────────────────────────────────
function shortProductLabel(specs) {
  if (!specs) return '—'
  const s = specs.toLowerCase()
  if (s.includes('poster')) return 'Poster'
  if (s.includes('coloured') || s.includes('colored')) return 'Colour'
  return 'Card'
}

export default function Dashboard() {
  const [orders, setOrders]     = useState(null)
  const [logs, setLogs]         = useState(null)
  const [loading, setLoading]   = useState(true)
  const [error, setError]       = useState(null)
  const [tab, setTab]           = useState('production')
  const [detailOrder, setDetailOrder] = useState(null)
  const [showArchive, setShowArchive] = useState(false)
  const [browsePeriod, setBrowsePeriod] = useState('all') // 'all' | '7d' | '30d' | '90d'

  useEffect(() => {
    Promise.all([
      fetch(API_BASE + '?action=orders').then(r => r.json()),
      fetch(API_BASE + '?action=logs').then(r => r.json()),
    ])
      .then(([o, l]) => { setOrders(o); setLogs(l); setLoading(false) })
      .catch(e => { setError(e.message); setLoading(false) })
  }, [])

  // ── Toggle production checkbox ─────────────────────────────────────────
  const toggleField = useCallback((invoiceNum, field, currentVal) => {
    const newVal = !currentVal
    setOrders(prev => prev.map(o =>
      o.invoice_num === invoiceNum ? { ...o, [field]: newVal } : o
    ))
    fetch(API_BASE + '?action=update', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ invoice_num: invoiceNum, [field]: newVal }),
    }).catch(() => {
      setOrders(prev => prev.map(o =>
        o.invoice_num === invoiceNum ? { ...o, [field]: currentVal } : o
      ))
    })
  }, [])

  // ── Derived data ───────────────────────────────────────────────────────
  const paidOrders = useMemo(() =>
    (orders || []).filter(o => o.status === 'paid'),
    [orders]
  )
  const activeOrders = useMemo(() => paidOrders.filter(o => !o.done), [paidOrders])
  const archivedOrders = useMemo(() => paidOrders.filter(o => o.done), [paidOrders])
  const pendingOrders = useMemo(() =>
    (orders || []).filter(o => o.status !== 'paid'),
    [orders]
  )
  const totalRevenue = useMemo(() =>
    paidOrders.reduce((sum, o) => sum + parseFloat(o.amount_inc || o.amount_eur || 0), 0),
    [paidOrders]
  )

  // ── Production stats (only active orders) ─────────────────────────────
  const prodStats = useMemo(() => {
    const filesWaiting = activeOrders.filter(o => !o.files_checked).length
    const printWaiting = activeOrders.filter(o => o.files_checked && !o.printed).length
    const shipWaiting = activeOrders.filter(o => o.printed && o.delivery === 'yes' && !o.shipped).length
    const pickupWaiting = activeOrders.filter(o => o.printed && o.delivery !== 'yes' && !o.ready_pickup).length
    return { inProgress: activeOrders.length, filesWaiting, printWaiting, shipWaiting, pickupWaiting }
  }, [activeOrders])

  // ── Browse stats (with period filter + ordered vs browsed) ─────────────
  const browseStats = useMemo(() => {
    if (!logs || logs.length === 0) return null

    // Period filter
    const now = new Date()
    const cutoff = browsePeriod === '7d' ? new Date(now - 7 * 86400000)
      : browsePeriod === '30d' ? new Date(now - 30 * 86400000)
      : browsePeriod === '90d' ? new Date(now - 90 * 86400000)
      : null
    const filtered = cutoff
      ? logs.filter(e => e.timestamp && new Date(e.timestamp) >= cutoff)
      : logs

    // Count orders per product type in same period
    const orderedByProduct = {}
    const orderPriceSums = {} // for avg price per product
    const orderedBySize = {}
    const orderedByQty = {}
    const orderedByPrice = { 'under50': 0, '50to100': 0, '100to200': 0, 'over200': 0 }
    paidOrders.forEach(o => {
      const oDate = new Date(o.paid_at || o.created_at)
      if (cutoff && oDate < cutoff) return
      const label = shortProductLabel(o.specs)
      orderedByProduct[label] = (orderedByProduct[label] || 0) + 1
      const price = parseFloat(o.amount_inc || o.amount_eur || 0)
      orderPriceSums[label] = (orderPriceSums[label] || 0) + price
      // Extract size from specs
      const specParts = (o.specs || '').split(' \u00B7 ')
      const sizePart = specParts[1]?.trim() || 'unknown'
      const sizeKey = sizePart.split(' ')[0] // e.g. "A6" from "A6 (105×148 mm)"
      orderedBySize[sizeKey] = (orderedBySize[sizeKey] || 0) + 1
      // Extract quantity
      const qtyPart = specParts.find(p => p.includes('cop'))
      const qtyMatch = qtyPart?.match(/(\d[\d\s']*)/)
      const qtyKey = qtyMatch ? qtyMatch[1].replace(/[\s']/g, '') : 'unknown'
      orderedByQty[qtyKey] = (orderedByQty[qtyKey] || 0) + 1
      // Price range
      if (price < 50) orderedByPrice['under50']++
      else if (price < 100) orderedByPrice['50to100']++
      else if (price < 200) orderedByPrice['100to200']++
      else orderedByPrice['over200']++
    })

    // Tally browse logs
    const tabCalc = {}, tabOrdered = {}
    const browsePriceSums = {} // for avg browse price per product
    const sizeCalc = {}, sizeOrdered = {}
    const qtyCalc = {}, qtyOrdered = {}
    const priceCalc = { 'under50': 0, '50to100': 0, '100to200': 0, 'over200': 0 }
    const dailyCounts = {}

    // Map tab names to short labels for consistency
    const tabToShort = t => {
      const tl = (t || '').toLowerCase()
      if (tl.includes('poster')) return 'Poster'
      if (tl.includes('colour') || tl.includes('color')) return 'Colour'
      return 'Card'
    }

    filtered.forEach(entry => {
      const t = tabToShort(entry.tab)
      tabCalc[t] = (tabCalc[t] || 0) + 1
      const ep = parseFloat(entry.price || 0)
      if (ep > 0) browsePriceSums[t] = (browsePriceSums[t] || 0) + ep

      const s = entry.size || 'unknown'
      sizeCalc[s] = (sizeCalc[s] || 0) + 1

      const q = String(entry.quantity || 'unknown')
      qtyCalc[q] = (qtyCalc[q] || 0) + 1

      const p = parseFloat(entry.price || 0)
      if (p < 50) priceCalc['under50']++
      else if (p < 100) priceCalc['50to100']++
      else if (p < 200) priceCalc['100to200']++
      else priceCalc['over200']++

      const day = (entry.timestamp || '').slice(0, 10)
      if (day) dailyCounts[day] = (dailyCounts[day] || 0) + 1
    })

    // Copy ordered counts using same keys
    Object.keys(orderedByProduct).forEach(k => { tabOrdered[k] = orderedByProduct[k] || 0 })
    Object.keys(orderedBySize).forEach(k => { sizeOrdered[k] = orderedBySize[k] || 0 })
    Object.keys(orderedByQty).forEach(k => { qtyOrdered[k] = orderedByQty[k] || 0 })

    // Total orders in period
    const ordersInPeriod = paidOrders.filter(o => {
      if (!cutoff) return true
      return new Date(o.paid_at || o.created_at) >= cutoff
    }).length

    // Average prices per product
    const avgPrice = {}
    const allProducts = new Set([...Object.keys(tabCalc), ...Object.keys(orderedByProduct)])
    allProducts.forEach(p => {
      const browseAvg = tabCalc[p] > 0 ? (browsePriceSums[p] || 0) / tabCalc[p] : 0
      const orderAvg = orderedByProduct[p] > 0 ? (orderPriceSums[p] || 0) / orderedByProduct[p] : 0
      avgPrice[p] = { browseAvg, orderAvg, browseCount: tabCalc[p] || 0, orderCount: orderedByProduct[p] || 0 }
    })

    return {
      tabCalc, tabOrdered, sizeCalc, sizeOrdered, qtyCalc, qtyOrdered,
      priceCalc, priceOrdered: orderedByPrice,
      dailyCounts, total: filtered.length, ordersInPeriod, avgPrice,
    }
  }, [logs, paidOrders, browsePeriod])

  // ── Render helpers ─────────────────────────────────────────────────────
  const fmtDate = iso => {
    if (!iso) return '—'
    const d = new Date(iso)
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
  }
  const fmtDateShort = iso => {
    if (!iso) return '—'
    const d = new Date(iso)
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' })
  }

  const sortedEntries = obj => Object.entries(obj || {}).sort((a, b) => b[1] - a[1])

  const tabBtn = value => ({
    padding: '8px 20px', border: 'none', cursor: 'pointer', fontSize: 13, fontWeight: 600,
    background: tab === value ? accent : '#fff', color: tab === value ? '#fff' : mid,
    borderRadius: 8, transition: 'all 0.15s',
  })

  const StatCard = ({ label, value, sub, color }) => (
    <div style={{ background: '#fff', borderRadius: 12, padding: '16px 20px', border: `1.5px solid ${borderClr}`, flex: 1, minWidth: 120 }}>
      <div style={ls}>{label}</div>
      <div style={{ fontSize: 28, fontWeight: 800, color: color || dark, letterSpacing: '-0.02em' }}>{value}</div>
      {sub && <div style={{ fontSize: 11, color: mid, marginTop: 2 }}>{sub}</div>}
    </div>
  )

  // ── Stacked bar chart: blue = ordered, grey = calculated only ─────────
  const DualBarChart = ({ calcData, orderedData, label, avgPrices }) => {
    const allKeys = [...new Set([...Object.keys(calcData), ...Object.keys(orderedData || {})])]
    const sorted = allKeys
      .map(k => ({ key: k, calc: calcData[k] || 0, ordered: (orderedData || {})[k] || 0 }))
      .sort((a, b) => b.calc - a.calc)
      .slice(0, 8)
    const max = Math.max(...sorted.map(s => s.calc), 1)

    return (
      <div style={{ background: '#fff', borderRadius: 12, padding: '16px 20px', border: `1.5px solid ${borderClr}` }}>
        <div style={ls}>{label}</div>
        {sorted.map(({ key, calc, ordered }) => {
          const browseOnly = calc - ordered
          return (
            <div key={key} style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 8 }}>
              <div style={{ width: 80, fontSize: 12, fontWeight: 600, color: dark, textAlign: 'right', flexShrink: 0 }}>{key}</div>
              <div style={{ flex: 1, display: 'flex', flexDirection: 'column', gap: 2 }}>
                {/* Stacked bar */}
                <div style={{ display: 'flex', height: 16, borderRadius: 4, overflow: 'hidden', background: '#f0ebe7' }}>
                  {ordered > 0 && (
                    <div style={{ width: `${(ordered / max) * 100}%`, background: accent, height: '100%', minWidth: 2 }}
                      title={`${ordered} ordered`} />
                  )}
                  {browseOnly > 0 && (
                    <div style={{ width: `${(browseOnly / max) * 100}%`, background: '#ccc', height: '100%', minWidth: 2 }}
                      title={`${browseOnly} browsed only`} />
                  )}
                </div>
              </div>
              <div style={{ fontSize: 10, textAlign: 'right', fontFamily: mono, flexShrink: 0, display: 'flex', gap: 6, alignItems: 'center' }}>
                <span>
                  <span style={{ color: accent, fontWeight: 700 }}>{ordered}</span>
                  <span style={{ color: '#ccc' }}> / {calc}</span>
                </span>
                {avgPrices?.[key] && (
                  <span style={{ fontSize: 9, color: mid, fontWeight: 600 }}>
                    ~{'\u20AC'}{fmtEur(avgPrices[key].orderCount > 0 ? avgPrices[key].orderAvg : avgPrices[key].browseAvg)}
                  </span>
                )}
              </div>
            </div>
          )
        })}
        {/* Legend */}
        <div style={{ display: 'flex', gap: 16, marginTop: 8, fontSize: 10, color: mid }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
            <div style={{ width: 10, height: 10, borderRadius: 2, background: accent }} /> Ordered
          </div>
          <div style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
            <div style={{ width: 10, height: 10, borderRadius: 2, background: '#ccc' }} /> Browsed only
          </div>
        </div>
      </div>
    )
  }

  // ── Order detail modal ─────────────────────────────────────────────────
  const OrderDetailModal = ({ order, onClose }) => {
    if (!order) return null
    const specParts = (order.specs || '').split(' \u00B7 ').filter(p => {
      const lower = p.trim().toLowerCase()
      // Remove delivery/amount lines from specs — shown separately
      if (lower.startsWith('delivery')) return false
      if (lower.startsWith('pick-up')) return false
      return true
    })
    const dl = getDeadlineInfo(order)
    return (
      <div onClick={onClose} style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.4)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 9999 }}>
        <div onClick={e => e.stopPropagation()} style={{ background: '#fff', borderRadius: 16, padding: 28, maxWidth: 520, width: '90%', maxHeight: '80vh', overflow: 'auto', boxShadow: '0 20px 60px rgba(0,0,0,0.3)' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 16 }}>
            <div>
              <div style={{ fontSize: 18, fontWeight: 800, color: dark }}>Order {order.invoice_num}</div>
              <div style={{ fontSize: 12, color: mid }}>{fmtDate(order.paid_at || order.created_at)}</div>
            </div>
            <button onClick={onClose} style={{ background: 'none', border: 'none', fontSize: 22, cursor: 'pointer', color: mid, padding: '4px 8px' }}>&times;</button>
          </div>

          {/* Deadline — top */}
          <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 16, padding: '8px 12px', background: '#f9f5f2', borderRadius: 8 }}>
            <div style={{ fontSize: 11, fontWeight: 700, color: mid, textTransform: 'uppercase', letterSpacing: '0.08em' }}>Deadline</div>
            {dl.urgent && <span style={{ fontSize: 14 }}>&#9889;</span>}
            <span style={{ fontSize: 15, fontWeight: 700, color: dl.color }}>{dl.label}</span>
            {dl.urgent && <span style={{ fontSize: 11, color: red, fontWeight: 600 }}>EXPRESS</span>}
          </div>

          {/* Customer */}
          <div style={{ marginBottom: 16 }}>
            <div style={ls}>Customer</div>
            <div style={{ fontSize: 14, fontWeight: 600, color: dark }}>{order.customer_name || '—'}</div>
            {order.customer_company && <div style={{ fontSize: 13, color: mid }}>{order.customer_company}</div>}
            <div style={{ fontSize: 13, color: mid }}>{order.customer_email}</div>
            {order.customer_phone && <div style={{ fontSize: 13, color: mid }}>{order.customer_phone}</div>}
            {order.customer_address && <div style={{ fontSize: 13, color: mid, marginTop: 4 }}>{order.customer_address}, {order.customer_zip} {order.customer_city}</div>}
          </div>

          {/* Specs */}
          <div style={{ marginBottom: 16 }}>
            <div style={ls}>Order Details</div>
            <div style={{ background: '#f9f5f2', borderRadius: 10, padding: '12px 16px' }}>
              {specParts.map((part, i) => (
                <div key={i} style={{ fontSize: 13, color: dark, padding: '3px 0', borderBottom: i < specParts.length - 1 ? '1px solid #ede8e4' : 'none' }}>
                  {part.trim()}
                </div>
              ))}
            </div>
          </div>

          {/* Delivery */}
          <div style={{ background: '#f0f4ff', borderRadius: 10, padding: '12px 16px', marginBottom: 16 }}>
            <div style={{ ...ls, color: '#1565c0' }}>Delivery</div>
            <div style={{ fontSize: 14, fontWeight: 700, color: '#1565c0' }}>{order.delivery === 'yes' ? 'DPD / Bike' : 'Pick-up at shop'}</div>
            {order.delivery === 'yes' && order.ship_address && (
              <div style={{ fontSize: 12, color: mid, marginTop: 4 }}>{order.ship_address}, {order.ship_zip} {order.ship_city}</div>
            )}
          </div>

          {/* VAT */}
          {order.vat_number && (
            <div style={{ fontSize: 12, color: mid }}>VAT: {order.vat_number} &middot; {order.doc_type === 'invoice' ? 'Invoice' : 'Receipt'}</div>
          )}
        </div>
      </div>
    )
  }

  // ── Repeat customers (same name, multiple orders in same week) ────────
  const repeatCustomers = useMemo(() => {
    const set = new Set()
    const byWeek = {}
    paidOrders.forEach(o => {
      const name = (o.customer_name || '').toLowerCase().trim()
      if (!name) return
      const d = new Date(o.paid_at || o.created_at)
      // ISO week key: year + week number
      const jan1 = new Date(d.getFullYear(), 0, 1)
      const week = Math.ceil(((d - jan1) / 86400000 + jan1.getDay() + 1) / 7)
      const key = `${d.getFullYear()}-W${week}-${name}`
      byWeek[key] = (byWeek[key] || 0) + 1
    })
    paidOrders.forEach(o => {
      const name = (o.customer_name || '').toLowerCase().trim()
      if (!name) return
      const d = new Date(o.paid_at || o.created_at)
      const jan1 = new Date(d.getFullYear(), 0, 1)
      const week = Math.ceil(((d - jan1) / 86400000 + jan1.getDay() + 1) / 7)
      const key = `${d.getFullYear()}-W${week}-${name}`
      if (byWeek[key] > 1) set.add(o.invoice_num)
    })
    return set
  }, [paidOrders])

  // ── Production table row ───────────────────────────────────────────────
  const ProductionRow = ({ o, faded }) => {
    const dl = getDeadlineInfo(o)
    return (
      <tr style={{ borderBottom: '1px solid #f0ebe7', opacity: faded ? 0.4 : 1, background: faded ? '#fafaf8' : '#fff' }}>
        <td style={{ padding: '8px 8px', whiteSpace: 'nowrap', color: mid, textAlign: 'center', fontSize: 11 }}>{fmtDateShort(o.paid_at || o.created_at)}</td>
        <td style={{ padding: '8px 6px', fontFamily: mono, fontWeight: 600, color: accent, textAlign: 'center', fontSize: 11 }}>{o.invoice_num}</td>
        <td style={{ padding: '8px 6px', color: dark, fontSize: 11 }}>
          <div style={{ fontWeight: 600 }}>
            {o.customer_name || '—'}
            {repeatCustomers.has(o.invoice_num) && <span title="Repeat customer this week" style={{ color: orange, marginLeft: 3 }}>&#9733;</span>}
          </div>
        </td>
        <td style={{ padding: '8px 6px' }}>
          <button
            onClick={() => setDetailOrder(o)}
            style={{
              background: '#f5f0ff', border: '1px solid #d8d0f0', borderRadius: 4,
              padding: '2px 8px', cursor: 'pointer', fontSize: 11, color: accent,
              fontWeight: 600, whiteSpace: 'nowrap',
            }}
          >
            {shortProductLabel(o.specs)}
          </button>
        </td>
        <td style={{ padding: '8px 6px', textAlign: 'center', whiteSpace: 'nowrap' }}>
          <span style={{ fontWeight: 700, color: dl.color, fontSize: 11 }}>
            {dl.urgent && <span title="Express">&#9889;</span>}
            {dl.label}
          </span>
        </td>
        <td style={{ padding: '8px 4px', textAlign: 'center' }}>
          <input type="checkbox" checked={!!o.files_checked} onChange={() => toggleField(o.invoice_num, 'files_checked', o.files_checked)}
            style={{ width: 16, height: 16, accentColor: accent, cursor: 'pointer' }} />
        </td>
        <td style={{ padding: '8px 4px', textAlign: 'center' }}>
          <input type="checkbox" checked={!!o.printed} onChange={() => toggleField(o.invoice_num, 'printed', o.printed)}
            style={{ width: 16, height: 16, accentColor: accent, cursor: 'pointer' }} />
        </td>
        <td style={{ padding: '8px 4px', textAlign: 'center' }}>
          {o.delivery === 'yes' ? (
            <input type="checkbox" checked={!!o.shipped} onChange={() => toggleField(o.invoice_num, 'shipped', o.shipped)}
              style={{ width: 16, height: 16, accentColor: accent, cursor: 'pointer' }} />
          ) : (
            <span style={{ fontSize: 11, color: '#ddd' }}>—</span>
          )}
        </td>
        <td style={{ padding: '8px 4px', textAlign: 'center' }}>
          {o.delivery !== 'yes' ? (
            <input type="checkbox" checked={!!o.ready_pickup} onChange={() => toggleField(o.invoice_num, 'ready_pickup', o.ready_pickup)}
              style={{ width: 16, height: 16, accentColor: accent, cursor: 'pointer' }} />
          ) : (
            <span style={{ fontSize: 11, color: '#ddd' }}>—</span>
          )}
        </td>
        <td style={{ padding: '8px 4px', textAlign: 'center' }}>
          <input type="checkbox" checked={!!o.done} onChange={() => toggleField(o.invoice_num, 'done', o.done)}
            style={{ width: 16, height: 16, accentColor: green, cursor: 'pointer' }} />
        </td>
      </tr>
    )
  }

  // ── Loading / Error ────────────────────────────────────────────────────
  if (loading) return (
    <div style={{ padding: 40, textAlign: 'center', color: mid }}>Loading dashboard data...</div>
  )
  if (error) return (
    <div style={{ padding: 40, textAlign: 'center', color: red }}>
      Could not load dashboard data: {error}<br />
      <span style={{ fontSize: 12, color: mid }}>Make sure the PHP files are deployed on the server.</span>
    </div>
  )

  const prodHeaders = ['Date', 'Invoice', 'Customer', 'Order', 'Deadline', 'Files', 'Printed', 'Shipped', 'Pick-up', 'Done']

  return (
    <div>
      {/* Order detail modal */}
      <OrderDetailModal order={detailOrder} onClose={() => setDetailOrder(null)} />

      {/* Sub-tabs */}
      <div style={{ display: 'flex', gap: 8, marginBottom: 20 }}>
        <button onClick={() => setTab('production')} style={tabBtn('production')}>Production</button>
        <button onClick={() => setTab('orders')} style={tabBtn('orders')}>Paid Orders</button>
        <button onClick={() => setTab('browse')} style={tabBtn('browse')}>Browse Stats</button>
      </div>

      {/* ═══════════ PRODUCTION TAB ═══════════ */}
      {tab === 'production' && (
        <>
          {/* Summary cards */}
          <div style={{ display: 'flex', gap: 12, marginBottom: 20, flexWrap: 'wrap' }}>
            <StatCard label="In Progress" value={prodStats.inProgress} color={accent} />
            <StatCard label="Files to Check" value={prodStats.filesWaiting} color={prodStats.filesWaiting > 0 ? red : green} />
            <StatCard label="To Print" value={prodStats.printWaiting} color={prodStats.printWaiting > 0 ? orange : green} />
            <StatCard label="To Ship" value={prodStats.shipWaiting} color={prodStats.shipWaiting > 0 ? orange : green} />
            <StatCard label="To Pick Up" value={prodStats.pickupWaiting} color={prodStats.pickupWaiting > 0 ? orange : green} />
          </div>

          {/* Active orders */}
          {activeOrders.length === 0 && archivedOrders.length === 0 ? (
            <div style={{ background: '#fff', borderRadius: 12, padding: 32, textAlign: 'center', border: `1.5px solid ${borderClr}`, color: mid }}>
              No orders yet.
            </div>
          ) : (
            <>
              <div style={{ background: '#fff', borderRadius: 12, border: `1.5px solid ${borderClr}`, overflow: 'hidden' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
                  <thead>
                    <tr style={{ background: '#f9f5f2' }}>
                      {prodHeaders.map(h => (
                        <th key={h} style={{ textAlign: 'center', padding: '10px 6px', fontSize: 9, fontWeight: 700, letterSpacing: '0.08em', color: mid, textTransform: 'uppercase', borderBottom: `2px solid ${borderClr}`, whiteSpace: 'nowrap' }}>{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {activeOrders.length === 0 ? (
                      <tr><td colSpan={prodHeaders.length} style={{ padding: 20, textAlign: 'center', color: mid, fontSize: 12 }}>All jobs done!</td></tr>
                    ) : (
                      activeOrders.map(o => <ProductionRow key={o.invoice_num} o={o} faded={false} />)
                    )}
                  </tbody>
                </table>
              </div>

              {/* Archive toggle */}
              {archivedOrders.length > 0 && (
                <div style={{ marginTop: 16 }}>
                  <button
                    onClick={() => setShowArchive(!showArchive)}
                    style={{ background: 'none', border: 'none', cursor: 'pointer', fontSize: 12, color: mid, fontWeight: 600, padding: '4px 0' }}
                  >
                    {showArchive ? '▾' : '▸'} Archive ({archivedOrders.length} completed)
                  </button>
                  {showArchive && (
                    <div style={{ background: '#fff', borderRadius: 12, border: `1.5px solid ${borderClr}`, overflow: 'hidden', marginTop: 8 }}>
                      <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
                        <thead>
                          <tr style={{ background: '#f9f5f2' }}>
                            {prodHeaders.map(h => (
                              <th key={h} style={{ textAlign: 'center', padding: '8px 6px', fontSize: 9, fontWeight: 700, letterSpacing: '0.08em', color: '#ccc', textTransform: 'uppercase', borderBottom: `2px solid ${borderClr}`, whiteSpace: 'nowrap' }}>{h}</th>
                            ))}
                          </tr>
                        </thead>
                        <tbody>
                          {archivedOrders.map(o => <ProductionRow key={o.invoice_num} o={o} faded={true} />)}
                        </tbody>
                      </table>
                    </div>
                  )}
                </div>
              )}
            </>
          )}
        </>
      )}

      {/* ═══════════ ORDERS TAB ═══════════ */}
      {tab === 'orders' && (
        <>
          <div style={{ display: 'flex', gap: 12, marginBottom: 20, flexWrap: 'wrap' }}>
            <StatCard label="Total Orders" value={paidOrders.length} color={accent} />
            <StatCard label="Revenue (incl. VAT)" value={`\u20AC${fmtEur(totalRevenue)}`} color={green} />
            <StatCard label="Pending" value={pendingOrders.length} sub="not yet paid" color={pendingOrders.length > 0 ? red : mid} />
            <StatCard label="Avg. Order" value={paidOrders.length > 0 ? `\u20AC${fmtEur(totalRevenue / paidOrders.length)}` : '—'} />
          </div>

          {paidOrders.length === 0 ? (
            <div style={{ background: '#fff', borderRadius: 12, padding: 32, textAlign: 'center', border: `1.5px solid ${borderClr}`, color: mid }}>
              No paid orders yet. Orders will appear here once customers complete payment.
            </div>
          ) : (
            <div style={{ background: '#fff', borderRadius: 12, border: `1.5px solid ${borderClr}`, overflow: 'hidden' }}>
              <div style={{ overflowX: 'auto' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
                  <thead>
                    <tr style={{ background: '#f9f5f2' }}>
                      {['Date', 'Invoice', 'Customer', 'Amount', 'Delivery', 'Status'].map(h => (
                        <th key={h} style={{ textAlign: 'left', padding: '10px 12px', fontSize: 10, fontWeight: 700, letterSpacing: '0.08em', color: mid, textTransform: 'uppercase', borderBottom: `2px solid ${borderClr}`, whiteSpace: 'nowrap' }}>{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {paidOrders.map(o => (
                      <tr key={o.invoice_num} style={{ borderBottom: '1px solid #f0ebe7' }}>
                        <td style={{ padding: '10px 12px', whiteSpace: 'nowrap', color: dark }}>{fmtDate(o.paid_at || o.created_at)}</td>
                        <td style={{ padding: '10px 12px', fontFamily: mono, fontWeight: 600, color: accent }}>{o.invoice_num}</td>
                        <td style={{ padding: '10px 12px', color: dark }}>
                          <div style={{ fontWeight: 600 }}>{o.customer_name || '—'}</div>
                          {o.customer_company && <div style={{ fontSize: 11, color: mid }}>{o.customer_company}</div>}
                          <div style={{ fontSize: 11, color: mid }}>{o.customer_email}</div>
                        </td>
                        <td style={{ padding: '10px 12px', fontFamily: mono, fontWeight: 700, color: green, whiteSpace: 'nowrap' }}>
                          {'\u20AC'}{fmtEur(parseFloat(o.amount_inc || o.amount_eur || 0))}
                        </td>
                        <td style={{ padding: '10px 12px' }}>
                          <span style={{
                            display: 'inline-block', padding: '2px 8px', borderRadius: 6, fontSize: 11, fontWeight: 600,
                            background: o.delivery === 'yes' ? '#e3f2fd' : '#f5f5f5',
                            color: o.delivery === 'yes' ? '#1565c0' : '#999',
                          }}>
                            {o.delivery === 'yes' ? 'Delivery' : 'Pick-up'}
                          </span>
                        </td>
                        <td style={{ padding: '10px 12px' }}>
                          <span style={{
                            display: 'inline-block', padding: '2px 8px', borderRadius: 6, fontSize: 11, fontWeight: 600,
                            background: o.status === 'paid' ? '#e8f5e9' : '#fff3e0',
                            color: o.status === 'paid' ? '#2e7d32' : '#ef6c00',
                          }}>
                            {o.status === 'paid' ? 'Paid' : o.status}
                          </span>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {pendingOrders.length > 0 && (
            <div style={{ marginTop: 20 }}>
              <div style={{ ...ls, marginBottom: 10 }}>Pending / Unpaid</div>
              <div style={{ background: '#fff', borderRadius: 12, border: `1.5px solid ${borderClr}`, overflow: 'hidden' }}>
                <div style={{ overflowX: 'auto' }}>
                  <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
                    <tbody>
                      {pendingOrders.map(o => (
                        <tr key={o.invoice_num} style={{ borderBottom: '1px solid #f0ebe7' }}>
                          <td style={{ padding: '10px 12px', whiteSpace: 'nowrap', color: mid }}>{fmtDate(o.created_at)}</td>
                          <td style={{ padding: '10px 12px', fontFamily: mono, color: mid }}>{o.invoice_num}</td>
                          <td style={{ padding: '10px 12px', color: mid }}>{o.customer_name || '—'}</td>
                          <td style={{ padding: '10px 12px', color: mid, fontSize: 12 }}>{(o.specs || '—').split(' \u00B7 ').slice(0, 2).join(' \u00B7 ')}...</td>
                          <td style={{ padding: '10px 12px', fontFamily: mono, color: mid }}>{'\u20AC'}{fmtEur(parseFloat(o.amount_eur || 0))}</td>
                          <td style={{ padding: '10px 12px' }}>
                            <span style={{ display: 'inline-block', padding: '2px 8px', borderRadius: 6, fontSize: 11, fontWeight: 600, background: '#fff3e0', color: '#ef6c00' }}>
                              {o.status || 'pending'}
                            </span>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}
        </>
      )}

      {/* ═══════════ BROWSE STATS TAB ═══════════ */}
      {tab === 'browse' && (
        <>
          {/* Period filter */}
          <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 16 }}>
            <span style={{ fontSize: 11, fontWeight: 700, color: mid, textTransform: 'uppercase', letterSpacing: '0.08em' }}>Period:</span>
            {[
              { value: '7d', label: '7 days' },
              { value: '30d', label: '30 days' },
              { value: '90d', label: '90 days' },
              { value: 'all', label: 'All time' },
            ].map(({ value, label }) => (
              <button key={value} onClick={() => setBrowsePeriod(value)} style={{
                padding: '4px 12px', border: 'none', cursor: 'pointer', fontSize: 11, fontWeight: 600,
                background: browsePeriod === value ? accent : '#f0ebe7', color: browsePeriod === value ? '#fff' : mid,
                borderRadius: 6, transition: 'all 0.15s',
              }}>{label}</button>
            ))}
          </div>

          {/* Summary cards */}
          <div style={{ display: 'flex', gap: 12, marginBottom: 20, flexWrap: 'wrap' }}>
            <StatCard label="Calculations" value={browseStats?.total || 0} color={accent} />
            <StatCard label="Orders" value={browseStats?.ordersInPeriod || 0} color={green} />
            <StatCard
              label="Conversion"
              value={browseStats?.total > 0 ? `${((browseStats.ordersInPeriod / browseStats.total) * 100).toFixed(1)}%` : '—'}
              sub="calculated \u2192 ordered"
              color={browseStats?.total > 0 && browseStats.ordersInPeriod / browseStats.total > 0.05 ? green : orange}
            />
            <StatCard
              label="Browse Only"
              value={browseStats ? browseStats.total - browseStats.ordersInPeriod : 0}
              sub="didn't order"
              color={mid}
            />
          </div>

          {!browseStats ? (
            <div style={{ background: '#fff', borderRadius: 12, padding: 32, textAlign: 'center', border: `1.5px solid ${borderClr}`, color: mid }}>
              No browse data yet. Stats will appear once the logging is active on the customer calculator.
            </div>
          ) : (
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))', gap: 16 }}>
              <DualBarChart calcData={browseStats.tabCalc} orderedData={browseStats.tabOrdered} label="Interest by Product" avgPrices={browseStats.avgPrice} />
              <DualBarChart calcData={browseStats.sizeCalc} orderedData={browseStats.sizeOrdered} label="Interest by Size" />
              <DualBarChart calcData={browseStats.qtyCalc} orderedData={browseStats.qtyOrdered} label="Interest by Quantity" />
              <DualBarChart
                calcData={{
                  'Under \u20AC50': browseStats.priceCalc.under50,
                  '\u20AC50-100': browseStats.priceCalc['50to100'],
                  '\u20AC100-200': browseStats.priceCalc['100to200'],
                  'Over \u20AC200': browseStats.priceCalc.over200,
                }}
                orderedData={{
                  'Under \u20AC50': browseStats.priceOrdered.under50,
                  '\u20AC50-100': browseStats.priceOrdered['50to100'],
                  '\u20AC100-200': browseStats.priceOrdered['100to200'],
                  'Over \u20AC200': browseStats.priceOrdered.over200,
                }}
                label="Price Range Interest"
              />

              {/* Daily volume chart */}
              <div style={{ gridColumn: '1 / -1', background: '#fff', borderRadius: 12, padding: '16px 20px', border: `1.5px solid ${borderClr}` }}>
                <div style={ls}>Daily Calculations ({browsePeriod === 'all' ? 'last 30 days' : `last ${browsePeriod.replace('d', ' days')}`})</div>
                <div style={{ display: 'flex', alignItems: 'flex-end', gap: 4, height: 100, marginTop: 8 }}>
                  {(() => {
                    const numDays = browsePeriod === '7d' ? 7 : browsePeriod === '90d' ? 90 : 30
                    const days = []
                    for (let i = numDays - 1; i >= 0; i--) {
                      const d = new Date(); d.setDate(d.getDate() - i)
                      days.push(d.toISOString().slice(0, 10))
                    }
                    const max = Math.max(...days.map(d => browseStats.dailyCounts[d] || 0), 1)
                    // Show every Nth label to avoid crowding
                    const labelEvery = numDays > 30 ? 7 : numDays > 14 ? 3 : 1
                    return days.map((d, i) => {
                      const count = browseStats.dailyCounts[d] || 0
                      const h = (count / max) * 80
                      return (
                        <div key={d} style={{ flex: 1, display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 2 }}>
                          <div style={{ fontSize: 9, color: mid, fontFamily: mono }}>{count || ''}</div>
                          <div style={{ width: '100%', height: h, background: count > 0 ? accent : '#f0ebe7', borderRadius: 3, minHeight: 2 }} />
                          {(i % labelEvery === 0) && (
                            <div style={{ fontSize: 8, color: '#bbb', transform: 'rotate(-45deg)', whiteSpace: 'nowrap', marginTop: 4 }}>
                              {d.slice(5)}
                            </div>
                          )}
                        </div>
                      )
                    })
                  })()}
                </div>
              </div>
            </div>
          )}
        </>
      )}
    </div>
  )
}
