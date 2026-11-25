# clickclick.be — what is there, and what is not relevant any more

> **Oct 2026:** the shop moved from `/flyers/` to `/shop/` (local folder `shop/shop/`). `/flyers/` now only holds an `.htaccess` that 301-redirects to `/shop/`, query string included, so printed QR codes and old links keep working.


Written 20 Aug 2026. Covers the web root of clickclick.be. Marked clearly where
this is inferred from the local repos rather than seen on the server.

## Live and current

| path | what it is | source | gate |
|---|---|---|---|
| `/flyers/` | **the shop** — the customer route. Flyers, cards, posters, Mollie checkout, artwork upload | `Clickclick-shop/flyers/` | password (Mollie still in test mode) |
| `/calculator/` | **pricing + payment backend** — `calc.php`, `pay.php`, `webhook.php`, `upload.php`, `invoice.php`, `quote.php`, `thankyou.php`, plus `data/` (the pricing xlsx) and `orders/` | `Clickclick-shop/calculator/` | mixed: public endpoints open, admin/cron/`print.html` gated |
| `/calculator/print.html` | the **internal calculator** UI (built React) | `internal-print-calculator/` | password |
| `/print-calculator/` | red "internal use only" wrapper that iframes the above | `Clickclick-shop/print-calculator/` | password |
| `/legal/` | legal notice, privacy, terms — EN/FR/NL | `Clickclick-shop/legal/` | **public on purpose** |
| `/papers/` | the paper guide — EN/FR/NL | `Clickclick-shop/papers/` | **public on purpose** |
| `/chat/` + `/chat-api/` | the AI quote assistant. Prices through `calc.php` | `clickclick-chat/` | password (closed 19 Aug 2026) |
| `/tools/` | the internal hub linking flyers, chat, print-calculator, PaperStock, dashboard | `Clickclick-shop/tools/` | password |
| `/PaperStock/` | paper stock tracking tool. **Capital P and S** — `/stock/` is a 404 | `Node-CCG/Paper stock/` | see warning below |
| `/Dashboard-MP-CC/` | MultiPress business dashboard | `Node-CCG/multipress-extract/` | password |

## Removed 20 Aug 2026

* `calculator/tiegel.be/` — a full WordPress install with a live
  `wp-login.php` and an UpdraftPlus backup folder. Deleted.
* `calculator/Archive 15maart2026/` — a 24 Feb build snapshot holding an
  unprotected copy of the pricing spreadsheet. Deleted.

## Still to delete from the server

| path | why |
|---|---|
| `/calculator/customer.html` and `/calculator/customer.js` | the old customer-facing calculator, superseded by `/flyers/`. Old build with the pre-fix custom-size maths, on the old design, with a live "Continue to order" button. `calculator/.htaccess` line 104 leaves it **deliberately ungated**. Source is parked in `internal-print-calculator/parked-customer-calculator/` |
| `/print-calculator/.htpasswd` | byte-identical to the flyers one, which is what the config actually points at. Noted as deletable in `print-calculator/.htaccess` itself |
| `/deploy/` | not a real part of the site — created by a misrouted upload on 19 Aug 2026. **Contains a copy of `.htpasswd` in a web-reachable path**, so worth removing for that alone |
| `/Clickclick-shop/` | same cause, same problem — the workspace-root mistake reproduced the local folder name on the server |

## In progress, not finished

| path | state |
|---|---|
| `/calculator-test/` | the rebuilt internal calculator, parallel to `/calculator/`. Uploaded but **`.htaccess` never landed**, so WordPress intercepts it |
| `/print-calculator-test/` | its wrapper. Was rendering, then began returning Page Not Found. Unresolved — see `internal-print-calculator/DEPLOY-TEST.txt` |

## Worth re-checking

* **`/PaperStock/` may be open.** On 19 Jun 2026 it loaded with no password prompt, confirmed with an unauthenticated request. The intended gate appeared inactive. Never re-verified.
* **The shared password file.** `/flyers/.htpasswd` is the single credential file for `/flyers/`, `/tools/`, `/print-calculator/`, `/calculator/` and `/chat-api/`. It sits inside a directory that deploys mirror, which is how it was overwritten and then deleted on 19 Aug. Moving it above the web root would make that class of accident impossible.
* **tiegel.be is separate** and is staging only — never point anything permanent at it. `/request/` there was retired on 17 Aug; archive `request-api/requests/` and `logs/` (customer PII) before removing.

## How to verify any of this yourself

    curl -s -o /dev/null -w '%{http_code}\n' -u x:x https://clickclick.be/PATH/

401 = there and gated. 200 = there and open. 404 = not there. 500 = an
`.htaccess` problem. Always pass credentials — an anonymous 401 proves only that
a gate was declared, not that its password file exists.
