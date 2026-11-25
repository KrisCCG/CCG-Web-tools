# Click Click Graphics — Calculator & Dashboard Documentation

## Overview

Two React calculators for Click Click Graphics print shop (Brussels), plus an internal production dashboard. All built with React + Vite and compiled to standalone HTML files.

- **customer.html** — Public-facing price calculator (VAT-inclusive, no margins shown)
- **print.html** — Internal calculator (shows margins, cost breakdowns, profit) + Dashboard

Pricing data is loaded live from `print-calculator-values.xlsx` via SheetJS, with hardcoded fallbacks.

---

## Project Structure

```
clickclick-calculator/
├── src/
│   ├── main-customer.jsx              # Entry: renders CustomerCalculator
│   ├── main-print.jsx                 # Entry: renders PrintCalculator
│   ├── calculators/
│   │   ├── CustomerCalculator.jsx     # Public calculator + order form
│   │   └── PrintCalculator.jsx        # Internal calculator + dashboard tab
│   ├── components/
│   │   ├── Dashboard.jsx              # Production / Orders / Browse Stats
│   │   └── PaperPicker.jsx            # Colour swatch picker for creative papers
│   └── utils/
│       ├── pricing.js                 # Core pricing engine (calcPrice)
│       ├── constants.js               # Paper data, click tiers, settings
│       ├── excelLoader.js             # Parses Excel pricing file
│       └── dateHelpers.js             # Working day calculations
├── pay.php                            # Creates Mollie payment + saves order JSON
├── webhook.php                        # Mollie webhook: updates order, sends emails + PDF
├── thankyou.php                       # Post-payment confirmation page
├── api-dashboard.php                  # Dashboard REST API (orders, logs, updates)
├── log-calc.php                       # Anonymous browse logging endpoint
├── public/
│   └── print-calculator-values.xlsx   # Excel pricing data (4 sheets)
├── customer.html                      # HTML entry for public calculator
├── print.html                         # HTML entry for internal calculator
├── vite.config.js                     # Dual entry build config
├── package.json                       # React 18 + Vite + SheetJS
└── dist/                              # Build output (upload this to server)
```

---

## Calculators

### Customer Calculator (customer.html)

Two-step flow: quote builder then checkout.

**Step 1 — Quote Builder:**
- Three product tabs: Flyer / Card, Coloured Papers, Mini Poster
- Paper selection: MultiArt Silk, RecyStar, Biotop (with weight options)
- Coloured papers: 9 creative papers (Colorplan, Fedrigoni) with colour swatch picker
- Sizes: BC, A7, A6, A5, A4, A3, Custom (mm input)
- Print sides: Recto only / Recto & Verso
- Versions: 1–5 (with option for different paper per version on coloured)
- Ink options (coloured only): White, Black, Colour per side
- Express service: +35% (48h turnaround, not available for coloured papers)
- Delivery: Pick-up (free) or DPD/bike (+15.00 incl. VAT)
- Real-time price updates as parameters change
- Contact line: "Didn't find what you are looking for?" with email + phone

**Step 2 — Order Form:**
- Customer info: name, email, phone
- Company toggle: company name + VAT number (for invoice/Peppol)
- Invoice address: street, zip, city
- Shipping address: optional different address
- Receipt vs Invoice toggle
- Submits to pay.php for Mollie checkout

**Responsive:** Mobile (single column + sticky bottom bar), Tablet, Desktop (two-column with sticky quote panel)

### Internal Calculator (print.html)

Same quote builder as customer, plus:
- Full cost breakdown table: paper cost/sell, click cost/sell, machine, prepress, express fee
- Margin percentages per line item (colour-coded: green >40%, orange <40%)
- Min margin surcharge (auto-applied when margin drops below 30%)
- Final price: ex-VAT and incl. VAT
- Dashboard tab (see below)

---

## Pricing Engine (pricing.js)

`calcPrice(job)` calculates complete quotes:

