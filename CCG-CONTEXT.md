# Click Click Graphics — Project README
> This file exists to give Claude (Code or chat) full context about the CCG web project.  
> Keep it updated when files, prices, or logic change.

---

## 1. Business Overview

**Click Click Graphics SRL (CCG)**  
Print shop in Brussels running a Konica Minolta C12010s with white toner capability.  
Address: Aalststraat 7-11 Rue d'Alost, Brussel 1000 Bruxelles 
Website: clickclickgraphics.com / clickclick.be  
VAT: BE 0732.984.161  
IBAN: BE41 7360 6158 9610  
Email: kris@clickclickgraphics.com / shop@clickclickgraphics.com  
Phone: +32 2 880 70 36  
Signed by: "Julien and Kris"

**Hors Norme** — subsidiary brand for letterpress and laser printing (invoices in French).

---

## 2. Core Pricing Constants

| Parameter | Value |
|---|---|
| CMYK click cost (internal) | €0.035 |
| CMYK click cost (sell rate) | €0.135 |
| White toner cost | €600 / 13,000 impressions |
| Machine lease (monthly) | €1,347.50 at 10,000 impressions |
| Labor cost | €55/hr |
| Labor charged | €60/hr |
| Base print job time | 25 minutes |
| Material markup | 50% |
| Minimum margin floor | 30% (cost-based: profit/cost × 100) |
| VAT | 21% |
| Color switch fee | €20 |

> ⚠️ Margin is always **cost-based**: `profit / cost × 100` — NOT profit/sell price.  
> Minimum sell price floor: `cost × (1 + MIN_MARGIN / 100)`

---

## 3. File Structure

```
/calculator/
  customer.html              ← Customer-facing price calculator (VAT-inclusive, no margins shown)
  print.html                 ← Internal calculator (shows margins, cost breakdowns, working time)
  print-calculator-values.xlsx ← Shared live pricing data loaded by both calculators via SheetJS

/horsnorme/ (or root)
  calculator.html            ← Hors Norme customer-facing calculator + order form
  pay.php                    ← Creates Mollie payment and saves order JSON
  webhook.php                ← Triggered by Mollie on payment; generates PDF, sends emails
  thankyou.php               ← Post-payment confirmation page
  invoice.php                ← PDF generator using FPDF
  fpdf.php                   ← FPDF library
  orders/                    ← JSON order files + paid_orders.log + invoice counter
```

**WordPress:**
- Calculator iframes embedded at: https://clickclick.be/quote/ and /print-calculator/
- Direct files: https://clickclick.be/calculator/customer.html and /calculator/print.html
- Admin: https://clickclick.be/wp-admin/ (Twenty Twenty-Four theme, Full Site Editor)

**Hosting:** Infomaniak — file manager + FTP via FileZilla. WebDAV via Finder (⌘K).

---

## 4. Calculator Architecture

Both calculators are React + Vite, compiled to standalone HTML files (no separate JS chunks needed after build). They use Babel for JSX in standalone HTML mode.

**Live pricing via Excel:**  
Both load `print-calculator-values.xlsx` via SheetJS/fetch from the same directory.  
The Excel file has 6 sheets: `CMYK Papers`, `Creative Papers`, `Settings`, `Paper Types`, `Calculation Example`, `How It Works`.  
The `Settings` sheet exposes all constants as key/value pairs — prices can be updated without touching code.  
If the fetch fails, hardcoded fallback values are used. A footer indicator shows whether data came from Excel or defaults.

> ⚠️ The `.xlsx` file path must match the deployment subdirectory (e.g. `/calculator/print-calculator-values.xlsx`).

---

## 5. Paper & Product Catalog

### CMYK Papers
- MultiArt Silk: 115g, 130g, 170g, 250g, 300g, 350g, 400g
- RecyStar Nature: 90g, 115g, 130g, 170g, 250g, 300g
- Biotop: 120g, 160g, 250g, 300g

### Creative Papers (3×3 card picker UI)
- **Colorplan:** Forest Green, Claret/Bordeaux, Purple, Matcha Green, Sapphire Blue — all 350gsm
- **Fedrigoni:** Sirio Ultra Black 280gsm
- **CCG unlabelled stock:** Vermilion Red, Lavender, Navy Dark Blue — 300gsm

> ⚠️ Colorplan color state bug: the `color` state must ONLY be passed to the pricing function when `isColorplan` is true — otherwise it leaks into non-Colorplan calculations and inflates paper costs.

