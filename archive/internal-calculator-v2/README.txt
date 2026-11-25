PRINT CALCULATOR v2 — internal (16 Oct 2026)
=============================================
A new internal calculator that reads the NEW price file (price-data.xlsx,
made from pricing/print-calculator-values_YYYY-MM-DD.xlsx). English only.

It does NOT replace anything. The old calculator stays where it is:
  old:  clickclick.be/print-calculator/  ->  /calculator/print.html  (old Excel)
  new:  clickclick.be/calculator-v2/                                 (new Excel)
so both can be opened side by side to compare.

FILES (upload the whole folder content to  /calculator-v2/  on the server)
  index.html           the calculator (no build step, no npm)
  engine.js            the price formula — a copy of pricing/check_prices.py
  xlsx.full.min.js     SheetJS 0.18.5, reads the xlsx in the browser
                       (copied from build/internal-calculator/node_modules)
  fonts/               GT Pressura, self-hosted (same as the old calculator)
  price-data.xlsx      the price file
  selftest.json        test prices from the update script (checked on every load)
  .htaccess            DOT-FILE. Same password gate as /print-calculator/
                       (copied from clickclick.be/shop/print-calculator/).
                       The page shows cost prices and margins: never upload
                       the folder without it.

UPDATING PRICES (shop-v2 and this calculator together)
  1. Change the Excel in pricing/, close Excel.
  2. Double-click "Update prices.command" in pricing/.
  3. Upload the content of pricing/upload/ onto /sites/clickclick.be/ (overwrite).
  selftest.json holds 7,000+ prices from the update script. This page recalculates
  them on load: green bar = identical, red bar = do not use these prices.

TRY IT ON THE MAC (without uploading)
  Open index.html in a browser. The browser will not load price-data.xlsx
  from disk by itself: click "Load another xlsx" and pick the file.
  "Load another xlsx" also works online, to test a new Excel before uploading
  it — the file stays in your browser, nothing is sent anywhere.

WHAT IT SHOWS
  - Online price incl./excl. VAT, cost, margin.
  - Every line (paper, clicks, press, folding, final cut, pre-cut, file check)
    with how it is calculated, cost and sell.
  - Print: 4/4 (standard), 4/0, 1/1 black, 1/1 white (white = coloured papers only).
  - Quantity table from 25 copies up, with cost, profit (€) and margin.
  - Tab "Test jobs": the test jobs of the Excel with expected vs calculated.
  - "Show every paper": also papers that are not in the shop list.
  - Brochures (saddle stitched, not in the shop yet): closed size, pages, cover paper and
    inside paper; lines per print run (cover / inside), creasing the cover and stitching
    (by hand, or the tower above 75 copies AND 20 pages). VAT 21 % (like every product), prepress €30.

DASHBOARD TAB (added 20 Oct 2026, both shops 21 Oct)
  Same three views as the old calculator: Production (checkboxes, deadlines, order popup,
  archive), Paid orders, Browse stats. It reads the order API of BOTH shops, same server, same login:
    live shop   /calculator/api-dashboard.php          (orders of /flyers/)
    shop v2     /shop-v2/api/api-dashboard.php         (orders of /shop-v2/, TEST-... numbers)
  Filter: the shop (one source since 22 Oct 2026; the old shop is archived), and "Show test orders". Test orders get a TEST tag and
  are left out of revenue, average order and conversion. A tick goes back to the shop that owns
  the order. If one shop's API fails, the other still shows (red line at the top).
  Browse stats: the shop logs one anonymous line per quote the visitor looks at (product,
  size, paper, quantity, price; no name/IP/e-mail) into /shop-v2/api/orders/calc_log.jsonl: only after
  the visitor clicked something, 3 s after the last change, max 1 line per IP per 10 s, file stops at
  5 MB. The live shop's log stopped in May 2026 (old customer calculator retired).
  Table headers (Production + Paid orders) are sortable: click = sort, click again = reverse.
  Upload: index.html -> /calculator-v2/ ; api-dashboard.php + calc.php -> /shop-v2/api/ (api/.htaccess
  already gates it with the "Click Click - dashboard" login).
  Deadline = the order's own production_deadline (holidays excluded) when it has one.

CHECKED
  engine.js gives exactly the same price as check_prices.py and the shop's engine.php
  on 132,192 combinations (every product, size, paper, fold, pages, cover paper,
  quantities, 1 or 2 sides, CMYK / black / white, 1 or 3 versions, express, delivery)
  — 0 differences (17 Oct 2026, with brochures).
  If the formula in check_prices.py changes, engine.js must change too.