1. **Paper cost:** Looks up raw cost by type/weight/GSM, applies 50% markup
2. **Sheet calculation:** Pieces per SRA3 sheet (PPS), total sheets needed including 6 setup sheets
3. **Click pricing:** Volume-tiered rates per sheet:
   - Standard tiers: 1–249, 250–399, 400+ sheets
   - XXL tiers: for A3 / 1-up custom sizes (doubled rates)
   - Separate rates for CMYK, white ink, black ink, colour ink
4. **Machine/labour:** Fixed 25min labour + hourly machine rate (cost: 85/h, sell: 120/h)
5. **Prepress:** Fixed fee (default: 9.60)
6. **Colour changes:** Per-variant fee for multi-paper versions (20 per change)
7. **Margin enforcement:** If margin < 30%, adds surcharge to reach minimum
8. **Express:** +35% on final price
9. **VAT:** 21% Belgian VAT
10. **Rounding:** Nearest 0.05 with configurable thresholds

### Excel Data (print-calculator-values.xlsx)

Four sheets parsed at runtime:
- **CMYK Papers:** paper_type, gsm, cuts_per_mother, raw_cost
- **Coloured Papers:** paper_id, name, brand, gsm, cuts, raw_cost, color_cost, hex
- **Settings:** Key-value pairs overriding all SETTINGS constants
- **Paper Types:** paper_type display names for UI labels

Falls back to hardcoded defaults (constants.js) if file unavailable.

---

## Payment Flow

```
Customer clicks "Continue to order"
  -> Fills customer info (step 2)
  -> Clicks "Order" button
     |
     v
pay.php
  - Validates & sanitises all input
  - Generates invoice number (YYYY-NNN from counter file)
  - Creates Mollie payment via API (amount, description, metadata)
  - Saves order JSON to orders/{invoice_num}.json (status: "pending")
  - Redirects user to Mollie checkout
     |
     v
Mollie checkout (Bancontact, credit card, etc.)
     |
     v
webhook.php (called by Mollie server-to-server)
  - Fetches payment details from Mollie API
  - Updates order JSON with status, paid_at, amounts
  - On "paid":
    - Generates PDF invoice/receipt (if fpdf.php available)
    - Emails customer: confirmation + PDF attachment
    - Emails staff (kris@clickclickgraphics.com): notification + PDF
    - Appends to paid_orders.log
     |
     v
thankyou.php (user redirected here)
  - Shows confirmation card with invoice number
```

### Order JSON Structure

```json
{
  "invoice_num": "2026-027",
  "mollie_id": "tr_...",
  "amount_eur": "74.38",
  "amount_inc": 90.00,
  "amount_ex": 74.38,
  "specs": "Flyer / Card . A5 (148x210 mm) . Vermilion Red 300g/m2 . White . Recto only . 50 copies . Delivery",
  "status": "paid",
  "created_at": "2026-03-28T14:30:00+01:00",
  "paid_at": "2026-03-28T14:32:00+01:00",
  "customer_name": "Kris",
  "customer_email": "kris@clickclickgraphics.com",
  "customer_phone": "...",
  "customer_company": "",
  "customer_address": "straat",
  "customer_zip": "8000",
  "customer_city": "Brugge",
  "vat_number": "",
  "payment_type": "bancontact",
  "delivery": "yes",
  "doc_type": "receipt",
  "ship_address": "...",
  "ship_zip": "...",
  "ship_city": "...",
  "files_checked": false,
  "printed": false,
  "shipped": false,
  "ready_pickup": false,
  "done": false
}
```

---

## Dashboard

Accessible as a 4th tab ("Dashboard") in the internal calculator (print.html). Three sub-tabs:

### Production Tab (default)

**Summary cards:**
- In Progress — active orders count
- Files to Check — orders where files_checked = false
- To Print — files checked but not yet printed
- To Ship — printed, delivery orders not yet shipped
- To Pick Up — printed, pick-up orders not yet marked ready

**Production table columns:**
- Date, Invoice, Customer, Order (clickable — opens detail modal), Deadline
- Checkboxes: Files, Printed, Shipped (delivery only), Pick-up (pick-up only), Done

