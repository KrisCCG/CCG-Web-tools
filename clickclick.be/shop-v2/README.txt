SHOP v2 — the new online shop (TEST, 16 Oct 2026)
==================================================
Copy of the current shop (look, cart, file upload, EN/FR/NL), with:
  - products, sizes, papers, quantities and prices from the NEW price file
    (pricing/print-calculator-values_*.xlsx -> "Update prices")
  - the same price calculation as the internal calculator (calculator-v2)
  - the folder: new tile icon + fold choice with icons
    (folded in half / roll fold / zigzag fold) showing the folded size
  - a red TEST SHOP bar; online payment is switched off (api/config.php)
  - brochures (17 Oct 2026): A4 / A5 closed, 8-32 pages, cover 250 g of the same paper
    line as the inside paper, 25-150 copies, both sides, 1 version. VAT 21 % like every product (it was 6 % until
    21 Oct 2026; each item still carries its own VAT rate: calc.php "vatRate", pay.php "vat_rate").

The live shop (/shop/ + /calculator/) is NOT touched.

WHERE ON THE SERVER
  This whole folder  ->  /sites/clickclick.be/shop-v2/
  Address: https://clickclick.be/shop-v2/   (login: same as the calculator)

  Hidden dot-files that MUST be uploaded (FileZilla: Server > Force showing hidden files):
    .htaccess            login gate + stops WordPress
    api/.htaccess        blocks secrets/config/xlsx
    api/.user.ini        upload size limits (100 MB)
    api/data/.htaccess   blocks the price file (costs and margins) — never upload without it

HOW THE PRICES STAY CORRECT
  1. Change the Excel in pricing/, close Excel.
  2. Double-click "Update prices.command" in pricing/.
     It checks the Excel, calculates 7,000+ test prices and fills pricing/upload/.
  3. Upload the content of pricing/upload/ onto /sites/clickclick.be/ (overwrite).
     That updates the shop AND the internal calculator in one go.
  The shop recalculates all test prices with its own copy of the formula before it
  answers. If even one differs, it shows "prices temporarily unavailable" instead of
  a wrong price. The internal calculator shows a green or red bar for the same check.

FILES
  index.html, lib/i18n.js         the shop page and its texts (EN/FR/NL)
  lib/site-chrome/, fonts/, *.svg, logo.png, maintenance.html   copied from the live shop
  api/calc.php                    prices + product list (no costs ever leave the server)
  api/pay.php                     checks and prices the cart; payment OFF while testing
  api/upload.php, csrf.php, quote.php, cleanup.php   as in the live shop
  api/lib/engine.php              the price formula (same as engine.js / check_prices.py)
  api/lib/items.php               what may be ordered (from the Products tab)
  api/config.php                  payments_enabled => false
  api/data/                       price-data.xlsx + selftest.json (from Update prices)

MOLLIE TEST MODE (prepared 20 Oct 2026)
  Added to api/: webhook.php, thankyou.php, invoice.php, fpdf.php, font/ (receipt PDF fonts).
  1. Upload api/ (all of it, incl. font/ and the hidden .htaccess) and config.php.
  2. Create api/secrets.php ON THE SERVER ONLY (copy secrets.example.php): put a Mollie TEST
     key (test_...) in 'mollie_key', leave 'mollie_webhook_ips' out. Never copy the live key.
  3. Order in the shop, pay with Mollie's test page (choose "Paid"). Check: thank-you page with
     TEST banner, receipt mail with [TEST] subject and a PDF marked TEST, internal mail to shop@.
  Safety built in:
    - a live_ key is refused unless config.php has 'allow_live' => true (go-live step only)
    - test orders are numbered TEST-2026-001... (own counter, no clash with the live shop)
    - test payments are NEVER mailed to WinAuditor (the accountant)
    - test orders live in shop-v2/api/orders/, so the Dashboard (live orders) does not show them
  webhook.php has no login (api/.htaccess exempts it) because Mollie must reach it. It trusts
  nothing in the POST: it asks Mollie's API for the payment status with our key.

BROWSE STATS (21 Oct 2026)
  index.html sends the configuration being looked at to api/calc.php?log=1 (3 s after the last change,
  only after a click/keypress); calc.php writes one anonymous line to api/orders/calc_log.jsonl.
  api/api-dashboard.php serves orders + that log to the calculator-v2 Dashboard.

BACKUP / RETENTION / ERASURE (21 Oct 2026)
  api/backup.php, api/retention.php, api/admin/erase.php ported from the old /calculator/ (with fixes for
  cart orders). Setup, schedule and restore: BACKUP.md (local, do not upload).

MAILS (20 Oct 2026)
  - Customer mail + thank-you page follow the language chosen in the shop (NL / FR / EN, stored
    in the order as "lang"). The internal mail to shop@ and the PDF receipt stay English
    (the PDF already carries FR/NL alt-lines).
  - Mails landed in spam: PHP mail() from clickclick.be is not DKIM-signed and its sender domain
    fails SPF. Fix = secrets.php 'smtp_user' + 'smtp_pass' (mailbox shop@clickclickgraphics.com,
    app password): mails then go through Infomaniak's SMTP, signed. Also encoded subject + MIME.

BEFORE GOING LIVE (not done yet)
  - LAST STEP before the final upload (decided 21 Oct 2026): rate limit on api/calc.php so nobody can read all
    prices in bulk: about 60 requests per 10 min per IP (lib/ratelimit.php, key 'calc:'+ip, 429 + audit line when it trips)
    and ITEM_MAX_COUNT in lib/items.php from 10 to about 6. Test locally (loop of requests), then upload.
  - decide how the Dashboard reads orders of this shop (it reads /calculator/orders now)
  - set 'allow_live' => true and a live_ key in secrets.php, remove the TEST SHOP bar and the
    login (keep webhook reachable), then move to /shop/.
