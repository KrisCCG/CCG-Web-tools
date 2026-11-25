import React, { useState, useMemo, useEffect } from 'react'
import PaperPicker from '../components/PaperPicker.jsx'
import Dashboard from '../components/Dashboard.jsx'
import { calcPrice } from '../utils/pricing.js'
import { loadExcelData } from '../utils/excelLoader.js'
import { addWorkingDays, formatDate } from '../utils/dateHelpers.js'
import {
  BRAND, FLYER_SIZES, POSTER_SIZES, SETTINGS, LONG_SIZES, LONG_SIZE_PAPERS,
  DEFAULT_PAPER_DATA, DEFAULT_CREATIVE_PAPERS, PRINTABLE_W, PRINTABLE_H,} from '../utils/constants.js'

// ── Responsive breakpoint hook ───────────────────────────────────────────────
const useBreakpoint = () => {
  const [width, setWidth] = useState(
    typeof window !== 'undefined' ? window.innerWidth : 1200
  )
  useEffect(() => {
    const handler = () => setWidth(window.innerWidth)
    window.addEventListener('resize', handler)
    return () => window.removeEventListener('resize', handler)
  }, [])
  return {
    isMobile:  width < 768,
    isTablet:  width >= 768 && width < 1100,
    isDesktop: width >= 1100,
    isWide:    width >= 768,
  }
}