**Features:**
- Repeat customer star: orange star next to customers with multiple orders in the same week
- Deadline colour coding: green (>2 days), orange (1–2 days), red (today/overdue)
- Lightning bolt icon for express orders
- Clicking "Done" moves the order to the archive
- Archive section: collapsible, shows completed orders (faded)
- Order detail modal: full customer info, specs breakdown, delivery info, deadline

**Deadline calculation from paid_at:**
- Express: +2 working days
- Coloured papers: +11 working days
- Standard CMYK: +5 working days

### Paid Orders Tab

**Summary cards:** Total Orders, Revenue (incl. VAT), Pending (unpaid), Avg. Order

**Orders table:** Date, Invoice, Customer, Amount, Delivery (badge), Status (badge)

**Pending section:** Shows orders with status != "paid" (abandoned checkouts)

### Browse Stats Tab

Tracks anonymous calculator usage vs. actual orders.

**Period filter:** 7 days, 30 days, 90 days, All time

**Summary cards:** Calculations, Orders, Conversion rate, Browse Only count

**Stacked bar charts** (blue = ordered, grey = browsed only):
- Interest by Product (Card, Poster, Colour) — with average price per product inline
- Interest by Size (A6, A5, A4, etc.)
- Interest by Quantity (250, 500, 1000, etc.)
- Price Range Interest (under 50, 50–100, 100–200, over 200)

**Daily calculation volume chart** (bar chart, adjusts to selected period)

### Browse Logging

`log-calc.php` receives anonymous POST requests from the customer calculator:
- Debounced (3 seconds) — fires when price changes
- Logs: tab, paper, weight, size, quantity, sides, versions, express, price
- Rate-limited: max 1 log per IP per 10 seconds
- Stored as JSONL in `orders/calc_log.jsonl`
- No personal data captured

### Dashboard API (api-dashboard.php)

- `GET ?action=orders` — returns all order JSON files as array
- `GET ?action=logs` — returns calc_log.jsonl entries as array
- `POST ?action=update` — updates production fields on an order (files_checked, printed, shipped, ready_pickup, done)

---

## Build & Deploy

### Local Development

```bash
cd clickclick-calculator
npm install
npm run dev        # Starts Vite dev server on port 5173
```

Access at:
- http://localhost:5173/calculator/customer.html
- http://localhost:5173/calculator/print.html

### Production Build

```bash
npm run build      # Outputs to dist/
```

### Server Deployment (Infomaniak)

Upload to `sites/clickclick.be/calculator/`:

```
calculator/
├── customer.html
├── customer.js
├── print.html
├── print.js
├── chunks/dateHelpers-*.js
├── print-calculator-values.xlsx
├── pay.php
├── webhook.php
├── thankyou.php
├── api-dashboard.php
├── log-calc.php
├── fpdf.php          (server-only, PDF generation)
├── invoice.php       (server-only, PDF template)
├── font/             (server-only, PDF fonts)
└── orders/           (server-only, created automatically)
```

The calculators are embedded in WordPress pages via iframes.

---

## Key Configuration (constants.js / Excel Settings)

| Setting | Default | Description |
|---------|---------|-------------|
| PREPRESS | 9.60 | Fixed prepress fee per order |
| SETUP_SHEETS | 6 | Extra sheets for machine setup |
| MACHINE_COST | 85 | Machine cost per hour |
| MACHINE_SELL | 120 | Machine sell per hour |
| LABOR_MIN | 15 | Labour minutes per order |
| PAPER_MARKUP | 0.50 | 50% markup on raw paper cost |
| MIN_MARGIN | 30 | Minimum margin %, surcharge applied below |
| CSF (colour switch fee) | 20 | Fee per paper variant change |
| VAT | 0.21 | Belgian VAT rate |
| EXPRESS_MARKUP | 35 | Express surcharge percentage |
| ROUND_STEP | 0.05 | Price rounding increment |
| ROUND_THRESHOLD | 1.00 | Minimum price for rounding |

### Number Formatting

Belgian format throughout: comma for decimals, apostrophe for thousands (e.g., 1'250,00).

### Margin Calculation

Margin is **cost-based** (profit / cost), NOT revenue-based. Example: cost 10, sell 15 = 50% margin.
