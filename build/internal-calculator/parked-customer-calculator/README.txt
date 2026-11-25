PARKED — customer-facing calculator (kept for the coloured papers)
=================================================================
Taken out of the build on 20 Aug 2026. Kept because it is the only UI that
handles the Coloured Papers range.

This copy is the CORRECTED one — it already carries both of today's fixes:
  · custom sizes impose on the printable area (300x430), not the full SRA3
    sheet, so they are no longer under-priced by up to 23%
  · "g/m2" -> "gsm", because GT Pressura has no superscript-two glyph

The copy in Node-CCG/Archive/clickclick-calculator/ does NOT have these. Use
this one if it is ever revived.

TO REVIVE
  1. Copy src/* back over ../src/ and customer.html back to ../
  2. Add   customer: 'customer.html'   to input: {} in ../vite.config.js
  3. npm run build

DEPENDS ON (still present in the live project, do not delete):
  src/components/PaperPicker.jsx      shared with the internal calculator
  src/utils/translations.js           also used by utils/dateHelpers.js
  src/utils/pricing.js, constants.js, excelLoader.js, dateHelpers.js
