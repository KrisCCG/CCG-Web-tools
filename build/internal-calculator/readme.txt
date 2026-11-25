Click Click — Internal & Customer Calculator
=============================================

Last updated: 2026-05-06

WHAT THIS IS
------------
Two calculators that share one codebase:

  print.html      Internal calculator (shows margins, costs, profit).
                  Team-only, behind WordPress access.
  customer.html   Public calculator (VAT-incl prices, no margins).
                  Embedded on the WordPress site.

Built with React + Vite. Pricing constants live in an Excel file
(`print-calculator-values.xlsx`) loaded by the app at runtime.


WHERE FILES LIVE
----------------

LOCAL (development)
  /Users/clickclick/Desktop/Node-CCG/clickclick-calculator/
    src/                       React source
    public/                    static assets bundled into dist
      print-calculator-values.xlsx   master Excel
    dist/                      build output (created by `npm run build`)
    README.md                  developer docs (npm scripts, structure)
    DOCUMENTATION.md           pricing-engine & architecture deep-dive
    readme.txt                 this file (deploy + gotchas)

LIVE (server: Infomaniak, https://clickclick.be)
  /calculator/                 ← REAL APP LIVES HERE
    print.html                 internal entry
    customer.html              customer entry
    print.js                   internal bundle
    customer.js                customer bundle
    chunks/dateHelpers-XXX.js  shared chunk (filename has hash;
                               regenerated on every build, so
                               print.html / customer.html must
                               always upload together with chunks/)
    print-calculator-values.xlsx  live pricing data
    calc.php, pay.php, webhook.php, thankyou.php  payment + flyer endpoint

  /print-calculator/           ← WORDPRESS WRAPPER (NOT THE APP)
    A WordPress page that iframes /calculator/print.html.
    Do not look here for files; the iframe target is /calculator/.

  Important: when verifying a deploy, fetch
    https://clickclick.be/calculator/print.js
  NOT
    https://clickclick.be/print-calculator/print.js  (404)


HOW TO DEPLOY A CHANGE
----------------------

1. Edit source under src/.
2. Build:
     cd /Users/clickclick/Desktop/Node-CCG/clickclick-calculator
     npm run build
3. Upload these from dist/ to /calculator/ on the server:

   For an internal-calculator change:
     dist/print.html
     dist/print.js
     dist/chunks/dateHelpers-*.js     (whichever hash this build produced)

   For a customer-calculator change:
     dist/customer.html
     dist/customer.js
     dist/chunks/dateHelpers-*.js

   For a change touching shared utils (pricing.js, dateHelpers.js, etc.):
     upload everything in dist/ EXCEPT print-calculator-values.xlsx
     (see next section).

4. Hard-reload the browser (Cmd+Shift+R / Ctrl+Shift+R) to bypass cache.

5. Verify the new code is live:
     https://clickclick.be/calculator/print.js
   View source and look for a known string from your change. The bundle
   is minified, so search for distinctive literals or export names.


THE XLSX FILE
-------------
print-calculator-values.xlsx is the source of truth for prices. Two copies:

  Local:  public/print-calculator-values.xlsx
          (gets copied to dist/ by the build)
  Live:   /calculator/print-calculator-values.xlsx on the server

The live file is the one customers/staff hit. To update prices:
  1. Edit the LOCAL public/print-calculator-values.xlsx
  2. Upload ONLY that file to /calculator/ on the server
  3. No rebuild needed — the JS fetches it at runtime

WARNING: when uploading dist/, do NOT overwrite the live xlsx unless
you intentionally changed pricing. A blanket "sync dist/" can clobber
recent price edits made directly on the server.


COMMON PITFALLS
---------------

1. Wrong URL when verifying.
   /print-calculator/ is the WordPress page; /calculator/ is the app.

2. Stale browser cache.
   Always hard-reload after upload. Or open in private window.

3. Two `clickclick-calculator/` folders on the laptop.
   The Node-CCG repo can be checked out as the main tree AND as git
   worktrees under .claude/worktrees/. Each has its OWN `dist/` and
   `node_modules/`. Building in a worktree does not update the main
   checkout's dist. Always build in the location you intend to upload
   from.

4. The chunks/dateHelpers-XXX.js filename changes on every build.
   print.html and customer.html reference that exact filename. If you
   upload a new print.js but forget the new chunk file (or upload the
   chunk but forget the new HTML), the page breaks with a 404 in the
   network tab. Upload the trio together.

5. Inline components inside the calculator function cause input focus
   loss after every keystroke. (Bug reported & fixed 2026-05-06.) When
   editing PrintCalculator.jsx or CustomerCalculator.jsx, do NOT add new
   sub-components defined inside the parent function and rendered as
   <Subcomponent .../>. Each render creates a new component reference
   and React unmounts/remounts the subtree, killing focus on any input
   inside. If you need to factor JSX out, either:
     a) define the component at module scope (outside the parent), or
     b) call it as a function: {Subcomponent({ ...props })} instead of
        <Subcomponent .../>


QUICK SMOKE TEST AFTER A DEPLOY
-------------------------------
Open https://clickclick.be/calculator/print.html and:
  1. Type a multi-digit number into Quantity. All digits should land
     in one go (no focus loss).
  2. Switch between tabs (Flyer / Coloured Papers / Mini Poster).
  3. Confirm the SRA3 sheet count and price update as expected.

If the page looks blank, check the browser console: a 404 on print.js
or chunks/dateHelpers-*.js means the trio wasn't uploaded together.
