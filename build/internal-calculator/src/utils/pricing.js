import { CLICK_PRICING, PPS, LONG_SIZES, SRA3_W, SRA3_H, PRINTABLE_W, PRINTABLE_H, SETTINGS, getClickSellRate } from './constants.js'

// ── Helpers ─────────────────────────────────────────────────────────────────

export function getClickKey(printMode, sides, isXXL) {
  if (printMode === 'cmyk')
    return sides === 'single' ? '4/0_quadri' : '4/4_quadri'
  if (printMode === 'white_only')
    return isXXL
      ? (sides === 'single' ? '1/0_white_xxl' : '1/1_white_xxl')
      : (sides === 'single' ? '1/0_white'     : '1/1_white')
  return sides === 'single' ? '1/0_noir' : '1/1_noir'
}

export function getClickPrice(key) {
  const p = CLICK_PRICING[key]
  return p ? { sell: p.clickSell } : { sell: 0 }
}

// For Coloured Papers: compute click cost and sell per SRA3 sheet
// Single ink per side. Double = single × 2 (front + back each get the single rate)
export function getColouredClickPerSheet(frontInk, backInk, isXXL) {
  const {
    CLICK_WHITE_SELL, CLICK_BLACK_SELL, CLICK_COLOUR_SELL,
    CLICK_WHITE_COST, CLICK_BLACK_COST, CLICK_COLOUR_COST,
  } = SETTINGS

  function inkSell(ink) {
    if (ink === 'white')  return CLICK_WHITE_SELL
    if (ink === 'black')  return CLICK_BLACK_SELL
    if (ink === 'colour') return CLICK_COLOUR_SELL
    return 0
  }
  function inkCost(ink) {
    if (ink === 'white')  return CLICK_WHITE_COST
    if (ink === 'black')  return CLICK_BLACK_COST
    if (ink === 'colour') return CLICK_COLOUR_COST
    return 0
  }

  // frontInk and backInk are arrays with a single element e.g. ['white']
  const front = Array.isArray(frontInk) && frontInk.length > 0 ? frontInk[0] : null
  const back  = Array.isArray(backInk) && backInk.length > 0 ? backInk[0] : null

  let sell = front ? inkSell(front) : 0
  let cost = front ? inkCost(front) : 0
  if (back) {
    sell += inkSell(back)
    cost += inkCost(back)
  }

  return { sell, cost }
}

export function calcCustomPPS(w, h) {
  // Impose against the PRINTABLE area, not the sheet — 10 mm is unprintable on
  // every side. Using the full 320x450 claimed pieces fitted that do not.
  //
  // NB the trailing 1: anything too big to fit at all still returns 1, i.e. "one
  // per sheet", which is why the UI clamps the inputs to the printable size. A
  // format larger than that is a banner job and belongs to the LP size, which
  // has its own paper, click and speed rules.
  return Math.max(
    Math.floor(PRINTABLE_W / w) * Math.floor(PRINTABLE_H / h),
    Math.floor(PRINTABLE_W / h) * Math.floor(PRINTABLE_H / w),
    1
  )
}

export function roundPrice(price) {
  const { ROUND_STEP, ROUND_DOWN_THRESHOLD } = SETTINGS
  const lower = Math.floor(price / ROUND_STEP) * ROUND_STEP
  const above = price - lower
  if (above === 0) return price
  if (above <= ROUND_DOWN_THRESHOLD) return lower
  return lower + ROUND_STEP
}

