// ── Brand colours ──────────────────────────────────────────────────────────
export const BRAND = {
  blue:  '#003DFF',
  white: '#FFFFFF',
  red:   '#FF1F2A',
  grey:  '#F7F7F7',
  green: '#095A4D',
}

// ── Excel file path (served as a static asset) ─────────────────────────────
// Single source of truth shared with the flyer calculator (calc.php reads
// the same file server-side via /calculator/data/print-calculator-values.xlsx).
export const XLSX_FILE = '/calculator/data/print-calculator-values.xlsx'

// ── Default paper data (overwritten from Excel on load) ────────────────────
export const DEFAULT_PAPER_DATA = {
  multiart: {
    '115': { s: 2, cost: 0.047568 },
    '130': { s: 2, cost: 0.053772 },
    '170': { s: 2, cost: 0.070317 },
    '250': { s: 2, cost: 0.105248 },
    '300': { s: 2, cost: 0.354428 },
    '350': { s: 2, cost: 0.149923 },
    '400': { s: 2, cost: 0.323133 },
  },
  recystar: {
    '90':  { s: 1, cost: 0.0875   },
    '135': { s: 2, cost: 0.24376  },
    '250': { s: 1, cost: 0.27042  },
    '300': { s: 1, cost: 0.3245   },
  },
  biotop: {
    '120': { s: 4, cost: 0.232144 },
    '160': { s: 1, cost: 0.078682 },
    '250': { s: 1, cost: 0.12726  },
    '300': { s: 1, cost: 0.152712 },
  },
}

// ── Default creative papers ─────────────────────────────────────────────────
export const DEFAULT_CREATIVE_PAPERS = [
  { id: 'cp_forest',    name: 'Forest Green',      brand: 'Colorplan',   gsm: 350, cuts: 8, rawCost: 0.21728,   colorCost: 4.475, hex: '#2e5733' },
  { id: 'cp_claret',    name: 'Claret (Bordeaux)',  brand: 'Colorplan',   gsm: 350, cuts: 8, rawCost: 0.21728,   colorCost: 4.351, hex: '#7b1f3a' },
  { id: 'cp_purple',    name: 'Purple',             brand: 'Colorplan',   gsm: 350, cuts: 8, rawCost: 0.21728,   colorCost: 4.433, hex: '#6a1b9a' },
  { id: 'cp_matcha',    name: 'Matcha Green',       brand: 'Colorplan',   gsm: 350, cuts: 8, rawCost: 0.21728,   colorCost: 4.267, hex: '#8aab5e' },
  { id: 'cp_sapphire',  name: 'Sapphire Blue',      brand: 'Colorplan',   gsm: 350, cuts: 8, rawCost: 0.21728,   colorCost: 4.31,  hex: '#0d47a1' },
  { id: 'fed_black',    name: 'Sirio Ultra Black',  brand: 'Fedrigoni',   gsm: 280, cuts: 8, rawCost: 0.258274,  colorCost: 4.00,  hex: '#1c1c1c' },
  { id: 'cc_vermilion', name: 'Vermilion Red',      brand: 'Click Click', gsm: 300, cuts: 8, rawCost: 0.233,     colorCost: 4.90,  hex: '#d32f2f' },
  { id: 'cc_lavender',  name: 'Lavender',           brand: 'Click Click', gsm: 300, cuts: 8, rawCost: 0.2657,    colorCost: 4.694, hex: '#b39ddb' },
  { id: 'cc_navy',      name: 'Navy Dark Blue',     brand: 'Click Click', gsm: 300, cuts: 8, rawCost: 0.2657,    colorCost: 4.69,  hex: '#1a47a0' },
]

// ── Flyer & poster sizes ────────────────────────────────────────────────────
export const FLYER_SIZES = [
  { value: 'BC',     label: 'Business Card (85×55 mm)' },
  { value: 'A7',     label: 'A7 (74×105 mm)'           },
  { value: 'A6',     label: 'A6 (105×148 mm)'          },
  { value: 'A5',     label: 'A5 (148×210 mm)'          },
  { value: 'A4',     label: 'A4 (210×297 mm)'          },
  { value: 'A3',     label: 'A3 (297×420 mm)'          },
  { value: 'custom', label: 'Custom Size'               },
]

export const POSTER_SIZES = [
  { value: 'A4',     label: 'A4 (210×297 mm)' },
  { value: 'A3',     label: 'A3 (297×420 mm)' },
  { value: 'LP',     label: 'Tall poster (300×600 mm)' },
  { value: 'custom', label: 'Custom Size'       },
]

