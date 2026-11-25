# Click Click Graphics — Print Calculator

## Project structure

```
clickclick-calculator/
├── customer.html                 ← HTML entry for customer calculator
├── print.html                    ← HTML entry for internal calculator
├── vite.config.js                ← Build config (two outputs)
├── package.json
├── public/
│   └── print-calculator-values.xlsx  ← PUT YOUR EXCEL FILE HERE
└── src/
    ├── main-customer.jsx         ← Entry point (customer)
    ├── main-print.jsx            ← Entry point (internal)
    ├── calculators/
    │   ├── CustomerCalculator.jsx   ← Public quote tool + payment buttons
    │   └── PrintCalculator.jsx      ← Internal tool with margin breakdown
    ├── components/
    │   └── PaperPicker.jsx          ← Reusable creative paper selector
    └── utils/
        ├── constants.js             ← All config, defaults, settings
        ├── pricing.js               ← Pricing engine (calcPrice)
        ├── excelLoader.js           ← Loads prices from Excel
        └── dateHelpers.js           ← Working days & date formatting
```

## Setup (first time only)

```bash
npm install
```

## Daily development

```bash
npm run dev
```
Opens at http://localhost:5173/customer.html (customer)
Opens at http://localhost:5173/print.html (internal)

## Build for production

```bash
npm run build
```
Output goes to `/dist/` — upload this folder to your server.

## Update prices

1. Edit `public/print-calculator-values.xlsx`
2. Re-upload only that file to your server
3. No rebuild needed — the calculator fetches it live

## Add a new product

1. Create a new file in `src/calculators/` e.g. `StickersCalculator.jsx`
2. Add a new entry point in `src/` e.g. `main-stickers.jsx`
3. Add a new HTML entry e.g. `stickers.html`
4. Register it in `vite.config.js` under `rollupOptions.input`
5. Run `npm run build`

## Configuration

Edit `src/utils/constants.js` to change:
- Brand colours
- Default paper data (fallback if Excel fails to load)
- Click pricing table
- Default settings (markup, VAT, express markup, etc.)

To change PayPal.me username or the WordPress payment URL,
edit the top of `src/calculators/CustomerCalculator.jsx`.

## Deploy to Infomaniak

Upload the contents of `/dist/` plus `print-calculator-values.xlsx`
to your `web/` folder on Infomaniak.
