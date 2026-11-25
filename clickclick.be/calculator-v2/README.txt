CALCULATOR-V2 — internal calculator + Dashboard (master copy, 25 Oct 2026)
=========================================================================
This folder = /sites/clickclick.be/calculator-v2/ on the server. Staff only (login "Click Click — dashboard").

  index.html         calculator + Dashboard (no build step). Newest: download button for customer files
                     and prices read from the shop (25 Oct 2026).
  engine.js          the price formula — a copy of pricing/check_prices.py (selftest proves they agree)
  xlsx.full.min.js   SheetJS 0.18.5, reads the price file in the browser
  fonts/             GT Pressura, self-hosted
  .htaccess          DOT-FILE: the staff login. Never upload the folder without it.

NO price file here any more (25 Oct 2026). The calculator reads the shop's price file
(/shop/api/data/price-data.xlsx + selftest.json) through /shop/api/api-dashboard.php.
Price updates: pricing/ > "Update prices.command" > upload pricing/upload/shop/ only.

The Dashboard reads orders and customer files from the same /shop/api/api-dashboard.php
(master copy: clickclick.be/shop-v2/api/api-dashboard.php).
