PRICES — how it works (16 Oct 2026)
===================================
print-calculator-values_YYYY-MM-DD.xlsx   the ONE place where prices, papers and products live.

To change prices:
  1. Edit the Excel, save, close Excel.
  2. Double-click "Update prices.command".
     - checks every tab (errors stop everything, warnings are shown)
     - calculates the test jobs and opens a report (reports/)
     - writes 7,000+ test prices (selftest.json)
     - fills the folder upload/ with exactly what the server needs:
         upload/shop/api/data/    price-data.xlsx + selftest.json
       ONE price file on the server since 25 Oct 2026: the shop, the chat AND the internal
       calculator (calculator-v2, through the shop's login-protected dashboard API) all use it.
     - also copies them into the local shop folder.
  3. FileZilla: drag the content of upload/ onto /sites/clickclick.be/ (overwrite).

The shop and the internal calculator check themselves against selftest.json.
If anything differs, they refuse to show prices instead of showing a wrong one.

"Check prices.command" does the same thing (old name, kept so nothing breaks).
check_prices.py holds the master price formula. engine.js (internal calculator) and
api/lib/engine.php (shop) are copies; selftest.json proves they are identical.

FLOOR PRICE (since 22 Oct 2026)
  Products tab, four optional columns: "floor price from / to paper weight (g/m2)", "floor price margin",
  "floor price min job price (EUR)". Where the paper weight is inside the range, the product is sold at
  cost + margin: no general minimum margin, no online discount, and its own minimum job price (0 = none).
  Empty = the product keeps the general rules (30 % minimum margin, EUR 50, online discount).
  Today only flyers: 80 to 170 g/m2, margin 25 %, minimum job price 0. The formula lives in check_prices.py,
  engine.js and engine.php; selftest.json (8,760 prices) proves they agree.