// ── Click pricing table ─────────────────────────────────────────────────────
// ── Volume-tiered click sell pricing (from Konica C12010s lease) ─────────
// Tiers based on total SRA3 sheets per job
// Standard format:
export const CLICK_TIERS = [
  { max: 249,   black: 0.056, blanc: 0.150, quadri: 0.135 },
  { max: 399,   black: 0.050, blanc: 0.130, quadri: 0.113 },
  { max: 499,   black: 0.045, blanc: 0.110, quadri: 0.090 },
  { max: 700,   black: 0.036, blanc: 0.090, quadri: 0.081 },
  { max: 899,   black: 0.033, blanc: 0.080, quadri: 0.076 },
  { max: 99999, black: 0.033, blanc: 0.070, quadri: 0.067 },
]
// XXL format (A3 / custom 1-up):
export const CLICK_TIERS_XXL = [
  { max: 249,   noir: 0.120, blanc: 0.300, quadri: 0.270 },
  { max: 399,   noir: 0.100, blanc: 0.260, quadri: 0.226 },
  { max: 499,   noir: 0.090, blanc: 0.220, quadri: 0.180 },
  { max: 700,   noir: 0.072, blanc: 0.180, quadri: 0.162 },
  { max: 899,   noir: 0.066, blanc: 0.160, quadri: 0.152 },
  { max: 99999, noir: 0.066, blanc: 0.140, quadri: 0.134 },
]

// Helper: get sell rate for a given ink type, sheet count, and XXL flag
export function getClickSellRate(ink, totalSheets, isXXL) {
  if (isXXL) {
    const tier = CLICK_TIERS_XXL.find(t => totalSheets <= t.max) || CLICK_TIERS_XXL[CLICK_TIERS_XXL.length - 1]
    if (ink === 'white') return tier.blanc
    if (ink === 'black') return tier.noir
    if (ink === 'colour') return tier.quadri
    return tier.quadri // cmyk
  }
  const tier = CLICK_TIERS.find(t => totalSheets <= t.max) || CLICK_TIERS[CLICK_TIERS.length - 1]
  if (ink === 'white') return tier.blanc
  if (ink === 'black') return tier.black
  if (ink === 'colour') return tier.quadri
  return tier.quadri // cmyk
}

// Legacy flat table (still used for copiesPerHour lookup)
export const CLICK_PRICING = {
  '1/0_noir':      { clickSell: 0.056, copiesPerHour: 4140 },
  '1/1_noir':      { clickSell: 0.112, copiesPerHour: 4140 },
  '4/0_quadri':    { clickSell: 0.135, copiesPerHour: 4140 },
  '4/4_quadri':    { clickSell: 0.270, copiesPerHour: 4140 },
  '1/0_white':     { clickSell: 0.150, copiesPerHour: 4140 },
  '1/1_white':     { clickSell: 0.300, copiesPerHour: 4140 },
  '1/0_white_xxl': { clickSell: 0.300, copiesPerHour: 4140 },
  '1/1_white_xxl': { clickSell: 0.600, copiesPerHour: 4140 },
}

// ── Pieces per SRA3 sheet ───────────────────────────────────────────────────
export const PPS = { BC: 20, A7: 16, A6: 8, A5: 4, A4: 2, A3: 1, LP: 1 }

// Tall poster, 300x600 mm. It does NOT fit an SRA3 (320x450) — it is fed as a
// ~320x620 strip on the banner tray (press maximum 330x900). Mirrors
// LONG_SIZES / SIZE_PAPERS in calculator/calc.php; keep the two in step.
export const LONG_SIZES = ['LP']
// Only MultiArt is cut for this format so far.
export const LONG_SIZE_PAPERS = ['multiart']

// ── SRA3 sheet dimensions (mm) ─────────────────────────────────────────────
export const SRA3_W = 320
export const SRA3_H = 450

// The press cannot print to the edge: 10 mm is lost on every side, so the
// imposable area is 20 mm smaller in each direction. Confirmed 20 Aug 2026.
//
// This is what the fixed PPS map above was always built against — check it:
// A7 gives 16 per sheet on 300x430 but 18 on the full 320x450, and A6 gives 8
// against 9. The map says 16 and 8. calcCustomPPS, however, was using the full
// sheet, so every custom size that straddled the difference was told more
// pieces fitted than actually do — which under-counts sheets and under-prices
// the job.
export const PRINT_MARGIN = 10
export const PRINTABLE_W  = SRA3_W - PRINT_MARGIN * 2   // 300
export const PRINTABLE_H  = SRA3_H - PRINT_MARGIN * 2   // 430

