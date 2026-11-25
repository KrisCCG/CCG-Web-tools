Click Click — Calculator deploy bundle (server-side)
====================================================

Upload to:   clickclick.be/calculator/

This bundle is STRICTLY ADDITIVE. It does not replace any of the existing
files under /calculator/ (pay.php, webhook.php, thankyou.php, etc.) —
only add these new ones:

  calc.php                                ← server-side pricing endpoint
  lib/xlsx_reader.php                     ← minimal xlsx parser
  data/.htaccess                          ← denies direct web access
  data/print-calculator-values.xlsx       ← master Excel (was in /flyers/)

Do NOT leave a copy of the xlsx anywhere web-accessible. Only PHP should
read it — the .htaccess in /calculator/data/ blocks direct downloads.

The endpoint:
  URL:      https://clickclick.be/calculator/calc.php
  Method:   GET or POST (JSON or form-encoded)
  Params:   product, paper_key, size, qty, sides, express, delivery
  Returns:  { ok, inclVat, exVat, perPieceIncl, perPieceEx, qty }

To update pricing: edit data/print-calculator-values.xlsx and re-upload it.
A cache file (data/.pricing_cache.json) is auto-refreshed on the next call.

Quick check after upload (paste in a browser):
  https://clickclick.be/calculator/calc.php?product=flyer&paper_key=ms130&size=A6&qty=250&sides=single&express=0&delivery=pickup

Should return JSON with the price (around €57).
