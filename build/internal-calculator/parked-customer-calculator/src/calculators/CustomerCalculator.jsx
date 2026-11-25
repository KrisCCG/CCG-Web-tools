import React, { useState, useMemo, useEffect } from 'react'
import PaperPicker from '../components/PaperPicker.jsx'
import LanguageToggle from '../components/LanguageToggle.jsx'
import { useTranslation } from '../utils/i18n.jsx'
import { calcPrice } from '../utils/pricing.js'
import { loadExcelData } from '../utils/excelLoader.js'
import { addWorkingDays, formatDate } from '../utils/dateHelpers.js'
import {
  BRAND, FLYER_SIZES, POSTER_SIZES, SETTINGS,
  DEFAULT_PAPER_DATA, DEFAULT_CREATIVE_PAPERS, PRINTABLE_W, PRINTABLE_H,} from '../utils/constants.js'

const PAY_URL = 'https://clickclick.be/calculator/pay.php'

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

export default function CustomerCalculator() {
  const bp = useBreakpoint()
  const { t, lang } = useTranslation()
  const [step,            setStep]            = useState(1) // 1 = calculator, 2 = order
  const [mobileQuoteOpen, setMobileQuoteOpen] = useState(false)

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
  const [isCompany,       setIsCompany]       = useState(false)
  const [vatNumber,       setVatNumber]       = useState('')
  const [frontInk,        setFrontInk]        = useState('white')
  const [backInk,         setBackInk]         = useState('black')
  const [delivery,        setDelivery]        = useState(false)

  // ── Customer info ────────────────────────────────────────────────────────
  const [custName,    setCustName]    = useState('')
  const [custEmail,   setCustEmail]   = useState('')
  const [custPhone,   setCustPhone]   = useState('')
  const [custCompany, setCustCompany] = useState('')
  const [custAddress, setCustAddress] = useState('')
  const [custZip,     setCustZip]     = useState('')
  const [custCity,    setCustCity]    = useState('')
  const [diffShip,    setDiffShip]    = useState(false)
  const [shipAddress, setShipAddress] = useState('')
  const [shipZip,     setShipZip]     = useState('')
  const [shipCity,    setShipCity]    = useState('')

  // ── Excel data ───────────────────────────────────────────────────────────
  const [livePaperData,  setLivePaperData]  = useState(DEFAULT_PAPER_DATA)
  const [liveCreative,   setLiveCreative]   = useState(DEFAULT_CREATIVE_PAPERS)
  const [livePaperTypes, setLivePaperTypes] = useState(null)
  const [dataSource,     setDataSource]     = useState('defaults')

  useEffect(() => {
    loadExcelData()
      .then(d => {
        if (d.paperData)  setLivePaperData(d.paperData)
        if (d.creative)   setLiveCreative(d.creative)
        if (d.paperTypes) setLivePaperTypes(d.paperTypes)
        setDataSource('excel')
        console.log('✅ Prices loaded from Excel')
      })
      .catch(e => {
        console.warn('⚠ Could not load Excel — using defaults:', e.message)
        setDataSource('defaults (file not found)')
      })
  }, [])

  useEffect(() => {
    if (bp.isWide) setMobileQuoteOpen(false)
  }, [bp.isWide])

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
  const allPaperOpts  = Object.fromEntries(
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
  const inkLabel  = ink => t('ink.' + ink)
  const printDesc = isCP
    ? (sides === 'double' ? `${t('label.recto')}: ${inkLabel(frontInk)} · ${t('label.verso')}: ${inkLabel(backInk)}` : inkLabel(frontInk))
    : t('print.cmyk')

  // ── Mode handler ─────────────────────────────────────────────────────────
  const handleMode = m => {
    setMode(m)
    if (m === 'flyer_cp')   { setExpress(false); setSize('A6') }
    if (m === 'flyer_cmyk') { setPaperType('multiart'); setWeight('130'); setSize('A6') }
    if (m === 'poster')     { setPaperType('multiart'); setWeight('130'); setSize('A4'); setDiffColors(false) }
  }

  const handlePaper = val => {
    setPaperType(val)
    const ws = allPaperOpts[val]?.weights || ['130']
    if (!ws.includes(weight)) setWeight(ws[0])
  }

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
    creativePaperId:  isCP ? creativePaper : null,
    versionPaperIds:  isCP && diffColors ? versionPaperIds.slice(0, versions) : null,
    paperData:        livePaperData,
    creativePapers:   liveCreative,
  }), [
    mode, paperType, weight, size, parsedQty, sides, versions, diffColors,
    versionPaperIds, parsedW, parsedH, effectiveExpress, frontInk, backInk,
    creativePaper, livePaperData, liveCreative, dataSource,
  ])

  const totalWithDelivery   = result ? result.totalInclVat + (delivery ? 15    : 0) : 0
  const totalExWithDelivery = result ? result.totalExVat   + (delivery ? 12.40 : 0) : 0

  // ── Anonymous browse logging (debounced, fire-and-forget) ──────────
  useEffect(() => {
    if (!result) return
    const timer = setTimeout(() => {
      const tabLabel = isPoster ? 'Mini Poster' : isCP ? 'Coloured Papers' : 'Flyer / Card'
      fetch('https://clickclick.be/calculator/log-calc.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          tab: tabLabel, paper: isCP ? creativePaper : paperType,
          weight: isCP ? selCP?.gsm : parseInt(weight), size,
          quantity: parsedQty, sides, versions: effectiveVersions,
          express: effectiveExpress, price: result.totalInclVat,
          priceExVat: result.totalExVat,
        }),
      }).catch(() => {}) // silent fail
    }, 3000) // 3s debounce
    return () => clearTimeout(timer)
  }, [mode, paperType, weight, size, parsedQty, sides, versions, effectiveExpress, creativePaper])

  // ── Payment ──────────────────────────────────────────────────────────────
  function buildSpecs() {
    const formatLabel = sizeOpts.find(s => s.value === size)?.label || size
    const paperLabel  = isCP ? `${selCP.name} ${selCP.gsm} gsm` : `${pt?.name} ${weight} gsm`
    return [
      isPoster ? 'Mini Poster' : 'Flyer / Card',
      formatLabel, paperLabel, printDesc,
      sides === 'double' ? 'Recto & Verso' : 'Recto only',
      `${result.totalQty} copies`,
      effectiveExpress ? 'Express 48h' : '',
      isCompany && vatNumber ? `VAT: ${vatNumber}` : '',
      delivery ? 'Delivery €15,00 incl. VAT' : 'Pick-up at shop',
    ].filter(Boolean).join(' · ')
  }

  const handleBancontact = () => {
    if (!custName || !custEmail || !custAddress || !custCity) {
      alert(t('alert.fillRequired'))
      return
    }
    if (delivery && diffShip && (!shipAddress || !shipCity)) {
      alert(t('alert.fillShipping'))
      return
    }
    const shipTo = delivery && diffShip
      ? { ship_address: shipAddress, ship_zip: shipZip, ship_city: shipCity }
      : {}
    const params = new URLSearchParams({
      simpay_amount:    Math.round(totalWithDelivery * 100),
      specs:            buildSpecs(),
      payment:          'bancontact',
      customer_name:    custName,
      customer_email:   custEmail,
      customer_phone:   custPhone,
      customer_company: custCompany,
      customer_address: custAddress,
      customer_zip:     custZip,
      customer_city:    custCity,
      delivery:         delivery ? 'yes' : 'no',
      receipt_email:    'shop@clickclickgraphics.com',
      doc_type:         isCompany ? 'invoice' : 'receipt',
      ...(isCompany && vatNumber ? { vat: vatNumber } : {}),
      ...shipTo,
    })
    window.top.location.href = `${PAY_URL}?${params.toString()}`
  }

  // ── Style tokens (Tiegel palette) ────────────────────────────────────────
  const accent    = '#4200FF'
  const red       = '#FF3B30'
  const dark      = '#000000'
  const mid       = '#555555'
  const borderClr = '#D8CFC9'
  const bgPage    = '#FFF5EF'
  const panelBg   = '#1a0066'
  const mono      = "'Courier New',monospace"

  const ls        = { fontSize: 10, fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.12em', color: mid, marginBottom: 6 }
  const ss        = { width: '100%', padding: '10px 14px', borderRadius: 8, border: `1.5px solid ${borderClr}`, fontSize: 14, fontWeight: 500, background: '#fff', color: dark, outline: 'none', cursor: 'pointer', appearance: 'none', backgroundImage: "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 5 5-5' stroke='%23999' stroke-width='1.5' fill='none'/%3E%3C/svg%3E\")", backgroundRepeat: 'no-repeat', backgroundPosition: 'right 12px center' }
  const iStyle    = { width: '100%', padding: '10px 14px', borderRadius: 8, border: `1.5px solid ${borderClr}`, fontSize: 14, fontWeight: 500, background: '#fff', color: dark, outline: 'none' }
  const darkInput = { width: '100%', padding: '10px 14px', borderRadius: 8, border: '1.5px solid rgba(255,255,255,0.15)', fontSize: 13, background: 'rgba(255,255,255,0.06)', color: '#fff', outline: 'none', boxSizing: 'border-box' }
  const darkLs    = { fontSize: 10, fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.12em', color: '#aaa0cc', marginBottom: 6 }
  const tb        = on => ({ flex: 1, padding: '10px 14px', border: 'none', cursor: 'pointer', fontSize: 13, fontWeight: 600, background: on ? accent : '#fff', color: on ? '#FFF5EF' : mid, transition: 'all 0.15s' })
  const mb        = on => ({ flex: 1, padding: '14px 16px', border: 'none', cursor: 'pointer', fontSize: 14, fontWeight: 700, letterSpacing: '-0.01em', background: on ? accent : 'transparent', color: on ? '#FFF5EF' : dark, borderRadius: 10, transition: 'all 0.2s' })

  const rightW   = bp.isDesktop ? 460 : 400
  const leftPad  = bp.isDesktop ? '40px 32px 40px 48px' : bp.isTablet ? '32px 24px 32px 32px' : '20px 16px 100px'
  const rightPad = bp.isDesktop ? '40px 48px 40px 0' : '32px 32px 32px 0'

  // ── Step 1: Quote panel (price only + continue button) ─────────────────
  const quotePanel = result ? (
    <div style={{ background: panelBg, borderRadius: bp.isMobile ? '20px 20px 0 0' : 14, padding: bp.isMobile ? '24px 20px 32px' : 28, boxShadow: '0 4px 20px rgba(0,0,0,0.2)' }}>
      {bp.isMobile && <div style={{ width: 36, height: 4, background: 'rgba(255,255,255,0.2)', borderRadius: 2, margin: '0 auto 20px' }} />}

      <div style={{ fontSize: 20, fontWeight: 800, letterSpacing: '-0.02em', color: '#fff', marginBottom: 20 }}>{t('quote.title')}</div>

      {/* Summary rows */}
      <div style={{ background: 'rgba(255,255,255,0.04)', borderRadius: 10, padding: '14px 16px', marginBottom: 20 }}>
        {[
          { label: t('quote.product'),  value: isPoster ? t('product.poster') : t('product.flyer') },
          { label: t('quote.format'),   value: size === 'custom' ? `${parsedW}×${parsedH} mm` : (sizeOpts.find(s => s.value === size)?.label || size) },
          ...(isCP
            ? [{ label: t('quote.paper'), value: selCP.name }, { label: t('quote.brand'), value: selCP.brand }, { label: t('quote.weight'), value: `${selCP.gsm} gsm` }]
            : [{ label: t('quote.paper'), value: pt?.name }, { label: t('quote.weight'), value: `${weight} gsm` }]),
          { label: t('quote.print'),    value: printDesc },
          { label: t('quote.sides'),    value: sides === 'double' ? t('sides.double') : t('sides.single') },
          ...((isFlyer || isPoster) && versions > 1 ? [{ label: t('quote.versions'), value: String(versions) }] : []),
          { label: t('quote.quantity'), value: result.totalQty.toLocaleString() + ((isFlyer || isPoster) && versions > 1 ? ` (${parsedQty.toLocaleString()} × ${versions})` : '') },
          ...(effectiveExpress ? [{ label: t('quote.service'), value: `⚡ ${t('express.quote')}` }] : []),
          { label: t('quote.delivery'), value: delivery ? t('delivery.ship.quote') : t('delivery.pickup.quote') },
        ].map((row, i, arr) => (
          <div key={i} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '6px 0', borderBottom: i < arr.length - 1 ? '1px solid rgba(255,255,255,0.06)' : 'none' }}>
            <span style={{ fontSize: 11, color: '#aaa0cc', fontWeight: 600 }}>{row.label}</span>
            <span style={{ fontSize: 11, color: row.label === t('quote.service') ? red : '#fff', fontWeight: 600, textAlign: 'right', maxWidth: '60%' }}>{row.value}</span>
          </div>
        ))}
      </div>

      {/* Ready by */}
      <div style={{ display: 'flex', alignItems: 'center', gap: 8, padding: '10px 12px', background: 'rgba(255,255,255,0.04)', borderRadius: 8, marginBottom: 20 }}>
        <span style={{ fontSize: 14 }}>📅</span>
        <div>
          <div style={{ fontSize: 10, color: '#aaa0cc', fontWeight: 600 }}>{t('quote.readyBy')}</div>
          <div style={{ fontSize: 13, fontWeight: 700, color: '#fff' }}>
            {formatDate(effectiveExpress ? addWorkingDays(2) : isCP ? addWorkingDays(11) : addWorkingDays(5), lang)}
          </div>
        </div>
        <span style={{ fontSize: 10, color: '#aaa0cc', marginLeft: 'auto', textAlign: 'right' }}>
          {effectiveExpress ? t('quote.timeExpress') : isCP ? t('quote.timeCP') : t('quote.timeStandard')}
        </span>
      </div>

      {/* Price */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginBottom: 8 }}>
        <div>
          <div style={{ fontSize: 11, color: '#aaa0cc', marginBottom: 4 }}>{t('quote.totalInclVat')}</div>
          <div style={{ fontSize: bp.isDesktop ? 42 : 36, fontWeight: 800, color: '#fff', fontFamily: mono, letterSpacing: '-0.03em', lineHeight: 1 }}>
            €{totalWithDelivery.toFixed(2)}
          </div>
        </div>
        <div style={{ textAlign: 'right' }}>
          <div style={{ fontSize: 11, color: '#aaa0cc', marginBottom: 4 }}>{t('quote.perPiece')}</div>
          <div style={{ fontSize: 18, fontWeight: 700, color: '#fff', fontFamily: mono }}>€{result.perPieceInclVat.toFixed(4)}</div>
        </div>
      </div>
      <div style={{ fontSize: 11, color: '#aaa0cc', marginBottom: 20 }}>
        {t('quote.exclVat')}: €{totalExWithDelivery.toFixed(2)}{delivery ? ` ${t('quote.inclDelivery')}` : ''} · {t('quote.perPieceShort')}: €{result.perPieceExVat.toFixed(4)}
      </div>

      {/* Continue to order button */}
      <button
        onClick={() => { setStep(2); window.scrollTo(0, 0) }}
        style={{ width: '100%', padding: 16, borderRadius: 12, border: 'none', background: 'linear-gradient(135deg,#4200FF 0%,#6633FF 100%)', color: '#fff', fontSize: 15, fontWeight: 800, cursor: 'pointer', letterSpacing: '-0.01em', boxShadow: '0 4px 20px rgba(66,0,255,0.35)', transition: 'all 0.2s' }}
        onMouseOver={e => { e.currentTarget.style.transform = 'translateY(-2px)'; e.currentTarget.style.boxShadow = '0 8px 28px rgba(66,0,255,0.5)' }}
        onMouseOut={e  => { e.currentTarget.style.transform = 'none';             e.currentTarget.style.boxShadow = '0 4px 20px rgba(66,0,255,0.35)' }}
      >
        {t('btn.continue')}
      </button>
      <div style={{ textAlign: 'center', marginTop: 8, fontSize: 11, color: '#aaa0cc' }}>
        🔒 {t('secure.checkout')}
      </div>
      <div style={{ textAlign: 'center', marginTop: 14, fontSize: 11, color: '#aaa0cc', lineHeight: 1.6, borderTop: '1px solid rgba(255,255,255,0.08)', paddingTop: 14 }}>
        {t('contact.notFound')}<br />
        <a href="mailto:info@clickclickgraphics.com" style={{ color: '#fff', textDecoration: 'none', fontWeight: 600 }}>info@clickclickgraphics.com</a>
        {' '}{t('contact.or')}{' '}
        <a href="tel:+3228807036" style={{ color: '#fff', textDecoration: 'none', fontWeight: 600 }}>+32 2 880 70 36</a>
      </div>
    </div>
  ) : null
  const OptionsCard = ({ wide }) => (
    <div style={{ background: '#fff', borderRadius: 14, padding: 20, border: `1.5px solid ${borderClr}`, display: 'flex', flexDirection: 'column', gap: 16 }}>

      {/* ROW 1 — Paper / Weight / Size */}
      {!isCP && wide && (
        <div style={{ display: 'flex', gap: 12 }}>
          <div style={{ flex: 1 }}>
            <div style={ls}>{t('label.paper')}</div>
            <select value={paperType} onChange={e => handlePaper(e.target.value)} style={ss}>
              {Object.entries(allPaperOpts).map(([k, v]) => <option key={k} value={k}>{v.name}</option>)}
            </select>
          </div>
          <div style={{ width: 100, flexShrink: 0 }}>
            <div style={ls}>{t('label.weight')}</div>
            <select value={weight} onChange={e => setWeight(e.target.value)} style={ss}>
              {(allPaperOpts[paperType]?.weights || []).map(w => <option key={w} value={w}>{w} gsm</option>)}
            </select>
          </div>
          <div style={{ flex: 1 }}>
            <div style={ls}>{t('label.size')}</div>
            <select value={size} onChange={e => setSize(e.target.value)} style={ss}>
              {sizeOpts.map(s => <option key={s.value} value={s.value}>{s.value === 'custom' ? 'Custom' : s.value.toUpperCase()}</option>)}
            </select>
          </div>
        </div>
      )}

      {!isCP && !wide && (
        <>
          <div style={{ display: 'flex', gap: 12 }}>
            <div style={{ flex: 1 }}>
              <div style={ls}>{t('label.paper')}</div>
              <select value={paperType} onChange={e => handlePaper(e.target.value)} style={ss}>
                {Object.entries(allPaperOpts).map(([k, v]) => <option key={k} value={k}>{v.name}</option>)}
              </select>
            </div>
            <div style={{ flex: 1 }}>
              <div style={ls}>{t('label.weight')}</div>
              <select value={weight} onChange={e => setWeight(e.target.value)} style={ss}>
                {(allPaperOpts[paperType]?.weights || []).map(w => <option key={w} value={w}>{w} gsm</option>)}
              </select>
            </div>
          </div>
          <div>
            <div style={ls}>{t('label.size')}</div>
            <select value={size} onChange={e => setSize(e.target.value)} style={ss}>
              {sizeOpts.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
            </select>
          </div>
        </>
      )}

      {/* Coloured Papers (wide: full width, size moves to row 2) */}
      {isCP && wide && (
        <PaperPicker papers={liveCreative} selected={creativePaper} onSelect={setCreativePaper} label="Coloured Paper" />
      )}

      {isCP && !wide && (
        <>
          <PaperPicker papers={liveCreative} selected={creativePaper} onSelect={setCreativePaper} label="Coloured Paper" />
          <div>
            <div style={ls}>{t('label.size')}</div>
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
            <div style={ls}>{t('label.widthMm')}</div>
            <input type="number" value={customW} onChange={e => setCustomW(e.target.value)} style={iStyle} min="10" max="300" />
          </div>
          <div style={{ flex: 1 }}>
            <div style={ls}>{t('label.heightMm')}</div>
            <input type="number" value={customH} onChange={e => setCustomH(e.target.value)} style={iStyle} min="10" max="430" />
          </div>
        </div>
      )}

      {/* ROW 2 — Quantity / Versions / Sides */}
      {wide ? (
        isCP ? (
          /* CP wide: Quantity + Versions + Size on one line */
          <div style={{ display: 'flex', gap: 12, alignItems: 'flex-end' }}>
            <div style={{ width: 100, flexShrink: 0 }}>
              <div style={ls}>{t('label.quantity')}</div>
              <input type="number" value={quantity} onChange={e => setQuantity(e.target.value)}
                style={{ ...iStyle, padding: '13px 10px', textAlign: 'center' }} min="1" />
            </div>
            <div style={{ width: 80, flexShrink: 0 }}>
              <div style={ls}>{t('label.versions')}</div>
              <select value={versions} onChange={e => setVersions(parseInt(e.target.value))}
                style={{ ...ss, padding: '13px 8px' }}>
                {Array.from({ length: SETTINGS.MAX_VERSIONS }).map((_, i) => <option key={i+1} value={i+1}>{i+1}</option>)}
              </select>
            </div>
            <div style={{ flex: 1 }}>
              <div style={ls}>{t('label.size')}</div>
              <select value={size} onChange={e => setSize(e.target.value)}
                style={{ ...ss, padding: '13px 10px' }}>
                {sizeOpts.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
              </select>
            </div>
          </div>
        ) : (
          /* CMYK wide: Quantity + Versions + Sides all on one line */
          <div style={{ display: 'flex', gap: 12, alignItems: 'flex-end' }}>
            <div style={{ flex: 2 }}>
              <div style={ls}>{t('label.quantity')}</div>
              <input type="number" value={quantity} onChange={e => setQuantity(e.target.value)} style={iStyle} min="1" />
            </div>
            {(isFlyer || isPoster) && (
              <div style={{ flex: 1 }}>
                <div style={ls}>{t('label.versions')}</div>
                <select value={versions} onChange={e => setVersions(parseInt(e.target.value))} style={ss}>
                  {Array.from({ length: SETTINGS.MAX_VERSIONS }).map((_, i) => <option key={i+1} value={i+1}>{i+1}</option>)}
                </select>
              </div>
            )}
            <div style={{ flex: 2 }}>
              <div style={ls}>{t('label.printSides')}</div>
              <div style={{ display: 'flex', borderRadius: 8, border: `1.5px solid ${borderClr}`, overflow: 'hidden' }}>
                <button onClick={() => setSides('single')} style={tb(sides === 'single')}>{t('sides.single')}</button>
                <button onClick={() => setSides('double')} style={tb(sides === 'double')}>{t('sides.double')}</button>
              </div>
            </div>
          </div>
        )
      ) : (
        <>
          <div style={{ display: 'flex', gap: 12 }}>
            <div style={{ flex: 2 }}>
              <div style={ls}>{t('label.quantity')}</div>
              <input type="number" value={quantity} onChange={e => setQuantity(e.target.value)} style={iStyle} min="1" />
            </div>
            {(isFlyer || isPoster) && (
              <div style={{ flex: 1 }}>
                <div style={ls}>{t('label.versions')}</div>
                <select value={versions} onChange={e => setVersions(parseInt(e.target.value))} style={ss}>
                  {Array.from({ length: SETTINGS.MAX_VERSIONS }).map((_, i) => <option key={i+1} value={i+1}>{i+1}</option>)}
                </select>
              </div>
            )}
          </div>
          {!isCP && (
            <div>
              <div style={ls}>{t('label.printSides')}</div>
              <div style={{ display: 'flex', borderRadius: 8, border: `1.5px solid ${borderClr}`, overflow: 'hidden' }}>
                <button onClick={() => setSides('single')} style={tb(sides === 'single')}>{t('sides.single')}</button>
                <button onClick={() => setSides('double')} style={tb(sides === 'double')}>{t('sides.double')}</button>
              </div>
            </div>
          )}
        </>
      )}

            {/* Different paper per version */}
      {isCP && isFlyer && versions > 1 && (
        <label style={{ display: 'flex', alignItems: 'center', gap: 10, cursor: 'pointer' }}>
          <input type="checkbox" checked={diffColors} onChange={e => setDiffColors(e.target.checked)} style={{ width: 18, height: 18, accentColor: accent }} />
          <span style={{ fontSize: 13, fontWeight: 600, color: dark }}>{t('diffPaper')}</span>
        </label>
      )}

      {/* Ink (Coloured Papers only) */}
      {isCP && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>

          {/* Recto only / Recto & Verso toggle — always visible for CP */}
          <div>
            <div style={ls}>{t('label.printSides')}</div>
            <div style={{ display: 'flex', borderRadius: 8, border: `1.5px solid ${borderClr}`, overflow: 'hidden' }}>
              <button onClick={() => setSides('single')} style={tb(sides === 'single')}>{t('sides.single')}</button>
              <button onClick={() => setSides('double')} style={tb(sides === 'double')}>{t('sides.double')}</button>
            </div>
          </div>

          {/* Ink buttons — wide: Recto + Verso side by side; mobile: stacked */}
          {wide ? (
            <div style={{ display: 'flex', gap: 12 }}>
              <div style={{ flex: 1 }}>
                <div style={ls}>{t('label.recto')}</div>
                <div style={{ display: 'flex', gap: 6 }}>
                  {INK_OPTIONS.map(ink => {
                    const on = frontInk === ink
                    return <button key={ink} onClick={() => setFrontInk(ink)} style={{ flex: 1, padding: '10px 10px', border: on ? `2px solid ${accent}` : `1.5px solid ${borderClr}`, borderRadius: 8, background: on ? '#EDE8FF' : '#fff', cursor: 'pointer', fontSize: 12, fontWeight: 700, color: on ? accent : mid, transition: 'all 0.15s' }}>{inkLabel(ink)}</button>
                  })}
                </div>
              </div>
              {sides === 'double' && (
                <div style={{ flex: 1 }}>
                  <div style={ls}>{t('label.verso')}</div>
                  <div style={{ display: 'flex', gap: 6 }}>
                    {INK_OPTIONS.map(ink => {
                      const on = backInk === ink
                      return <button key={ink} onClick={() => setBackInk(ink)} style={{ flex: 1, padding: '10px 10px', border: on ? `2px solid ${accent}` : `1.5px solid ${borderClr}`, borderRadius: 8, background: on ? '#EDE8FF' : '#fff', cursor: 'pointer', fontSize: 12, fontWeight: 700, color: on ? accent : mid, transition: 'all 0.15s' }}>{inkLabel(ink)}</button>
                    })}
                  </div>
                </div>
              )}
            </div>
          ) : (
            <div>
              <div style={ls}>{t('label.recto')}</div>
              <div style={{ display: 'flex', gap: 6 }}>
                {INK_OPTIONS.map(ink => {
                  const on = frontInk === ink
                  return <button key={ink} onClick={() => setFrontInk(ink)} style={{ flex: 1, padding: '10px 10px', border: on ? `2px solid ${accent}` : `1.5px solid ${borderClr}`, borderRadius: 8, background: on ? '#EDE8FF' : '#fff', cursor: 'pointer', fontSize: 12, fontWeight: 700, color: on ? accent : mid, transition: 'all 0.15s' }}>{inkLabel(ink)}</button>
                })}
              </div>
              {sides === 'double' && (
                <div style={{ marginTop: 12 }}>
                  <div style={ls}>{t('label.verso')}</div>
                  <div style={{ display: 'flex', gap: 6 }}>
                    {INK_OPTIONS.map(ink => {
                      const on = backInk === ink
                      return <button key={ink} onClick={() => setBackInk(ink)} style={{ flex: 1, padding: '10px 10px', border: on ? `2px solid ${accent}` : `1.5px solid ${borderClr}`, borderRadius: 8, background: on ? '#EDE8FF' : '#fff', cursor: 'pointer', fontSize: 12, fontWeight: 700, color: on ? accent : mid, transition: 'all 0.15s' }}>{inkLabel(ink)}</button>
                    })}
                  </div>
                </div>
              )}
            </div>
          )}

          <div style={{ fontSize: 11, color: mid, fontStyle: 'italic' }}>
            {t('otherOptions')}{' '}
            <a href="mailto:info@clickclickgraphics.com" style={{ color: accent, textDecoration: 'none', fontWeight: 600 }}>{t('sendEmail')}</a>
          </div>
        </div>
      )}

            {/* Express */}
      {canExpress && (
        <label style={{ display: 'flex', alignItems: 'center', gap: 10, cursor: 'pointer', padding: '12px 14px', borderRadius: 8, border: effectiveExpress ? `2px solid ${red}` : `1.5px solid ${borderClr}`, background: effectiveExpress ? '#FFF5F5' : '#fff', transition: 'all 0.2s' }}>
          <input type="checkbox" checked={express} onChange={e => { setExpress(e.target.checked); if (e.target.checked) setDelivery(false) }} style={{ width: 18, height: 18, accentColor: red }} />
          <div>
            <div style={{ fontSize: 13, fontWeight: 700, color: effectiveExpress ? red : dark }}>⚡ {t('express.title')}</div>
            <div style={{ fontSize: 11, color: mid, marginTop: 2 }}>{t('express.desc')}</div>
          </div>
        </label>
      )}

      {/* Delivery — pick-up or delivery (hidden when Express) */}
      <div>
        <div style={ls}>{t('delivery.label')}</div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
          <label style={{ display: 'flex', alignItems: 'flex-start', gap: 10, cursor: 'pointer', padding: '10px 12px', borderRadius: 8, border: !delivery ? `2px solid ${accent}` : `1.5px solid ${borderClr}`, background: !delivery ? '#EDE8FF' : '#fff' }}>
            <input type="radio" checked={!delivery} onChange={() => setDelivery(false)} style={{ accentColor: accent, marginTop: 2 }} />
            <div>
              <div style={{ fontSize: 13, fontWeight: 700, color: dark }}>{t('delivery.pickup.title')}</div>
              <div style={{ fontSize: 11, color: mid, marginTop: 2 }}>{t('delivery.pickup.address')}</div>
              <div style={{ fontSize: 12, fontWeight: 700, color: accent, marginTop: 3 }}>{t('delivery.pickup.free')}</div>
            </div>
          </label>
          {!effectiveExpress && (
            <label style={{ display: 'flex', alignItems: 'flex-start', gap: 10, cursor: 'pointer', padding: '10px 12px', borderRadius: 8, border: delivery ? `2px solid ${accent}` : `1.5px solid ${borderClr}`, background: delivery ? '#EDE8FF' : '#fff' }}>
              <input type="radio" checked={delivery} onChange={() => setDelivery(true)} style={{ accentColor: accent, marginTop: 2 }} />
              <div>
                <div style={{ fontSize: 13, fontWeight: 700, color: dark }}>{t('delivery.ship.title')}</div>
                <div style={{ fontSize: 11, color: mid, marginTop: 2 }}>{t('delivery.ship.desc')}</div>
                <div style={{ fontSize: 12, fontWeight: 700, color: accent, marginTop: 3 }}>{t('delivery.ship.price')}</div>
              </div>
            </label>
          )}
        </div>
      </div>

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

  // ── Step 2: Order screen ───────────────────────────────────────────────
  const orderScreen = result ? (
    <div style={{ maxWidth: 640, margin: '0 auto', padding: bp.isMobile ? '20px 16px 40px' : '40px 32px 60px' }}>
      {/* Back button */}
      <button onClick={() => { setStep(1); window.scrollTo(0, 0) }}
        style={{ background: 'none', border: 'none', cursor: 'pointer', padding: '8px 0', fontSize: 13, fontWeight: 700, color: accent, marginBottom: 20, display: 'flex', alignItems: 'center', gap: 6 }}>
        {t('btn.backToCalc')}
      </button>

      {/* Progress indicator */}
      <div style={{ display: 'flex', gap: 8, marginBottom: 28 }}>
        <div style={{ flex: 1, height: 4, borderRadius: 2, background: accent }} />
        <div style={{ flex: 1, height: 4, borderRadius: 2, background: accent }} />
        <div style={{ flex: 1, height: 4, borderRadius: 2, background: borderClr }} />
      </div>

      <h2 style={{ fontSize: 24, fontWeight: 800, color: dark, letterSpacing: '-0.03em', margin: '0 0 24px' }}>
        {t('order.complete')} <span style={{ color: accent }}>{t('order.orderWord')}</span> {t('order.completeEnd')}
      </h2>

      {/* ── Order summary (read-only) ──────────────────────────────────── */}
      <div style={{ background: panelBg, borderRadius: 14, padding: 24, marginBottom: 24 }}>
        <div style={{ fontSize: 14, fontWeight: 700, color: '#fff', marginBottom: 16 }}>{t('order.summary')}</div>
        <div style={{ background: 'rgba(255,255,255,0.04)', borderRadius: 10, padding: '12px 16px', marginBottom: 16 }}>
          {[
            { label: t('quote.product'),  value: isPoster ? t('product.poster') : t('product.flyer') },
            { label: t('quote.format'),   value: size === 'custom' ? `${parsedW}×${parsedH} mm` : (sizeOpts.find(s => s.value === size)?.label || size) },
            ...(isCP
              ? [{ label: t('quote.paper'), value: `${selCP.name} (${selCP.brand})` }, { label: t('quote.weight'), value: `${selCP.gsm} gsm` }]
              : [{ label: t('quote.paper'), value: pt?.name }, { label: t('quote.weight'), value: `${weight} gsm` }]),
            { label: t('quote.print'),    value: printDesc },
            { label: t('quote.sides'),    value: sides === 'double' ? t('sides.double') : t('sides.single') },
            ...((isFlyer || isPoster) && versions > 1 ? [{ label: t('quote.versions'), value: String(versions) }] : []),
            { label: t('quote.quantity'), value: result.totalQty.toLocaleString() },
            ...(effectiveExpress ? [{ label: t('quote.service'), value: `⚡ ${t('express.short')}` }] : []),
            { label: t('quote.delivery'), value: delivery ? t('delivery.ship.quote') : t('delivery.pickup.quote') },
          ].map((row, i, arr) => (
            <div key={i} style={{ display: 'flex', justifyContent: 'space-between', padding: '5px 0', borderBottom: i < arr.length - 1 ? '1px solid rgba(255,255,255,0.06)' : 'none' }}>
              <span style={{ fontSize: 11, color: '#aaa0cc' }}>{row.label}</span>
              <span style={{ fontSize: 11, color: '#fff', fontWeight: 600 }}>{row.value}</span>
            </div>
          ))}
        </div>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end' }}>
          <div>
            <div style={{ fontSize: 10, color: '#aaa0cc' }}>{t('quote.totalInclVat')}</div>
            <div style={{ fontSize: 32, fontWeight: 800, color: '#fff', fontFamily: mono, letterSpacing: '-0.03em' }}>€{totalWithDelivery.toFixed(2)}</div>
          </div>
          <div style={{ fontSize: 11, color: '#aaa0cc' }}>{t('quote.exclVat')}: €{totalExWithDelivery.toFixed(2)}</div>
        </div>
      </div>

      {/* ── Upload print files ─────────────────────────────────────────── */}
      <div style={{ background: '#fff', borderRadius: 14, padding: 24, border: `1.5px solid ${borderClr}`, marginBottom: 24 }}>
        <div style={{ fontSize: 14, fontWeight: 700, color: dark, marginBottom: 8 }}>📁 {t('upload.title')}</div>
        <div style={{ fontSize: 12, color: mid, marginBottom: 16, lineHeight: 1.6 }}>
          {t('upload.desc')}
        </div>
        <a href="https://kdrive.infomaniak.com/app/collaborate/1775931/59e2e00c-83d5-4087-9ff6-9e871391b9cc"
          target="_blank" rel="noopener noreferrer"
          style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8, padding: '14px 20px', borderRadius: 10, background: '#EDE8FF', color: accent, fontSize: 14, fontWeight: 700, textDecoration: 'none', border: `1.5px solid ${accent}`, transition: 'all 0.15s' }}
          onMouseOver={e => { e.currentTarget.style.background = accent; e.currentTarget.style.color = '#fff' }}
          onMouseOut={e  => { e.currentTarget.style.background = '#EDE8FF'; e.currentTarget.style.color = accent }}
        >
          📤 {t('upload.button')}
        </a>
        <div style={{ fontSize: 11, color: mid, marginTop: 8, fontStyle: 'italic' }}>
          {t('upload.tip')}
        </div>
      </div>

      {/* ── Your details ───────────────────────────────────────────────── */}
      <div style={{ background: '#fff', borderRadius: 14, padding: 24, border: `1.5px solid ${borderClr}`, marginBottom: 24 }}>
        <div style={{ fontSize: 14, fontWeight: 700, color: dark, marginBottom: 16 }}>{t('form.yourDetails')}</div>

        {[
          { label: `${t('form.fullName')} *`, val: custName,    set: setCustName,    ph: t('ph.name') },
          { label: `${t('form.email')} *`,    val: custEmail,   set: setCustEmail,   ph: t('ph.email'), type: 'email' },
          { label: t('form.phone'),            val: custPhone,   set: setCustPhone,   ph: t('ph.phone') },
        ].map(({ label, val, set, ph, type }) => (
          <div key={label} style={{ marginBottom: 12 }}>
            <div style={ls}>{label}</div>
            <input type={type || 'text'} value={val} onChange={e => set(e.target.value)} placeholder={ph} style={iStyle} />
          </div>
        ))}

        {/* Company (optional) */}
        <div style={{ marginBottom: 12 }}>
          <label style={{ display: 'flex', alignItems: 'center', gap: 8, cursor: 'pointer', marginBottom: isCompany ? 10 : 0 }}>
            <input type="checkbox" checked={isCompany} onChange={e => { setIsCompany(e.target.checked); if (!e.target.checked) { setVatNumber(''); setCustCompany('') } }} style={{ width: 16, height: 16, accentColor: accent }} />
            <span style={{ fontSize: 13, fontWeight: 600, color: dark }}>{t('form.isCompany')}</span>
          </label>
          {isCompany && (
            <>
              <div style={{ marginBottom: 10 }}>
                <div style={ls}>{t('form.companyName')}</div>
                <input type="text" value={custCompany} onChange={e => setCustCompany(e.target.value)} placeholder={t('form.companyName')} style={iStyle} />
              </div>
              <div>
                <div style={ls}>{t('form.vatNumber')}</div>
                <input type="text" value={vatNumber} onChange={e => setVatNumber(e.target.value)} placeholder={t('ph.vat')} style={iStyle} />
              </div>
            </>
          )}
        </div>

        {/* Address */}
        <div style={{ borderTop: `1px solid ${borderClr}`, paddingTop: 16, marginTop: 16 }}>
          <div style={{ fontSize: 12, fontWeight: 700, color: dark, marginBottom: 12 }}>{isCompany ? t('form.invoiceAddress') : t('form.address')}</div>
          <div style={{ marginBottom: 10 }}>
            <div style={ls}>{t('form.street')} *</div>
            <input type="text" value={custAddress} onChange={e => setCustAddress(e.target.value)} placeholder={t('ph.street')} style={iStyle} />
          </div>
          <div style={{ display: 'flex', gap: 10, marginBottom: 10 }}>
            <div style={{ flex: 1 }}>
              <div style={ls}>{t('form.zip')} *</div>
              <input type="text" value={custZip} onChange={e => setCustZip(e.target.value)} placeholder={t('ph.zip')} style={iStyle} />
            </div>
            <div style={{ flex: 2 }}>
              <div style={ls}>{t('form.city')} *</div>
              <input type="text" value={custCity} onChange={e => setCustCity(e.target.value)} placeholder={t('ph.city')} style={iStyle} />
            </div>
          </div>
        </div>

        {/* Different shipping address */}
        {delivery && (
          <div style={{ borderTop: `1px solid ${borderClr}`, paddingTop: 16, marginTop: 8 }}>
            <label style={{ display: 'flex', alignItems: 'center', gap: 8, cursor: 'pointer', marginBottom: diffShip ? 12 : 0 }}>
              <input type="checkbox" checked={diffShip} onChange={e => setDiffShip(e.target.checked)} style={{ width: 16, height: 16, accentColor: accent }} />
              <span style={{ fontSize: 13, fontWeight: 600, color: dark }}>{t('form.diffShip')}</span>
            </label>
            {diffShip && (
              <>
                <div style={{ marginBottom: 10 }}>
                  <div style={ls}>{t('form.shipStreet')} *</div>
                  <input type="text" value={shipAddress} onChange={e => setShipAddress(e.target.value)} placeholder={t('ph.street')} style={iStyle} />
                </div>
                <div style={{ display: 'flex', gap: 10 }}>
                  <div style={{ flex: 1 }}>
                    <div style={ls}>{t('form.shipZip')} *</div>
                    <input type="text" value={shipZip} onChange={e => setShipZip(e.target.value)} placeholder={t('ph.zip')} style={iStyle} />
                  </div>
                  <div style={{ flex: 2 }}>
                    <div style={ls}>{t('form.shipCity')} *</div>
                    <input type="text" value={shipCity} onChange={e => setShipCity(e.target.value)} placeholder={t('ph.city')} style={iStyle} />
                  </div>
                </div>
              </>
            )}
          </div>
        )}
      </div>

      {/* ── Pay ────────────────────────────────────────────────────────── */}
      {(() => {
        const canPay = custName && custEmail && custAddress && custCity && (!delivery || !diffShip || (shipAddress && shipCity))
        return (
          <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
            {/* Progress */}
            <div style={{ display: 'flex', gap: 8, marginBottom: 4 }}>
              <div style={{ flex: 1, height: 4, borderRadius: 2, background: accent }} />
              <div style={{ flex: 1, height: 4, borderRadius: 2, background: accent }} />
              <div style={{ flex: 1, height: 4, borderRadius: 2, background: canPay ? accent : borderClr }} />
            </div>
            <button
              onClick={canPay ? handleBancontact : undefined}
              style={{ width: '100%', padding: 18, borderRadius: 12, border: 'none', background: canPay ? 'linear-gradient(135deg,#003DFF 0%,#0052ff 100%)' : '#e0e0e0', color: canPay ? '#fff' : '#999', fontSize: 16, fontWeight: 800, cursor: canPay ? 'pointer' : 'not-allowed', letterSpacing: '-0.01em', boxShadow: canPay ? '0 4px 20px rgba(66,0,255,0.35)' : 'none', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 10, transition: 'all 0.2s' }}
              onMouseOver={e => { if (canPay) { e.currentTarget.style.transform = 'translateY(-2px)'; e.currentTarget.style.boxShadow = '0 8px 28px rgba(66,0,255,0.5)' }}}
              onMouseOut={e  => { if (canPay) { e.currentTarget.style.transform = 'none';             e.currentTarget.style.boxShadow = '0 4px 20px rgba(66,0,255,0.35)' }}}
            >
              <svg width="22" height="16" viewBox="0 0 42 28" fill="none">
                <rect width="42" height="28" rx="4" fill={canPay ? '#005498' : '#999'}/>
                <path d="M6 14h12l4-6h8l-4 6h6l-4 6H10l4-6H6z" fill={canPay ? '#FFD700' : '#ccc'}/>
              </svg>
              {canPay ? t('btn.payBancontact', { amount: totalWithDelivery.toFixed(2) }) : t('btn.fillRequired')}
            </button>
            {!canPay && (
              <div style={{ textAlign: 'center', fontSize: 11, color: mid }}>
                {t('receipt.required')}
              </div>
            )}
            <div style={{ textAlign: 'center', marginTop: 4, fontSize: 11, color: '#9B8F89' }}>
              🔒 {t('secure.mollie')}<br/>
              {isCompany ? t('receipt.company') : t('receipt.personal')}
            </div>
          </div>
        )
      })()}

      <div style={{ textAlign: 'center', padding: '24px 0 16px', fontSize: 11, color: '#9B8F89' }}>
        Click Click Graphics · Aalststraat 7-11, 1000 Brussels<br />
        <span style={{ fontSize: 9, color: '#C5B8B1' }}>Data: {dataSource}</span>
      </div>
    </div>
  ) : null

  // ── Render ───────────────────────────────────────────────────────────────
  if (step === 2 && result) {
    return <div style={{ background: bgPage, minHeight: '100vh' }}>{orderScreen}</div>
  }

  return (
    <div style={{ background: bgPage, minHeight: '100vh' }}>

      {bp.isWide ? (
        /* ── WIDE: two-column ───────────────────────────────────────────── */
        <div style={{ maxWidth: 1280, margin: '0 auto', display: 'grid', gridTemplateColumns: `1fr ${rightW}px`, minHeight: '100vh', alignItems: 'start' }}>

          {/* Left */}
          <div style={{ padding: leftPad, display: 'flex', flexDirection: 'column', gap: 16 }}>
            <div style={{ padding: '8px 0 4px', display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
              <div>
                <div style={{ fontSize: 26, fontWeight: 800, letterSpacing: '-0.03em', color: dark }}>
                  {t('header.print')} <span style={{ color: accent }}>{t('header.calculator')}</span>
                </div>
                <div style={{ fontSize: 12, color: mid, marginTop: 4 }}>{t('header.subtitle')}</div>
              </div>
              <LanguageToggle />
            </div>

            <div style={{ background: '#fff', borderRadius: 14, padding: 4, display: 'flex', border: `1.5px solid ${borderClr}` }}>
              {[{ value: 'flyer_cmyk', label: t('tab.flyer') }, { value: 'flyer_cp', label: t('tab.coloured') }, { value: 'poster', label: t('tab.poster') }].map(({ value, label }) => (
                <button key={value} onClick={() => handleMode(value)} style={mb(mode === value)}>{label}</button>
              ))}
            </div>

            <div style={{ fontSize: 12, color: mid, textAlign: 'center', padding: '2px 0' }}>
              {t('contact.notFound')}<br />
              <a href="mailto:info@clickclickgraphics.com" style={{ color: accent, textDecoration: 'none', fontWeight: 600 }}>info@clickclickgraphics.com</a>
              {' '}{t('contact.or')}{' '}
              <a href="tel:+3228807036" style={{ color: accent, textDecoration: 'none', fontWeight: 600 }}>+32 2 880 70 36</a>
            </div>

            {OptionsCard({ wide: true })}

            <div style={{ padding: '16px 0 24px', fontSize: 11, color: '#9B8F89' }}>
              Click Click Graphics · Aalststraat 7-11, 1000 Brussels<br />
              {t('footer.prices')}<br />
              <span style={{ fontSize: 9, color: '#C5B8B1' }}>Data: {dataSource}</span>
            </div>
          </div>

          {/* Right: sticky quote */}
          <div style={{ padding: rightPad, position: 'sticky', top: 0, maxHeight: '100vh', overflowY: 'auto', scrollbarWidth: 'thin', scrollbarColor: 'rgba(0,0,0,0.15) transparent' }}>
            <div style={{ paddingTop: bp.isDesktop ? 40 : 32 }}>
              {quotePanel}
            </div>
          </div>
        </div>

      ) : (
        /* ── MOBILE: single column + sticky bar ─────────────────────────── */
        <>
          <div style={{ padding: leftPad, display: 'flex', flexDirection: 'column', gap: 16 }}>
            <div style={{ textAlign: 'center', padding: '8px 0 4px' }}>
              <div style={{ display: 'flex', justifyContent: 'center', marginBottom: 8 }}><LanguageToggle /></div>
              <div style={{ fontSize: 22, fontWeight: 800, letterSpacing: '-0.03em', color: dark }}>
                {t('header.print')} <span style={{ color: accent }}>{t('header.calculator')}</span>
              </div>
              <div style={{ fontSize: 12, color: mid, marginTop: 4 }}>{t('header.subtitle')}</div>
            </div>

            <div style={{ background: '#fff', borderRadius: 14, padding: 4, display: 'flex', border: `1.5px solid ${borderClr}` }}>
              {[{ value: 'flyer_cmyk', label: t('tab.flyer') }, { value: 'flyer_cp', label: t('tab.coloured.short') }, { value: 'poster', label: t('tab.poster.short') }].map(({ value, label }) => (
                <button key={value} onClick={() => handleMode(value)} style={mb(mode === value)}>{label}</button>
              ))}
            </div>

            <div style={{ fontSize: 11, color: mid, textAlign: 'center', padding: '2px 0' }}>
              {t('contact.notFound')}{' '}
              <a href="mailto:info@clickclickgraphics.com" style={{ color: accent, textDecoration: 'none', fontWeight: 600 }}>{t('contact.contactUs')}</a>
            </div>

            {OptionsCard({ wide: false })}

            <div style={{ textAlign: 'center', padding: '16px 0 24px', fontSize: 11, color: '#9B8F89' }}>
              Click Click Graphics · Aalststraat 7-11, 1000 Brussels<br />
              {t('footer.prices')}<br />
              <span style={{ fontSize: 9, color: '#C5B8B1' }}>Data: {dataSource}</span>
            </div>
          </div>

          {result && (() => {
            const canPay = custName && custEmail && custAddress && custCity && (!delivery || !diffShip || (shipAddress && shipCity))
            return (
            <div style={{ position: 'fixed', bottom: 0, left: 0, right: 0, zIndex: 100, background: panelBg, boxShadow: '0 -4px 24px rgba(66,0,255,0.25)' }}>
              {mobileQuoteOpen && (
                <div style={{ maxHeight: '85vh', overflowY: 'auto', padding: '0 16px' }}>
                  <style>{`@keyframes slideUp { from { opacity:0; transform:translateY(20px) } to { opacity:1; transform:translateY(0) } }`}</style>
                  <div style={{ animation: 'slideUp 0.25s ease-out' }}>{quotePanel}</div>
                </div>
              )}
              <div style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '12px 16px', borderTop: mobileQuoteOpen ? '1px solid rgba(255,255,255,0.08)' : 'none' }}>
                <button onClick={() => setMobileQuoteOpen(o => !o)} style={{ flex: 1, background: 'none', border: 'none', cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 10, padding: 0 }}>
                  <div style={{ textAlign: 'left' }}>
                    <div style={{ fontSize: 11, color: '#aaa0cc', fontWeight: 600, textTransform: 'uppercase', letterSpacing: '0.08em' }}>{t('quote.totalInclVat')}</div>
                    <div style={{ fontSize: 26, fontWeight: 800, color: '#FFF5EF', fontFamily: mono, letterSpacing: '-0.03em', lineHeight: 1.1 }}>€{totalWithDelivery.toFixed(2)}</div>
                  </div>
                  <div style={{ marginLeft: 4, fontSize: 11, color: '#22c55e', fontWeight: 700 }}>{mobileQuoteOpen ? `▼ ${t('mobile.close')}` : `▲ ${t('mobile.details')}`}</div>
                </button>
                <button
                  onClick={() => { setStep(2); window.scrollTo(0, 0) }}
                  style={{ flexShrink: 0, padding: '13px 16px', borderRadius: 12, border: 'none', background: 'linear-gradient(135deg,#4200FF 0%,#6633FF 100%)', color: '#fff', fontSize: 13, fontWeight: 800, cursor: 'pointer', boxShadow: '0 4px 16px rgba(66,0,255,0.4)', display: 'flex', alignItems: 'center', gap: 6, transition: 'all 0.2s' }}
                >
                  {t('btn.order')}
                </button>
                <button
                  onClick={canPay ? handleBancontact : undefined}
                  style={{ flexShrink: 0, padding: '13px 16px', borderRadius: 12, border: 'none', background: canPay ? 'linear-gradient(135deg,#003DFF 0%,#0052ff 100%)' : '#555', color: canPay ? '#fff' : '#999', fontSize: 13, fontWeight: 800, cursor: canPay ? 'pointer' : 'not-allowed', boxShadow: canPay ? '0 4px 16px rgba(66,0,255,0.4)' : 'none', display: 'flex', alignItems: 'center', gap: 6, transition: 'all 0.2s' }}
                >
                  <svg width="16" height="11" viewBox="0 0 42 28" fill="none">
                    <rect width="42" height="28" rx="4" fill={canPay ? '#005498' : '#777'}/>
                    <path d="M6 14h12l4-6h8l-4 6h6l-4 6H10l4-6H6z" fill={canPay ? '#FFD700' : '#999'}/>
                  </svg>
                  Pay
                </button>
              </div>
            </div>
            )
          })()}
        </>
      )}
    </div>
  )
}