export default function PrintCalculator() {
  const bp = useBreakpoint()

  // ── State ────────────────────────────────────────────────────────────────
  const [mode,            setMode]            = useState('flyer_cmyk')
  const [paperType,       setPaperType]       = useState('multiart')
  const [weight,          setWeight]          = useState('130')
  const [size,            setSize]            = useState('A6')
  const [customW,         setCustomW]         = useState('150')
  const [customH,         setCustomH]         = useState('210')
  const [quantity,        setQuantity]        = useState('250')
  const [sides,           setSides]           = useState('single')
  const [creativePaper,   setCreativePaper]   = useState('cp_forest')
  const [versions,        setVersions]        = useState(1)
  const [diffColors,      setDiffColors]      = useState(false)
  const [versionPaperIds, setVersionPaperIds] = useState(Array(SETTINGS.MAX_VERSIONS).fill('cp_forest'))
  const [express,         setExpress]         = useState(false)
  const [frontInk,        setFrontInk]        = useState('white')
  const [backInk,         setBackInk]         = useState('black')
  const [mobileQuoteOpen, setMobileQuoteOpen] = useState(false)

  // ── Excel data ───────────────────────────────────────────────────────────
  const [livePaperData,   setLivePaperData]   = useState(DEFAULT_PAPER_DATA)
  const [liveCreative,    setLiveCreative]    = useState(DEFAULT_CREATIVE_PAPERS)
  const [livePaperTypes,  setLivePaperTypes]  = useState(null)
  const [dataSource,      setDataSource]      = useState('defaults')

  useEffect(() => {
    loadExcelData()
      .then(d => {
        if (d.paperData)  setLivePaperData(d.paperData)
        if (d.creative)   setLiveCreative(d.creative)
        if (d.paperTypes) setLivePaperTypes(d.paperTypes)
        setDataSource('excel')
      })
      .catch(e => {
        console.warn('⚠ Could not load Excel — using defaults:', e.message)
        setDataSource('defaults (file not found)')
      })
  }, [])

  

  // ── Derived flags ────────────────────────────────────────────────────────
  const isFlyer           = mode === 'flyer_cmyk' || mode === 'flyer_cp'
  const isCP              = mode === 'flyer_cp'
  const isPoster          = mode === 'poster'
  const canExpress        = !isCP
  const effectiveExpress  = express && canExpress
  const effectiveVersions = isFlyer || isPoster ? versions : 1

  const parsedQty = Math.max(1, parseInt(quantity) || 1)
  const parsedW   = Math.max(10, Math.min(PRINTABLE_W, parseInt(customW) || 100))
  const parsedH   = Math.max(10, Math.min(PRINTABLE_H, parseInt(customH) || 100))

  // ── Paper options ────────────────────────────────────────────────────────
  const SHORT_NAMES  = { multiart: 'MultiArt Silk', recystar: 'RecyStar', biotop: 'Biotop' }
  const allPaperOpts = Object.fromEntries(
    Object.entries(livePaperData).map(([k, v]) => [k, {
      name:    SHORT_NAMES[k] || (livePaperTypes?.[k]) || k,
      weights: Object.keys(v).sort((a, b) => parseInt(a) - parseInt(b)),
    }])
  )
  const sizeOpts = isFlyer ? FLYER_SIZES : POSTER_SIZES
  const pt       = isCP ? { name: 'Coloured Papers' } : (allPaperOpts[paperType] || Object.values(allPaperOpts)[0])
  const selCP    = liveCreative.find(p => p.id === creativePaper) || liveCreative[0]

  // ── Ink helpers ──────────────────────────────────────────────────────────
  const INK_OPTIONS = ['white', 'black', 'colour']
  const inkLabel  = ink => ink === 'white' ? 'White' : ink === 'black' ? 'Black' : 'Colour'
  const printDesc = isCP
    ? (sides === 'double' ? `Recto: ${inkLabel(frontInk)} · Verso: ${inkLabel(backInk)}` : inkLabel(frontInk))
    : 'Full colour (CMYK)'

  // ── Mode handler ─────────────────────────────────────────────────────────
  const handleMode = m => {
    setMode(m)
    if (m === 'flyer_cp')   { setExpress(false); setSize('A6') }
    if (m === 'flyer_cmyk') { setPaperType('multiart'); setWeight('130'); setSize('A6') }
    if (m === 'poster')     { setPaperType('multiart'); setWeight('130'); setSize('A4'); setDiffColors(false) }
  }
  // The tall poster is banner-fed and only MultiArt is cut for it, so the other
  // stocks are greyed out while it is selected. Mirrors SIZE_PAPERS in calc.php.
  const longPaperBlocked = k => LONG_SIZES.includes(size) && !LONG_SIZE_PAPERS.includes(k)

  const handlePaper = val => {
    setPaperType(val)
    const ws = allPaperOpts[val]?.weights || ['130']
    if (!ws.includes(weight)) setWeight(ws[0])
  }

  useEffect(() => {
    if (LONG_SIZES.includes(size) && !LONG_SIZE_PAPERS.includes(paperType)) {
      handlePaper(LONG_SIZE_PAPERS[0])
    }
  }, [size])

  // ── Price calculation ────────────────────────────────────────────────────
  const result = useMemo(() => calcPrice({
    paperType, weight: parseInt(weight), size,
    quantity: parsedQty, sides, printMode: 'cmyk',
    versions: effectiveVersions,
    diffColors: isCP ? diffColors : false,
    customW: parsedW, customH: parsedH,
    express: effectiveExpress,
    frontInk: isCP ? (frontInk === 'colour' ? ['colour'] : [frontInk]) : null,
    backInk:  isCP ? (backInk === 'colour' ? ['colour'] : [backInk])  : null,
    creativePaperId: isCP ? creativePaper : null,
    versionPaperIds: isCP && diffColors ? versionPaperIds.slice(0, versions) : null,
    paperData:       livePaperData,
    creativePapers:  liveCreative,
  }), [
    mode, paperType, weight, size, parsedQty, sides, versions, diffColors,
    versionPaperIds, parsedW, parsedH, effectiveExpress, frontInk, backInk,
    creativePaper, livePaperData, liveCreative, dataSource,
  ])

  const bd = result?.breakdown

  // ── Style tokens (Flyer palette: cream + black + indigo accent) ──────────
  const accent    = '#3211fc'
  const accentHi  = '#5336ff'
  const red       = '#ef3226'
  const green     = BRAND.green
  const ink       = '#0a0a0a'
  const dark      = '#0a0a0a'
  const mid       = '#6c645b'
  const borderClr = '#0a0a0a'
  const bgPage    = '#fef3ea'
  const cardBg    = '#f7e9dc'
  const cardHi    = '#eedfc8'
  const cream     = '#fef3ea'
  const panelBg   = '#1a0066'
  const shadow    = `5px 5px 0 ${ink}`
  const shadowSm  = `3px 3px 0 ${ink}`
  const mono      = "'Courier New',monospace"

  const ls        = { fontSize: 11, fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.12em', color: ink, marginBottom: 8 }
  const darkLs    = { fontSize: 10, fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.12em', color: '#aaa0cc', marginBottom: 6 }
  const ss        = { width: '100%', padding: '13px 36px 13px 14px', borderRadius: 12, border: `2px solid ${ink}`, fontSize: 14, fontWeight: 600, background: cardBg, color: ink, outline: 'none', cursor: 'pointer', appearance: 'none', fontFamily: 'inherit', backgroundImage: "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 5 5-5' stroke='%230a0a0a' stroke-width='1.8' fill='none'/%3E%3C/svg%3E\")", backgroundRepeat: 'no-repeat', backgroundPosition: 'right 14px center' }
  const iStyle    = { width: '100%', padding: '13px 14px', borderRadius: 12, border: `2px solid ${ink}`, fontSize: 14, fontWeight: 600, background: cardBg, color: ink, outline: 'none', fontFamily: 'inherit' }
  const tb        = on => ({ flex: 1, padding: '13px 14px', border: `2px solid ${ink}`, borderRadius: 12, cursor: 'pointer', fontSize: 14, fontWeight: 600, fontFamily: 'inherit', background: on ? accent : cardBg, color: on ? cream : ink, boxShadow: on ? shadowSm : 'none', transition: 'all 0.1s' })
  const mb        = on => ({ flex: 1, padding: '14px 16px', border: `2px solid ${ink}`, cursor: 'pointer', fontSize: 14, fontWeight: 700, letterSpacing: '-0.01em', fontFamily: 'inherit', background: on ? accent : cardBg, color: on ? cream : ink, borderRadius: 12, transition: 'all 0.1s', boxShadow: on ? shadowSm : 'none' })

  const rightW   = bp.isDesktop ? 480 : 420
  const leftPad  = bp.isDesktop ? '40px 32px 40px 48px' : bp.isTablet ? '32px 24px 32px 32px' : '20px 16px 100px'
  const rightPad = bp.isDesktop ? '40px 48px 40px 0' : '32px 32px 32px 0'

  // ── Quote panel (simplified internal view) ─────────────────────────────
  const QuotePanel = () => result ? (
    <div style={{ background: panelBg, borderRadius: 14, padding: 24, marginTop: 16 }}>

      {/* Job summary + delivery estimate in one row */}
      <div style={{ display: 'flex', gap: 12, marginBottom: 16 }}>
        <div style={{ flex: 1, background: 'rgba(255,255,255,0.06)', borderRadius: 10, padding: '12px 14px', fontSize: 12, color: '#aaa0cc', lineHeight: 1.8 }}>
          <strong style={{ color: '#fff' }}>
            {result.totalQty.toLocaleString()}× {size === 'custom' ? `${parsedW}×${parsedH}mm` : (sizeOpts.find(s => s.value === size)?.label || size)}
          </strong>
          {(isFlyer || isPoster) && versions > 1 && <span> ({parsedQty.toLocaleString()} × {versions} versions)</span>}
          {' '}on {isCP ? `${selCP.name} (${selCP.brand}) ${selCP.gsm} gsm` : `${pt?.name} ${weight} gsm`}
          {' '}· {sides === 'double' ? 'Recto & Verso' : 'Recto only'}
          {' '}· {printDesc}
          {effectiveExpress && <span style={{ display: 'inline-block', background: red, color: '#fff', padding: '1px 6px', borderRadius: 4, fontSize: 10, fontWeight: 700, marginLeft: 6 }}>⚡ EXPRESS</span>}
        </div>
        <div style={{ background: 'rgba(255,255,255,0.06)', borderRadius: 10, padding: '12px 14px', display: 'flex', flexDirection: 'column', justifyContent: 'center', minWidth: 160 }}>
          <div style={{ fontSize: 10, color: '#aaa0cc', fontWeight: 600, marginBottom: 2 }}>Estimated ready by</div>
          <div style={{ fontSize: 13, fontWeight: 700, color: '#fff' }}>
            {formatDate(effectiveExpress ? addWorkingDays(2) : isCP ? addWorkingDays(11) : addWorkingDays(5))}
          </div>
          <div style={{ fontSize: 10, color: '#aaa0cc', marginTop: 2 }}>
            {effectiveExpress ? 'Express 48h' : isCP ? '~2 weeks' : '5 working days'}
          </div>
        </div>
      </div>

      {/* Cost breakdown — white background for easy reading */}
      {bd && (
        <div style={{ background: '#fff', borderRadius: 10, padding: '16px 18px' }}>
          <table style={{ width: '100%', borderCollapse: 'collapse', fontFamily: mono, fontSize: 13 }}>
            <thead>
              <tr style={{ borderBottom: '2px solid #e0e0e0' }}>
                {['ITEM', 'COST', 'SELL', 'MARGIN'].map((h, i) => (
                  <th key={h} style={{ textAlign: i === 0 ? 'left' : 'right', color: '#555', fontSize: 10, fontWeight: 700, letterSpacing: '0.08em', padding: '0 0 8px' }}>{h}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {[
                { l: 'Paper',  c: bd.paperCost, s: bd.paperSell },
                { l: 'Clicks', c: bd.clickCost, s: bd.clickSell },
                ...(bd.colorChangeFee > 0 ? [{ l: `Color changes (${bd.colorChanges}×€${SETTINGS.CSF})`, c: bd.colorChangeFee, s: bd.colorChangeFee }] : []),
              ].map((row, i) => {
                const rm = row.c > 0 ? ((row.s - row.c) / row.c * 100) : 0
                return (
                  <tr key={i} style={{ borderBottom: '1px solid #f0f0f0' }}>
                    <td style={{ color: '#333', padding: '6px 0' }}>{row.l}</td>
                    <td style={{ color: '#c0392b', textAlign: 'right', padding: '6px 0' }}>€{row.c.toFixed(2)}</td>
                    <td style={{ color: '#27ae60', textAlign: 'right', padding: '6px 0' }}>€{row.s.toFixed(2)}</td>
                    <td style={{ color: rm > 40 ? '#27ae60' : '#f59e0b', textAlign: 'right', padding: '6px 0', fontWeight: 600 }}>{rm.toFixed(1)}%</td>
                  </tr>
                )
              })}
            </tbody>
            <tfoot>
              {/* Subtotal = material costs only */}
              <tr style={{ borderTop: '2px solid #e0e0e0', background: '#f9f9f9' }}>
                <td style={{ color: '#333', fontWeight: 700, padding: '8px 0' }}>Subtotal materials</td>
                <td style={{ color: '#c0392b', fontWeight: 700, textAlign: 'right', padding: '8px 0' }}>€{(bd.paperCost + bd.clickCost).toFixed(2)}</td>
                <td style={{ color: '#27ae60', fontWeight: 700, textAlign: 'right', padding: '8px 0' }}>€{(bd.paperSell + bd.clickSell).toFixed(2)}</td>
                <td style={{ color: '#555', textAlign: 'right', padding: '8px 0' }}>
                  {(bd.paperCost + bd.clickCost) > 0 ? (((bd.paperSell + bd.clickSell) - (bd.paperCost + bd.clickCost)) / (bd.paperCost + bd.clickCost) * 100).toFixed(1) : 0}%
                </td>
              </tr>
              {/* Overhead rows */}
              {[
                { l: `Machine (incl. ${bd.setupMinutes} min labor)`, c: bd.machineCost,  s: bd.machineSell  },
                { l: 'Prepress',                     c: bd.prepressCost, s: bd.prepressSell },
                ...(bd.expressFee > 0 ? [{ l: `⚡ Express (+${SETTINGS.EXPRESS_MARKUP}%)`, c: 0, s: bd.expressFee }] : []),
              ].map((row, i) => {
                const rm = row.c > 0 ? ((row.s - row.c) / row.c * 100) : 0
                return (
                  <tr key={i} style={{ borderBottom: '1px solid #f0f0f0' }}>
                    <td style={{ color: '#666', padding: '6px 0', fontSize: 12 }}>{row.l}</td>
                    <td style={{ color: '#c0392b', textAlign: 'right', padding: '6px 0', fontSize: 12 }}>€{row.c.toFixed(2)}</td>
                    <td style={{ color: '#27ae60', textAlign: 'right', padding: '6px 0', fontSize: 12 }}>€{row.s.toFixed(2)}</td>
                    <td style={{ color: row.c > 0 ? (rm > 40 ? '#27ae60' : '#f59e0b') : '#999', textAlign: 'right', padding: '6px 0', fontSize: 12 }}>{row.c > 0 ? rm.toFixed(1) + '%' : '—'}</td>
                  </tr>
                )
              })}
              {bd.surcharge > 0 && (
                <tr>
                  <td style={{ color: '#f59e0b', padding: '4px 0', fontSize: 11 }} colSpan={2}>⚠ Min {SETTINGS.MIN_MARGIN}% margin surcharge</td>
                  <td style={{ color: '#f59e0b', textAlign: 'right', padding: '4px 0' }}>+€{bd.surcharge.toFixed(2)}</td>
                  <td />
                </tr>
              )}
              <tr style={{ borderTop: '2px solid #333' }}>
                <td style={{ color: '#000', fontWeight: 800, padding: '8px 0', fontSize: 14 }}>FINAL (ex-VAT)</td>
                <td style={{ color: '#c0392b', fontWeight: 800, textAlign: 'right', padding: '8px 0', fontSize: 14 }}>€{bd.finalCost.toFixed(2)}</td>
                <td style={{ color: '#27ae60', fontWeight: 800, textAlign: 'right', padding: '8px 0', fontSize: 14 }}>€{bd.finalSell.toFixed(2)}</td>
                <td style={{ color: bd.margin >= SETTINGS.MIN_MARGIN ? '#27ae60' : '#f59e0b', fontWeight: 800, textAlign: 'right', padding: '8px 0', fontSize: 14 }}>{bd.margin.toFixed(1)}%</td>
              </tr>
              <tr>
                <td style={{ color: accent, fontWeight: 700, padding: '4px 0', fontSize: 13 }}>FINAL (incl. 21% VAT)</td>
                <td /><td style={{ color: accent, fontWeight: 700, textAlign: 'right', padding: '4px 0', fontSize: 13 }}>€{bd.finalInclVat.toFixed(2)}</td><td />
              </tr>
            </tfoot>          </table>

          {/* Profit + SRA3 + Working time */}
          <div style={{ marginTop: 12, paddingTop: 12, borderTop: '1px solid #e0e0e0', display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 8 }}>
            <div>
              <div style={{ fontSize: 10, color: '#555', marginBottom: 2, textTransform: 'uppercase', letterSpacing: '0.06em' }}>Profit</div>
              <div style={{ fontSize: 20, fontWeight: 800, color: bd.profit > 0 ? '#27ae60' : '#c0392b', fontFamily: mono }}>€{bd.profit.toFixed(2)}</div>
            </div>
            <div style={{ textAlign: 'center' }}>
              <div style={{ fontSize: 10, color: '#555', marginBottom: 2, textTransform: 'uppercase', letterSpacing: '0.06em' }}>SRA3 sheets</div>
              <div style={{ fontSize: 20, fontWeight: 800, color: '#333', fontFamily: mono }}>{result.sra3Used}</div>
            </div>
            <div style={{ textAlign: 'right' }}>
              <div style={{ fontSize: 10, color: '#555', marginBottom: 2, textTransform: 'uppercase', letterSpacing: '0.06em' }}>Working time</div>
              <div style={{ fontSize: 18, fontWeight: 800, color: '#333', fontFamily: mono }}>
                {bd.totalMinutes >= 60
                  ? `${Math.floor(bd.totalMinutes / 60)}h ${bd.totalMinutes % 60}min`
                  : `${bd.totalMinutes} min`}
              </div>
              <div style={{ fontSize: 10, color: '#888', marginTop: 1 }}>
                {bd.printMinutes} min print + {bd.setupMinutes} min setup
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  ) : null

  // ── Options card ─────────────────────────────────────────────────────────
  const OptionsCard = ({ wide }) => (
    <div style={{ background: cardBg, borderRadius: 20, padding: 28, border: `2px solid ${ink}`, boxShadow: shadow, display: 'flex', flexDirection: 'column', gap: 18 }}>

      {/* ROW 1 — Paper / Weight / Size (CMYK wide: all on one line) */}
      {!isCP && wide && (
        <div style={{ display: 'flex', gap: 12 }}>
          <div style={{ flex: 1 }}>
            <div style={ls}>Paper</div>
            <select value={paperType} onChange={e => handlePaper(e.target.value)} style={ss}>
              {Object.entries(allPaperOpts).map(([k, v]) => <option key={k} value={k} disabled={longPaperBlocked(k)}>{v.name}</option>)}
            </select>
          </div>
          <div style={{ width: 124, flexShrink: 0 }}>
            <div style={ls}>Weight</div>
            <select value={weight} onChange={e => setWeight(e.target.value)} style={ss}>
              {(allPaperOpts[paperType]?.weights || []).map(w => <option key={w} value={w}>{w} gsm</option>)}
            </select>
          </div>
          <div style={{ flex: 1 }}>
            <div style={ls}>Size</div>
            <select value={size} onChange={e => setSize(e.target.value)} style={ss}>
              {sizeOpts.map(s => <option key={s.value} value={s.value}>{s.value === 'custom' ? 'Custom' : s.value === 'LP' ? 'Tall 300×600' : s.value.toUpperCase()}</option>)}
            </select>
          </div>
        </div>
      )}

      {!isCP && !wide && (
        <>
          <div style={{ display: 'flex', gap: 12 }}>
            <div style={{ flex: 1 }}>
              <div style={ls}>Paper</div>
              <select value={paperType} onChange={e => handlePaper(e.target.value)} style={ss}>
                {Object.entries(allPaperOpts).map(([k, v]) => <option key={k} value={k} disabled={longPaperBlocked(k)}>{v.name}</option>)}
              </select>
            </div>
            <div style={{ flex: 1 }}>
              <div style={ls}>Weight</div>
              <select value={weight} onChange={e => setWeight(e.target.value)} style={ss}>
                {(allPaperOpts[paperType]?.weights || []).map(w => <option key={w} value={w}>{w} gsm</option>)}
              </select>
            </div>
          </div>
          <div>
            <div style={ls}>Size</div>
            <select value={size} onChange={e => setSize(e.target.value)} style={ss}>
              {sizeOpts.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
            </select>
          </div>
        </>
      )}

      {/* CP wide: PaperPicker full width */}
      {isCP && wide && (
        <PaperPicker papers={liveCreative} selected={creativePaper} onSelect={setCreativePaper} label="Coloured Paper" />
      )}

      {/* CP mobile: PaperPicker + Size stacked */}
      {isCP && !wide && (
        <>
          <PaperPicker papers={liveCreative} selected={creativePaper} onSelect={setCreativePaper} label="Coloured Paper" />
          <div>
            <div style={ls}>Size</div>
            <select value={size} onChange={e => setSize(e.target.value)} style={ss}>
              {sizeOpts.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
            </select>
          </div>
        </>
      )}

      {/* Custom size */}
      {size === 'custom' && (
        <div style={{ display: 'flex', gap: 12 }}>
          <div style={{ flex: 1 }}>
            <div style={ls}>Width (mm)</div>
            <input type="number" value={customW} onChange={e => setCustomW(e.target.value)} style={iStyle} />
          </div>
          <div style={{ flex: 1 }}>
            <div style={ls}>Height (mm)</div>
            <input type="number" value={customH} onChange={e => setCustomH(e.target.value)} style={iStyle} />
          </div>
        </div>
      )}

      {/* ROW 2 — Quantity / Versions / Size (CP) / Sides */}
      {wide ? (
        isCP ? (
          <div style={{ display: 'flex', gap: 12, alignItems: 'flex-end' }}>
            <div style={{ width: 124, flexShrink: 0 }}>
              <div style={ls}>Quantity</div>
              <input type="number" value={quantity} onChange={e => setQuantity(e.target.value)}
                style={{ ...iStyle, padding: '13px 10px', textAlign: 'center' }} min="1" />
            </div>
            <div style={{ width: 80, flexShrink: 0 }}>
              <div style={ls}>Versions</div>
              <select value={versions} onChange={e => setVersions(parseInt(e.target.value))}
                style={{ ...ss, padding: '13px 8px' }}>
                {Array.from({ length: SETTINGS.MAX_VERSIONS }).map((_, i) => <option key={i+1} value={i+1}>{i+1}</option>)}
              </select>
            </div>
            <div style={{ flex: 1 }}>
              <div style={ls}>Size</div>
              <select value={size} onChange={e => setSize(e.target.value)}
                style={{ ...ss, padding: '13px 10px' }}>
                {sizeOpts.map(s => <option key={s.value} value={s.value}>{s.value === 'custom' ? 'Custom' : s.value === 'LP' ? 'Tall 300×600' : s.value.toUpperCase()}</option>)}
              </select>
            </div>
          </div>
        ) : (
          <div style={{ display: 'flex', gap: 12, alignItems: 'flex-end' }}>
            <div style={{ flex: 2 }}>
              <div style={ls}>Quantity</div>
              <input type="number" value={quantity} onChange={e => setQuantity(e.target.value)} style={iStyle} min="1" />
            </div>
            {(isFlyer || isPoster) && (
              <div style={{ flex: 1 }}>
                <div style={ls}>Versions</div>
                <select value={versions} onChange={e => setVersions(parseInt(e.target.value))} style={ss}>
                  {Array.from({ length: SETTINGS.MAX_VERSIONS }).map((_, i) => <option key={i+1} value={i+1}>{i+1}</option>)}
                </select>
              </div>
            )}
            <div style={{ flex: 2 }}>
              <div style={ls}>Print sides</div>
              <div style={{ display: 'flex', gap: 8 }}>
                <button onClick={() => setSides('single')} style={tb(sides === 'single')}>Recto only</button>
                <button onClick={() => setSides('double')} style={tb(sides === 'double')}>Recto & Verso</button>
              </div>
            </div>
          </div>
        )
      ) : (
        <>
          <div style={{ display: 'flex', gap: 12 }}>
            <div style={{ flex: 2 }}>
              <div style={ls}>Quantity</div>
              <input type="number" value={quantity} onChange={e => setQuantity(e.target.value)} style={iStyle} min="1" />
            </div>
            {(isFlyer || isPoster) && (
              <div style={{ flex: 1 }}>
                <div style={ls}>Versions</div>
                <select value={versions} onChange={e => setVersions(parseInt(e.target.value))} style={ss}>
                  {Array.from({ length: SETTINGS.MAX_VERSIONS }).map((_, i) => <option key={i+1} value={i+1}>{i+1}</option>)}
                </select>
              </div>
            )}
          </div>
          {!isCP && (
            <div>
              <div style={ls}>Print sides</div>
              <div style={{ display: 'flex', gap: 8 }}>
                <button onClick={() => setSides('single')} style={tb(sides === 'single')}>Recto only</button>
                <button onClick={() => setSides('double')} style={tb(sides === 'double')}>Recto & Verso</button>
              </div>
            </div>
          )}
        </>
      )}

      {/* Different paper per version */}
      {isCP && isFlyer && versions > 1 && (
        <label style={{ display: 'flex', alignItems: 'center', gap: 10, cursor: 'pointer' }}>
          <input type="checkbox" checked={diffColors} onChange={e => setDiffColors(e.target.checked)} style={{ width: 18, height: 18, accentColor: accent }} />
          <span style={{ fontSize: 13, fontWeight: 600, color: dark }}>Different paper per version</span>
        </label>
      )}

      {/* Ink (CP only) */}
      {isCP && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
          <div>
            <div style={ls}>Print sides</div>
            <div style={{ display: 'flex', gap: 8 }}>
              <button onClick={() => setSides('single')} style={tb(sides === 'single')}>Recto only</button>
              <button onClick={() => setSides('double')} style={tb(sides === 'double')}>Recto & Verso</button>
            </div>
          </div>
          {wide ? (
            <div style={{ display: 'flex', gap: 12 }}>
              <div style={{ flex: 1 }}>
                <div style={ls}>Recto</div>
                <div style={{ display: 'flex', gap: 6 }}>
                  {INK_OPTIONS.map(ink => {
                    const on = frontInk === ink
                    return <button key={ink} onClick={() => setFrontInk(ink)} style={{ flex: 1, padding: '11px 10px', border: `2px solid ${ink}`, borderRadius: 12, background: on ? accent : cardBg, cursor: 'pointer', fontSize: 12, fontWeight: 700, fontFamily: 'inherit', color: on ? cream : ink, boxShadow: on ? shadowSm : 'none', transition: 'all 0.1s' }}>{inkLabel(ink)}</button>
                  })}
                </div>
              </div>
              {sides === 'double' && (
                <div style={{ flex: 1 }}>
                  <div style={ls}>Verso</div>
                  <div style={{ display: 'flex', gap: 6 }}>
                    {INK_OPTIONS.map(ink => {
                      const on = backInk === ink
                      return <button key={ink} onClick={() => setBackInk(ink)} style={{ flex: 1, padding: '11px 10px', border: `2px solid ${ink}`, borderRadius: 12, background: on ? accent : cardBg, cursor: 'pointer', fontSize: 12, fontWeight: 700, fontFamily: 'inherit', color: on ? cream : ink, boxShadow: on ? shadowSm : 'none', transition: 'all 0.1s' }}>{inkLabel(ink)}</button>
                    })}
                  </div>
                </div>
              )}
            </div>
          ) : (
            <div>
              <div style={ls}>Recto</div>
              <div style={{ display: 'flex', gap: 6 }}>
                {INK_OPTIONS.map(ink => {
                  const on = frontInk === ink
                  return <button key={ink} onClick={() => setFrontInk(ink)} style={{ flex: 1, padding: '11px 10px', border: `2px solid ${ink}`, borderRadius: 12, background: on ? accent : cardBg, cursor: 'pointer', fontSize: 12, fontWeight: 700, fontFamily: 'inherit', color: on ? cream : ink, boxShadow: on ? shadowSm : 'none', transition: 'all 0.1s' }}>{inkLabel(ink)}</button>
                })}
              </div>
              {sides === 'double' && (
                <div style={{ marginTop: 12 }}>
                  <div style={ls}>Verso</div>
                  <div style={{ display: 'flex', gap: 6 }}>
                    {INK_OPTIONS.map(ink => {
                      const on = backInk === ink
                      return <button key={ink} onClick={() => setBackInk(ink)} style={{ flex: 1, padding: '11px 10px', border: `2px solid ${ink}`, borderRadius: 12, background: on ? accent : cardBg, cursor: 'pointer', fontSize: 12, fontWeight: 700, fontFamily: 'inherit', color: on ? cream : ink, boxShadow: on ? shadowSm : 'none', transition: 'all 0.1s' }}>{inkLabel(ink)}</button>
                    })}
                  </div>
                </div>
              )}
            </div>
          )}
        </div>
      )}

      {/* Express */}
      {canExpress && (
        <label style={{ display: 'flex', alignItems: 'center', gap: 12, cursor: 'pointer', padding: '14px 16px', borderRadius: 12, border: `2px dashed ${ink}`, background: effectiveExpress ? cardHi : cardBg, transition: 'all 0.15s' }}>
          <input type="checkbox" checked={express} onChange={e => setExpress(e.target.checked)} style={{ width: 18, height: 18, accentColor: accent }} />
          <div>
            <div style={{ fontSize: 14, fontWeight: 700, color: effectiveExpress ? red : ink }}>⚡ Express Service</div>
            <div style={{ fontSize: 12, color: mid, marginTop: 2, fontWeight: 500 }}>Ready within 48 hours (+{SETTINGS.EXPRESS_MARKUP}%)</div>
          </div>
        </label>
      )}

      {/* Per-version paper pickers */}
      {isCP && diffColors && versions > 1 && (
        <div>
          {Array.from({ length: versions }).map((_, i) => (
            <PaperPicker key={i} papers={liveCreative} selected={versionPaperIds[i]}
              onSelect={id => { const nc = [...versionPaperIds]; nc[i] = id; setVersionPaperIds(nc) }}
              label={`Version ${i + 1} paper`}
            />
          ))}
        </div>
      )}
    </div>
  )

  // ── Render ───────────────────────────────────────────────────────────────
  return (
    <div style={{ background: bgPage, minHeight: '100vh' }}>
      <div style={{ maxWidth: 900, margin: '0 auto', padding: bp.isDesktop ? '40px 48px' : bp.isTablet ? '32px 32px' : '20px 16px' }}>

        {/* Header */}
        <div style={{ marginBottom: 20 }}>
          <div style={{ fontSize: 26, fontWeight: 800, letterSpacing: '-0.03em', color: dark }}>
            Internal <span style={{ color: accent }}>Calculator</span>
          </div>
          <div style={{ fontSize: 12, color: mid, marginTop: 4 }}>Click Click Graphics · Team Only</div>
        </div>

        {/* Mode tabs */}
        <div style={{ display: 'flex', gap: 8, marginBottom: 18, flexWrap: 'wrap' }}>
          {[{ value: 'flyer_cmyk', label: 'Flyer / Card' }, { value: 'flyer_cp', label: 'Coloured Papers' }, { value: 'poster', label: 'Mini Poster' }, { value: 'dashboard', label: 'Dashboard' }].map(({ value, label }) => (
            <button key={value} onClick={() => value === 'dashboard' ? setMode('dashboard') : handleMode(value)} style={mb(mode === value)}>{label}</button>
          ))}
        </div>

        {mode === 'dashboard' ? (
          <Dashboard />
        ) : (
        <>
        {/* Options card */}
        {OptionsCard({ wide: true })}

        {/* Quote panel — below the options */}
        {QuotePanel()}

        </>
        )}

        {/* Footer */}
        <div style={{ padding: '24px 0 16px', fontSize: 11, color: '#9B8F89' }}>
          Click Click Graphics · Aalststraat 7-11, 1000 Brussels · All prices excl. VAT unless noted<br />
          <span style={{ fontSize: 9, color: '#C5B8B1' }}>Data: {dataSource}</span>
        </div>
      </div>
    </div>
  )
}