// ── Default global settings (overwritten from Excel Settings sheet) ─────────
export let SETTINGS = {
  PREPRESS_FEE:           9.60,
  SETUP_SHEETS:           6,
  MACHINE_COST_PER_HOUR:  85,
  MACHINE_SELL_PER_HOUR:  120,
  LABOR_MINUTES:          15,
  // Press speed, SRA3 sheets per hour. Read from the Settings sheet of the
  // shared xlsx (machine_speed); this is only the fallback for when that file
  // cannot be loaded. It was hard-coded in pricing.js before 19 Aug 2026, so a
  // speed change in the spreadsheet reached the shop but silently not this tool.
  MACHINE_SPEED:          4140,
  // Banner feed for the tall poster, and the surcharge for the unusual format.
  // Both come from the shared xlsx (machine_speed_long / long_size_uplift);
  // these are only fallbacks for when that file cannot be loaded.
  MACHINE_SPEED_LONG:     1242,
  LONG_SIZE_UPLIFT:       25,
  LABOR_COST_PER_HOUR:    55,
  LABOR_CHARGE_PER_HOUR:  60,
  MARKUP:                 50,
  MIN_MARGIN:             30,
  CSF:                    20,
  VAT:                    21,
  ROUND_STEP:             5,
  ROUND_DOWN_THRESHOLD:   1,
  MAX_VERSIONS:           5,
  EXPRESS_MARKUP:         35,
  // ── Click COST rates (internal cost for CCG) ──
  CLICK_CMYK_COST:        0.035,
  CLICK_WHITE_COST:       0.200,
  CLICK_BLACK_COST:       0.012,
  CLICK_COLOUR_COST:      0.035,   // Colour = CMYK on coloured paper
  // ── Click SELL rates (charged to customer, from Konica lease) ──
  // Double = single × 2
  CLICK_CMYK_SELL:        0.135,
  CLICK_WHITE_SELL:       0.150,
  CLICK_BLACK_SELL:       0.056,
  CLICK_COLOUR_SELL:      0.135,   // Colour = CMYK (Quadri)
}

// Apply settings loaded from Excel
export function applySettings(s) {
  if (s.setup_sheets         != null) SETTINGS.SETUP_SHEETS           = s.setup_sheets
  if (s.prepress_fee         != null) SETTINGS.PREPRESS_FEE           = s.prepress_fee
  if (s.labor_minutes        != null) SETTINGS.LABOR_MINUTES          = s.labor_minutes
  if (s.machine_cost_rate    != null) SETTINGS.MACHINE_COST_PER_HOUR  = s.machine_cost_rate
  if (s.machine_sell_rate    != null) SETTINGS.MACHINE_SELL_PER_HOUR  = s.machine_sell_rate
  if (s.machine_speed        != null) SETTINGS.MACHINE_SPEED          = s.machine_speed
  if (s.machine_speed_long   != null) SETTINGS.MACHINE_SPEED_LONG     = s.machine_speed_long
  if (s.long_size_uplift     != null) SETTINGS.LONG_SIZE_UPLIFT       = s.long_size_uplift * 100
  if (s.paper_markup         != null) SETTINGS.MARKUP                 = s.paper_markup * 100
  if (s.min_margin           != null) SETTINGS.MIN_MARGIN             = s.min_margin * 100
  if (s.vat_rate             != null) SETTINGS.VAT                    = s.vat_rate * 100
  if (s.color_switch_fee     != null) SETTINGS.CSF                    = s.color_switch_fee
  if (s.express_markup       != null) SETTINGS.EXPRESS_MARKUP         = s.express_markup * 100
  if (s.round_step           != null) SETTINGS.ROUND_STEP             = s.round_step
  if (s.round_down_threshold != null) SETTINGS.ROUND_DOWN_THRESHOLD   = s.round_down_threshold
  // ── Click COST rates ──
  if (s.click_cmyk_cost_single   != null) SETTINGS.CLICK_CMYK_COST    = s.click_cmyk_cost_single
  if (s.click_white_cost_single  != null) SETTINGS.CLICK_WHITE_COST   = s.click_white_cost_single
  if (s.click_black_cost_single  != null) SETTINGS.CLICK_BLACK_COST   = s.click_black_cost_single
  if (s.click_colour_cost_single != null) SETTINGS.CLICK_COLOUR_COST  = s.click_colour_cost_single
  // ── Click SELL rates ──
  if (s.click_cmyk_sell_single   != null) SETTINGS.CLICK_CMYK_SELL    = s.click_cmyk_sell_single
  if (s.click_white_sell_single  != null) SETTINGS.CLICK_WHITE_SELL   = s.click_white_sell_single
  if (s.click_black_sell_single  != null) SETTINGS.CLICK_BLACK_SELL   = s.click_black_sell_single
  if (s.click_colour_sell_single != null) SETTINGS.CLICK_COLOUR_SELL  = s.click_colour_sell_single
}
