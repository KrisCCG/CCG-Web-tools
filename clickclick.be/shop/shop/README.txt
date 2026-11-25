Click Click — Fast Quote deploy bundle (front-end)
==================================================

Upload the CONTENTS of this folder to:

   clickclick.be/flyers/

Target URL: https://clickclick.be/flyers/

Files:
  .htaccess     — stops WP from intercepting, sets MIME types
  index.html    — the quote page
  logo.png      — header logo
  lib/i18n.js   — EN/FR/NL translations

Prices are fetched from https://clickclick.be/calculator/calc.php — that
endpoint must be deployed too (see ../calculator/ bundle).

To edit pricing: edit the Excel at /calculator/data/print-calculator-values.xlsx
(NOT in this folder). The xlsx is no longer shipped with the front-end so the
raw costs/margins stay private.
