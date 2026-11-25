# Click Click Graphics — Flyer Calculator

Customer-facing flyer / card / poster order flow for **clickclick.be/flyers/**, plus the shared payment + artwork-upload backend at **clickclick.be/calculator/**.

The frontend takes specs (size, paper, qty, sides, versions, delivery, customer details) and an artwork file, hits a Mollie checkout, then drops the order JSON + uploaded files into a per-invoice folder on the server.

---

## Architecture

```
clickclick.be/
├─ flyers/            ← customer-facing UI (this project)
│   ├─ index.html         single-page app, vanilla JS modules
│   ├─ lib/i18n.js        EN / FR / NL strings
│   ├─ caddy.svg          shopping-cart icon
│   ├─ bespoke-icon.svg   envelope icon for "need something bespoke?" link
│   ├─ logo.png
│   └─ .htaccess + .htpasswd  basic-auth gate while testing
│
├─ calculator/        ← shared backend (this project + the legacy React calculator)
│   ├─ calc.php           live price endpoint, reads pricing-data.xlsx
│   ├─ pay.php            creates a Mollie payment, persists order JSON
│   ├─ webhook.php        Mollie callback → updates status, moves artwork, emails
│   ├─ thankyou.php       post-payment confirmation page
│   ├─ upload.php         multipart endpoint for staged artwork uploads
│   ├─ csrf.php           one-shot CSRF token issuer (form fetches on load)
│   ├─ cleanup.php        sweeps abandoned /orders/temp/ folders (>24 h)
│   ├─ backup.php         daily GPG-encrypted backup → Infomaniak kDrive
│   ├─ retention.php      weekly PII redaction + artwork purge sweeps
│   ├─ secrets.php        Mollie key, GPG passphrase, kDrive creds (gitignored)
│   ├─ secrets.example.php template documenting secrets.php shape
│   ├─ admin/
│   │   ├─ erase.php      GDPR right-to-erasure endpoint (basic-auth)
│   │   └─ .htaccess      basic-auth gate (reuses flyers/.htpasswd)
│   ├─ data/
│   │   ├─ print-calculator-values.xlsx  ← single source of truth for prices
│   │   └─ .htaccess                       blocks direct HTTP access
│   ├─ lib/
│   │   ├─ xlsx_reader.php                minimal pure-PHP xlsx parser
│   │   ├─ audit.php                      append-only JSON audit logger (PII redacted)
│   │   └─ ratelimit.php                  sliding-window flat-file IP limiter
│   ├─ orders/                            (server-only — gitignored)
│   │   ├─ {YYYY}-{NNN}.json              per-order metadata
│   │   ├─ {YYYY}-{NNN}/artwork/*.pdf     uploaded artwork
│   │   ├─ temp/{upload_id}/*.pdf         staging area before payment
│   │   ├─ invoice_counter_{YYYY}.txt
│   │   ├─ paid_orders.log
│   │   ├─ audit.log                      every payment/upload/webhook event
│   │   ├─ backup.log                     nightly backup run results
│   │   ├─ retention.log                  weekly retention sweep results
│   │   ├─ .csrf-tokens.json              live CSRF tokens (1h TTL)
│   │   └─ .ratelimit/                    per-IP counters (transient)
│   ├─ BACKUP.md                          restore runbook (read this!)
│   ├─ INCIDENT.md                        incident-response runbook
│   ├─ .htaccess                          PHP upload limits + security headers
│   └─ .user.ini                          PHP upload limits (PHP-FPM form)
│
└─ print-calculator/  ← legacy React calculator (different repo, same /calculator/ backend)
```

`/calculator/` is the **shared backend** — both the flyer calculator and the older React calculator POST to the same `pay.php`, share invoice numbering, and write into the same `/orders/` folder.

---

## Pricing pipeline

All prices flow from one xlsx file:

```
flyers/index.html
  └─ POST /calculator/calc.php?product=…&paper_key=…&size=…&qty=…&versions=…&sides=…
        └─ calc.php loads /calculator/data/print-calculator-values.xlsx
              ├─ Settings sheet           (markup, prepress, machine speed, VAT, rounding…)
              ├─ CMYK Papers sheet        (raw cost / mother sheet, cuts per mother)
              ├─ Click Tiers sheet        (volume-tiered click sell rates, A4-)
              ├─ Click Tiers XXL sheet    (same, A3)
              └─ Calculation Example      (in-Excel mini-calculator — for reference)
        └─ Returns { ok, inclVat, exVat, perPieceIncl, qty, versions, totalPieces }
```

The xlsx is **the only place to edit prices.** PHP reads it on every request (cached against its mtime), so uploading a new xlsx is the entire deploy for a price change.

Calculation steps in `calc.php::computePrice()`:

1. `print_sheets = CEILING(qty / pieces_per_sra3)`
2. `total_sra3 = (print_sheets + 6 setup) × versions`
3. `paper_cost = total_sra3 × (raw_cost / cuts_per_mother)` → ×1.5 markup → `paper_sell`
4. `click_sell = total_sra3 × volume_tier_rate` (× 2 if double-sided)
5. `click_cost = total_sra3 × 0.035` (CMYK ink cost)
6. `machine_hours = total_sra3 / 4140 + labor_min/60`
7. `total_cost = paper_cost + click_cost + machine_cost`
8. `total_sell = paper_sell + click_sell + machine_sell + €9.60 prepress`
9. If margin < 30 % floor: `sell = cost / 0.70`
10. Express? `sell × 1.35`
11. `× 1.21` VAT, then round to nearest €1 (≤ €0.50 above → down, else up)
12. Per-piece divides by `qty × versions`

---

## Order flow (end to end)

```
┌─ Customer fills the form ─────────────────────────────────────────────┐
│  product · size · paper · qty · versions · R/V · express · delivery   │
│  customer details · drops 1+ artwork files onto the upload zone       │
│                                                                       │
│  → upload.php receives each file with a client-minted upload_id (UUID │
│    v4, same id for all files in this order). Files staged in          │
│    /orders/temp/{upload_id}/                                          │
│                                                                       │
│  → "Pay €X,XX" button enables once isFormValid()                      │
└─────────────────────────────┬─────────────────────────────────────────┘
                              ↓
┌─ pay.php ─────────────────────────────────────────────────────────────┐
│  Generates invoice {YYYY}-{NNN}                                       │
│  Validates upload_id, scans temp folder → artwork_filenames[]         │
│  Saves /orders/{invoice}.json (status=pending)                        │
│  Creates Mollie payment, redirects to checkout                        │
└─────────────────────────────┬─────────────────────────────────────────┘
                              ↓
┌─ Mollie checkout ─────────────────────────────────────────────────────┐
│  Customer pays. Mollie posts the webhook AND redirects to             │
│  thankyou.php                                                         │
└─────────────────────────────┬─────────────────────────────────────────┘
                              ↓
┌─ webhook.php ─────────────────────────────────────────────────────────┐
│  Confirms payment with Mollie API                                     │
│  finalize_artwork(): moves every file from                            │
│  /orders/temp/{upload_id}/ into /orders/{invoice}/artwork/            │
│  Generates receipt/invoice PDF (fpdf), emails customer + WinAuditor + │
│  internal notification, appends to paid_orders.log                    │
└─────────────────────────────┬─────────────────────────────────────────┘
                              ↓
┌─ thankyou.php ────────────────────────────────────────────────────────┐
│  Reads order JSON, displays "✅ Your files are received: …"           │
│  (falls back to a curl-poke at the webhook if Mollie's webhook is     │
│  slower than the redirect)                                            │
└───────────────────────────────────────────────────────────────────────┘
```

---

## Deploy

Files live in two folders on Infomaniak shared hosting (PHP-FPM):

| Local                        | Server                         |
| ---------------------------- | ------------------------------ |
| `flyers/`                    | `/sites/clickclick.be/flyers/` |
| `calculator/`                | `/sites/clickclick.be/calculator/` |

Deploy = **SFTP upload via VS Code** (extension: SFTP by Natizyskunk).

Right-click a folder in the VS Code Explorer → **SFTP: Upload**. The extension mirrors the local tree under the configured `remotePath` (set in `.vscode/sftp.json` — gitignored, holds the SFTP password).

After uploading a new xlsx, `calc.php` rebuilds its cache automatically on the next request (it checks `filemtime`). After uploading code changes, hard-refresh the browser (Cmd+Shift+R) to bust HTML/JS cache.

---

## Common tasks

### Change prices

Edit `calculator/data/print-calculator-values.xlsx` locally → upload that single file to `/calculator/data/`. The "Calculation Example" tab in the xlsx is a working mini-calculator you can use to preview a price for any spec without touching the live site.

### Add a new paper

1. Open the xlsx, **CMYK Papers** sheet, append a row: `paper_type, gsm, cuts_per_mother, raw_cost`.
2. In `flyers/index.html`, find the `PRODUCTS` JS object and add the paper to the relevant product's `papers` array using the same `paper_key` shape (`ms170`, `bt250`, etc. — first two letters = type, rest = gsm).
3. Upload both files.

### Change UI copy or translations

Edit `flyers/lib/i18n.js`. The file is split into `en`, `fr`, `nl` blocks; each key (`hero.title`, `step3.titleEm`, `upload.error.tooMany`, …) needs all three. Upload that one file.

### Cron schedule (all configured via Infomaniak admin panel)

| When | Command | What it does |
|---|---|---|
| `0 3 * * *` | `php …/calculator/cleanup.php` | Sweep abandoned `/orders/temp/` folders > 24h |
| `0 4 * * *` | `php …/calculator/backup.php` | GPG-encrypt `/orders/`, upload to kDrive, prune > 30 days |
| `0 5 * * 0` | `php …/calculator/retention.php` | Weekly PII redaction + 6-month artwork purge |

Substitute the path prefix:
`/home/clients/<id>/sites/clickclick.be/calculator/`

Cleanup also runs probabilistically (~1 in 100 uploads) as a safety net.

### Deploying `secrets.php`

`calculator/secrets.php` holds the Mollie API key, GPG passphrase, and kDrive credentials. It's **gitignored** — fresh deploys need it uploaded manually once via SFTP. Copy `calculator/secrets.example.php` → `secrets.php` and fill in real values. See the comments in that file for how to generate the passphrase and where to mint a kDrive app token.

If `secrets.php` is missing or incomplete, `pay.php` / `webhook.php` / `thankyou.php` return a 500 with no leakage, and the customer sees "Service unavailable."

### Backups & incidents

- **`calculator/BACKUP.md`** — restore procedure, manual run, what's in the archive.
- **`calculator/INCIDENT.md`** — what to do if a breach is suspected, orders are lost, the webhook fails, or a customer asks for data deletion.

### Roll back to a known-good state

```
git checkout v1.0-pre-upload-feature -- flyers calculator
```

then re-upload those folders.

---

## WordPress embedding

The customer-facing URLs run on WordPress, but WP's nav + footer would interfere with a full-screen calculator UI. Each WP page is just an iframe pointing at the real app on `/calculator/` or `/flyers/`, with a small inline `<style>` block that hides WP's content padding and the post title.

The block currently used at `clickclick.be/print-calculator/` (which iframes the legacy React calculator):

```html
<style>
  .wp-block-post-title, h1.wp-block-post-title { display: none; }
  .entry-content, .wp-block-post-content, .wp-block-group, .is-layout-flow {
    padding: 0 !important; margin: 0 !important; max-width: 100% !important;
  }
  main.wp-block-group { padding-top: 0 !important; margin-top: 0 !important; }
  .wp-site-blocks { padding-top: 0 !important; }
</style>

<iframe
  src="https://clickclick.be/calculator/print.html"
  style="width:100vw; height:100vh; border:none; display:block; margin-left:calc(-50vw + 50%); position:relative;"
></iframe>
```

Equivalent block for a WP page that hosts the flyer calculator (e.g. `clickclick.be/flyer-quote/`):

```html
<style>
  .wp-block-post-title, h1.wp-block-post-title { display: none; }
  .entry-content, .wp-block-post-content, .wp-block-group, .is-layout-flow {
    padding: 0 !important; margin: 0 !important; max-width: 100% !important;
  }
  main.wp-block-group { padding-top: 0 !important; margin-top: 0 !important; }
  .wp-site-blocks { padding-top: 0 !important; }
</style>

<iframe
  src="https://clickclick.be/flyers/"
  style="width:100vw; height:100vh; border:none; display:block; margin-left:calc(-50vw + 50%); position:relative;"
></iframe>
```

Two gotchas to watch for if you publish the flyer iframe to a public WP page:

1. **Basic auth on `/flyers/`** — `flyers/.htpasswd` gates the URL during testing. A basic-auth dialog inside an iframe is awkward (some browsers block it, others prompt). Remove `flyers/.htaccess` + `.htpasswd` from the server (not from git) when going live.
2. **Mobile viewport height** — `100vh` inside an iframe can clip the bottom of the page on iOS Safari (the address bar eats some viewport). If you see the Pay button cut off, swap to a fixed value like `min-height: 1400px` or have the iframe message the parent with its content height.

---

## Tech stack

- **Frontend** — vanilla HTML / CSS / JS modules, single-file architecture (`flyers/index.html`). No bundler. Loads `lib/i18n.js` only.
- **Backend** — PHP 8 (Infomaniak PHP-FPM). No framework, no Composer dependencies, no database. Just `.php` files and a flat-file `/orders/` directory.
- **Pricing data** — Excel xlsx, parsed at request time via a custom 158-line `xlsx_reader.php` (ZipArchive + SimpleXMLElement, no PhpSpreadsheet/etc.).
- **Payments** — Mollie (test key currently in `pay.php` / `webhook.php` / `thankyou.php`; flip to `live_*` when ready).
- **PDF generation** — fpdf via `invoice.php` (loaded conditionally if present).
- **Email** — `mail()` (PHP built-in, uses Infomaniak's SMTP).

---

## Repo layout

```
Clickclick-flyer-calculator/   ← this repo
├─ README.md                   ← you are here
├─ .gitignore
├─ flyers/                     deploy as-is to /flyers/
├─ calculator/                 deploy as-is to /calculator/
├─ Archive/                    old fast-quote / quote-calculator (not used)
└─ claude-flyer-calculator.md  pre-existing project notes
```

GitHub: <https://github.com/KrisCCG/Clickclick-flyer-calculator>

Tags worth knowing:

- `v1.0-pre-upload-feature` — last commit before the artwork-upload flow replaced the Kdrive link.