// ── Main pricing function ────────────────────────────────────────────────────
export function calcPrice(job) {
  const {
    paperType, weight, size, quantity, sides, printMode,
    versions, diffColors, customW, customH, express,
    frontInk, backInk, creativePaperId, versionPaperIds,
    paperData, creativePapers,
  } = job

  const {
    SETUP_SHEETS, PREPRESS_FEE, MACHINE_COST_PER_HOUR, MACHINE_SELL_PER_HOUR,
    LABOR_MINUTES, MARKUP, MIN_MARGIN, CSF, VAT, EXPRESS_MARKUP, MACHINE_SPEED,
    MACHINE_SPEED_LONG, LONG_SIZE_UPLIFT,
    CLICK_CMYK_COST,
  } = SETTINGS

  const isCustom   = size === 'custom'
  const isLong     = LONG_SIZES.includes(size)
  const pps        = isCustom ? calcCustomPPS(customW, customH) : PPS[size]
  const isCreative = !!creativePaperId
  const totalQty   = quantity * versions
  const isXXL      = size === 'A3' || isLong || (isCustom && pps === 1)

  // Paper cost per SRA3 sheet
  function paperCostPerSheet(cpId) {
    if (isCreative) {
      const cp = creativePapers.find(p => p.id === cpId)
      if (!cp) return 0
      return cp.colorCost > 0 ? (cp.colorCost / cp.cuts) : (cp.rawCost / cp.cuts)
    }
    const pd = paperData[paperType]?.[String(weight)]
    if (!pd) return 0
    // pd.cost is the mother-sheet cost and pd.s how many SRA3 come off it. A
    // tall poster takes one 320x620 strip per mother, so there is nothing to
    // divide by — it costs the whole sheet. Same rule as calc.php.
    return isLong ? pd.cost : (pd.cost / pd.s)
  }

  // Color changes for creative papers with different papers per version
  let colorChanges = 0
  if (isCreative && diffColors && versions > 1) {
    const used = new Set()
    for (let v = 0; v < versions; v++)
      used.add(versionPaperIds?.[v] || creativePaperId)
    colorChanges = Math.max(0, used.size - 1)
  }

  let printSra3 = 0, paperCostTotal = 0
  for (let v = 0; v < versions; v++) {
    const cpId   = isCreative ? (diffColors && versionPaperIds?.[v] ? versionPaperIds[v] : creativePaperId) : null
    const sra3v  = Math.ceil(quantity / pps)
    printSra3     += sra3v
    paperCostTotal += (sra3v + SETUP_SHEETS) * paperCostPerSheet(cpId)
  }

  if (!isCreative) {
    const pd = paperData[paperType]?.[String(weight)]
    if (!pd) return null
  }

  const totalSra3      = printSra3 + (SETUP_SHEETS * versions)
  const paperSellTotal = paperCostTotal * (1 + MARKUP / 100)

  // Click calculation — volume-tiered sell rates
  let clickSellTotal, clickCostTotal = 0, copiesPerHour = isLong ? MACHINE_SPEED_LONG : MACHINE_SPEED
  if (isCreative && frontInk) {
    const effectiveBack = sides === 'double' ? backInk : []
    const front = Array.isArray(frontInk) && frontInk.length > 0 ? frontInk[0] : null
    const back  = Array.isArray(effectiveBack) && effectiveBack.length > 0 ? effectiveBack[0] : null

    // Sell: tiered by total sheets
    let sellPerSheet = front ? getClickSellRate(front, totalSra3, isXXL) : 0
    if (back) sellPerSheet += getClickSellRate(back, totalSra3, isXXL)
    clickSellTotal = totalSra3 * sellPerSheet

    // Cost: use internal cost rates
    const { CLICK_WHITE_COST, CLICK_BLACK_COST, CLICK_COLOUR_COST, CLICK_CMYK_COST } = SETTINGS
    function inkCost(ink) {
      if (ink === 'white')  return isXXL ? 0.400 : CLICK_WHITE_COST
      if (ink === 'black')  return CLICK_BLACK_COST
      if (ink === 'colour') return CLICK_COLOUR_COST
      return CLICK_CMYK_COST
    }
    let costPerSheet = front ? inkCost(front) : 0
    if (back) costPerSheet += inkCost(back)
    clickCostTotal = totalSra3 * costPerSheet
  } else {
    // CMYK mode — tiered sell rate
    const sellRate = getClickSellRate('cmyk', totalSra3, isXXL)
    const sellPerSheet = sides === 'double' ? sellRate * 2 : sellRate
    clickSellTotal = totalSra3 * sellPerSheet
    // Cost
    const costPerSheet = sides === 'double' ? (CLICK_CMYK_COST * 2) : CLICK_CMYK_COST
    clickCostTotal = totalSra3 * costPerSheet
    copiesPerHour = isLong ? MACHINE_SPEED_LONG : MACHINE_SPEED
  }

  const machineHours     = totalSra3 / copiesPerHour
  const laborHours       = LABOR_MINUTES / 60
  const totalMachineHours = machineHours + laborHours
  const machineCost      = totalMachineHours * MACHINE_COST_PER_HOUR
  const machineSell      = totalMachineHours * MACHINE_SELL_PER_HOUR
  const colorChangeFee   = colorChanges * CSF
  const totalCost        = paperCostTotal + clickCostTotal + machineCost + colorChangeFee
  const totalSell        = paperSellTotal + clickSellTotal + machineSell + PREPRESS_FEE + colorChangeFee
  const margin           = totalCost > 0 ? ((totalSell - totalCost) / totalCost) * 100 : 0

  let marginSell = totalSell, surcharge = 0
  if (margin < MIN_MARGIN) {
    marginSell = totalCost * (1 + MIN_MARGIN / 100)
    surcharge  = marginSell - totalSell
  }

  // Surcharge for the unusual format, applied after the minimum-margin floor so
  // it is a clean uplift on the customer price. Mirrors calc.php.
  if (isLong) marginSell *= (1 + LONG_SIZE_UPLIFT / 100)

  const expressFee    = express ? (marginSell * EXPRESS_MARKUP / 100) : 0
  const expressSell   = marginSell + expressFee
  const rawInclVat    = expressSell * (1 + VAT / 100)
  const roundedInclVat = roundPrice(rawInclVat)
  const finalExVat    = roundedInclVat / (1 + VAT / 100)
  const vatAmount     = roundedInclVat - finalExVat
  const finalCost     = totalCost   // all costs: paper + clicks + machine + color changes
  const finalProfit   = finalExVat - finalCost
  const finalMargin   = finalCost > 0 ? (finalProfit / finalCost) * 100 : 0

  const r2 = v => Math.round(v * 100) / 100
  const r4 = v => Math.round(v * 10000) / 10000

  return {
    totalExVat:       r2(finalExVat),
    totalInclVat:     r2(roundedInclVat),
    perPieceExVat:    r4(finalExVat / totalQty),
    perPieceInclVat:  r4(roundedInclVat / totalQty),
    totalQty,
    sra3Used: totalSra3,
    pps,
    vatAmount: r2(vatAmount),
    breakdown: {
      paperCost:      r2(paperCostTotal),
      paperSell:      r2(paperSellTotal),
      clickCost:      r2(clickCostTotal),
      clickSell:      r2(clickSellTotal),
      machineCost:    r2(machineCost),
      machineSell:    r2(machineSell),
      prepressCost:   PREPRESS_FEE,
      prepressSell:   PREPRESS_FEE,
      colorChangeFee,
      colorChanges,
      expressFee:     r2(expressFee),
      totalCost:      r2(totalCost),
      totalSell:      r2(totalSell),
      surcharge:      r2(surcharge),
      marginSell:     r2(marginSell),
      finalSell:      r2(finalExVat),
      finalInclVat:   r2(roundedInclVat),
      profit:         r2(finalProfit),
      margin:         Math.round(finalMargin * 10) / 10,
      finalCost:      r2(finalCost),
      printMinutes:   Math.round(machineHours * 60),
      setupMinutes:   LABOR_MINUTES,
      totalMinutes:   Math.round(totalMachineHours * 60),
    },
  }
}