### Other Products
- **Brochures:** Saddle-stitch, BDF machine at €120/hr + €60/hr labour, 500 booklets/hr, 10 setup waste sheets. Total pages asked first (including cover). Cover = 4pp/1 sheet. Inside minimum 8pp in multiples of 4. Max 10 sheets inside if ≤130g, max 8 sheets otherwise. Print labour = 25 min base + 30 sec/booklet.
- **Posters / Large Format:** A4/A3 via flyer engine. A2–A0/custom via Canon Inkjet roll, tiered m² pricing + €14.60 fixed fees. Area = net finished size only (no bleed), rounded up to 0.01 m². Tier boundaries use exclusive upper bounds (exactly 1.00 m² → second tier). Single rate applies to entire area.
- **Hors Norme:** Letterpress/laser. Includes rétiration logic for formats < A5. Hot foil stamping.

---

## 6. Payment Flow (CCG Webshop)

```
customer.html / calculator
    ↓  (POST with order params)
pay.php
    → Creates Mollie payment
    → Saves order as /orders/YEAR-NNN.json
    → Redirects to Mollie checkout
    ↓
Mollie processes payment
    ↓
webhook.php  (called by Mollie on payment confirmation)
    → Fetches payment details from Mollie
    → Calculates amount_ex (÷ 1.21) and amount_inc
    → Generates PDF via invoice.php / FPDF
    → Sends email to customer (simplified: delivery + contact info only)
    → Sends PDF to WinAuditor (accounting import)
    → Sends internal notification to shop@clickclickgraphics.com
    → Logs to orders/paid_orders.log
    ↓
thankyou.php
    → Shows confirmation page
    → If webhook hasn't fired yet (test mode): polls Mollie directly and triggers webhook manually
```

**Invoice number format:** `YEAR-NNN` (e.g. 2026-023), counter stored in `orders/invoice_counter_YYYY.txt`

**Mollie API key:** starts with `test_` in development — switch to live key for production.

---

## 7. Email Setup

### Customer email (webhook.php → `build_email_html()`)
Sent via PHP `mail()` with PDF attached. Content:
- Hello [name]
- Thank you, receipt/invoice attached
- Delivery: Pick-up at shop OR delivery address
- "Any questions? We're happy to help at info@clickclickgraphics.com or +32 2 880 70 36."
- Many thanks, Julien and Kris
- Footer: CCG address + VAT

### Internal email (to shop@clickclickgraphics.com)
Full order details: customer, specs, amount, delivery. PDF attached.

### WinAuditor (accounting)
PDF forwarded to `Invoices+O8H-AE-M8D@winauditor.net` for automatic import.

### Hors Norme emails (EmailJS)
- Service ID: `service_lzmdfmf`
- Template ID: `template_order_horsnorme`
- Public key: `xG5D6Dyu_k-PxJZkx`
- Template variables: `to_email`, `to_name`, `invoice_num`, `items`, `total_ex`, `total_inc`, `shipping_address`, `phone`, `vat_number`, `notes`, `reply_to`

---

## 8. Hors Norme Specifics

- Invoices in **French**
- Brand palette (Tiegel): `#4200FF` purple, `#FFF5EF` cream, `#1a0066` deep purple
- kDrive upload link: https://kdrive.infomaniak.com/app/collaborate/1775931/59e2e00c-83d5-4087-9ff6-9e871391b9cc
- EmailJS handles order confirmation emails (not PHP mail)

---

## 9. Number Formatting

Belgian format throughout:
- Decimal separator: **comma** (e.g. €49,59)
- Thousands separator: **apostrophe** (e.g. €1'347,50)

---

## 10. Key Rules & Known Bugs to Remember

1. **Margin is cost-based:** `profit / cost × 100` — never divide by sell price
2. **Colorplan color leak:** only pass `color` to pricing function when `isColorplan === true`
3. **Large format area:** use net finished size, no bleed, round up to 0.01 m², tier boundaries are exclusive upper bounds
4. **Brochure pages:** cover is always 4pp, inside min 8pp in multiples of 4
5. **Excel path:** must match deployment subdirectory exactly
6. **Vite chunk filenames change on every build** — verify by fetching compiled JS and scanning for chunk patterns before diagnosing path issues
7. **LLMs are unreliable for multi-step arithmetic** — all calculation logic must be deterministic JavaScript, never delegated to AI

---

## 11. Tools & Integrations

| Tool | Purpose |
|---|---|
| Infomaniak hosting | Web server, file manager, FTP |
| FileZilla | FTP uploads |
| Mollie | Online payments (Bancontact + other methods) |
| EmailJS | Hors Norme order emails |
| FPDF (PHP) | PDF invoice/receipt generation |
| SheetJS | Load Excel pricing data in browser |
| WinAuditor | Accounting — receives invoice PDFs by email |
| Papyrus | Paper supplier — weekly CSV for price updates |
| ccg-price-updater.html | Internal tool to process Papyrus CSV |
| WordPress | Twenty Twenty-Four theme, Full Site Editor, iframes for calculators |
| React + Vite | Frontend stack for calculators |
| Babel | JSX in standalone HTML tools |

---

*Last updated: March 2026*
