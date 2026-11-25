# Internal print calculator — new home (19 Aug 2026)

Moved out of `Node-CCG/Archive/clickclick-calculator/`, which was misleading:
this is the live source for `clickclick.be/print-calculator/`, not an archive.
The old folder is left untouched as a fallback.

## What this is

A Vite + React app. `print-calculator/index.html` in the flyer-calculator repo
is only a red "internal use only" wrapper that iframes `/calculator/print.html`
— the file built from here.

    npm install
    npm run build        # -> dist/print.html, dist/print.js, dist/chunks/…

Upload `dist/` contents to `/calculator/` on the server.

## Files that matter

    src/utils/constants.js          POSTER_SIZES, FLYER_SIZES, and this app's
                                    OWN Konica click tiers
    src/utils/pricing.js            the whole pricing implementation, in JS
    src/calculators/PrintCalculator.jsx   the internal UI
    src/calculators/CustomerCalculator.jsx  an older customer-facing calculator
    public/print-calculator-values.xlsx     this app's OWN copy of the values
    api-dashboard.php, log-calc.php  live at /calculator/, sourced only here

## Deliberately NOT copied from the archive

    node_modules/                   regenerate with npm install
    pay.php, webhook.php,           MARCH versions, superseded by the much
    thankyou.php                    larger ones in Clickclick-flyer-calculator/
                                    calculator/. Two payment implementations in
                                    two live folders would be dangerous — the
                                    flyer-calculator copies are authoritative.
    "print-calculator-values old.xlsx"   explicitly old

## Fonts — no third-party calls

`print.html` used to pull Space Grotesk from Google Fonts, which sent every
staff member's IP to Google on each load. Replaced 19 Aug 2026 with GT Pressura
self-hosted from `public/fonts/`. The built output now contacts nothing outside
clickclick.be — verified against `dist/`.

Two knock-on fixes that came with it, both worth keeping:
  · GT Pressura has no U+00B2, and `<sup>` is illegal inside `<option>`, so the
    app says "130 gsm" rather than "130 g/m²" — the same wording the shop uses.
  · GT Pressura is a wider face than Space Grotesk, so the fixed 100px weight
    column clipped and is now 124px. Watch for the same in other fixed widths.

## Pricing — what IS and IS NOT shared with the shop

`src/utils/constants.js` sets:

    export const XLSX_FILE = '/calculator/data/print-calculator-values.xlsx'

That is an ABSOLUTE path to the same spreadsheet `calculator/calc.php` reads, so
the shop, the chatbot and this tool all price from ONE file. `applySettings()`
overwrites the JS defaults from it at load, so the values in `SETTINGS` are only
fallbacks for when the fetch fails.

Since 19 Aug 2026 `machine_speed` is among the settings it reads — it used to be
a hard-coded 4140 in `pricing.js`, so a speed change in the spreadsheet reached
the shop but silently not this tool. The "Machine (incl. N min labor)" label was
likewise hard-coded at 25 while the maths used the real value; it now prints
`bd.setupMinutes`.

STILL NOT SHARED — the one real divergence left:

  * Click tiers. This app keeps its own in `src/utils/constants.js`; the shop
    reads the `Click Tiers` / `Click Tiers XXL` sheets. `excelLoader.js` reads
    only CMYK Papers, Coloured Papers, Settings and Paper Types, so a change to
    the tier sheets does NOT reach this tool.
  * (The tall poster WAS missing here; added 20 Aug 2026 — see below.)

## Tall poster (300x600, `LP`) — added 20 Aug 2026

Implemented in JS to mirror `calculator/calc.php` exactly. The four rules that
make it price correctly, and where they live:

    constants.js   PPS.LP = 1, LONG_SIZES, LONG_SIZE_PAPERS (MultiArt only),
                   MACHINE_SPEED_LONG + LONG_SIZE_UPLIFT read from the xlsx
    pricing.js     isLong -> whole mother sheet for paper (no /pd.s),
                   XXL click tier, banner speed, uplift after the margin floor
    PrintCalculator.jsx
                   size option, non-MultiArt stocks greyed out, and an effect
                   that snaps an illegal paper back to MultiArt

Verified against calc.php on six cases (LP at 130/170/115 gsm and 100/250/500,
plus A3 and A4 as controls) — identical to the cent.

The custom-size inputs are still clamped to 320x450 in `PrintCalculator.jsx`.
That is deliberate: `custom` prices off `calcCustomPPS`, which assumes an SRA3,
so it must not be used to fake a banner sheet. Use the `LP` size instead.

`public/print-calculator-values.xlsx` was DELETED on 19 Aug 2026. It shipped in
every build but was never fetched — the absolute path above always won — and its
stale contents made the two calculators look like they disagreed when they do
not. Do not reintroduce it.


## Printable area vs sheet size — fixed 20 Aug 2026

The press loses 10 mm on every side, so an SRA3 sheet (320x450) has a usable
imposition area of 300x430. `constants.js` now carries PRINT_MARGIN,
PRINTABLE_W and PRINTABLE_H.

The fixed PPS map was always built against the printable area — A7 gives 16 per
sheet on 300x430 but 18 on the full sheet, and the map says 16; A6 gives 8
against 9, and the map says 8. So named sizes were correct.

`calcCustomPPS` was NOT: it imposed on the full 320x450 and therefore claimed
pieces fitted that do not. 38% of the custom size range was affected, the worst
cases by a factor of two (105x155 was told 8 fit per sheet when 4 do), which
under-counts sheets and under-prices paper AND clicks. Real examples, 130 gsm
MultiArt:

    105x155 x500    EUR 65 -> 80   (+23%)
    105x305 x250    EUR 65 -> 80   (+23%)
    100x150 x1000   EUR 77 -> 80   (+4%)
    150x210 x500    EUR 80 -> 80   (unchanged — it never straddled the margin)

Named sizes and the tall poster are unchanged, verified. The shop is unaffected:
calc.php has no custom-size path at all, only the fixed PPS map.

The custom inputs now clamp to 300x430 rather than 320x450, in BOTH calculators.
Anything larger is a banner job and belongs to the LP size.
