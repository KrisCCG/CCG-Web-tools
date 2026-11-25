# Go-live: the new website on clickclick.be (plan of 22 Oct 2026)

Decided 22 Oct 2026 (Kris):
- The new WordPress site goes live on **clickclick.be** (not on clickclickgraphics.com).
- The chat opens to the public at go-live (decided 22 Oct 2026); the login comes off with `go-live-ready/chat*/.htaccess`, the per-visitor limits and the captcha stay.
- The new shop lives at **clickclick.be/shop/** (renamed from shop-v2). Chat at clickclick.be/chat/.
- This replaces the plan of 20 Oct (MIGRATION-clickclickgraphics.com.md). The OVH domain move is no longer needed
  for go-live. clickclickgraphics.com stays at OVH for now, only redirects to clickclick.be. The mailboxes
  @clickclickgraphics.com stay as they are (Infomaniak mail), so no mail change.

## State found 22 Oct 2026 (read-only checks)

| Address | Now | At go-live |
|---|---|---|
| clickclick.be/ | 301 to www.clickclickgraphics.com | the new WordPress home |
| clickclick.be/wp-login.php | 404 (no WordPress at this root) | WordPress login |
| clickclick.be/robots.txt | `Disallow: /` (blocks everything) | allow, with sitemap line |
| clickclick.be/shop/ | 401, the OLD shop | the NEW shop |
| clickclick.be/flyers/ | 301 to /shop/ | stays |
| clickclick.be/legal/ , /papers/ | 200, old static pages | **cancelled** (folders deleted at cutover); old addresses 301 to the new WordPress pages |
| clickclick.be/shop-v2/ , /chat/ | 401 (new, gated) | /shop-v2/ gone, /shop/ and /chat/ public |
| clickclick.be/print-calculator/ | 401 | retire later |
| clickclick.be/calculator/ | 404 | nothing |
| preview.clickclick.be | WordPress, 302 to password page, PHP 8.4 | stays as staging copy or is deleted later |
| clickclickgraphics.com | OVH, old WordPress, 17 pages (project posts, contact) | 301 to clickclick.be |

**OVH facts, 23 Oct 2026:** domain clickclickgraphics.com registered 31 Mar 2015, expires 26 Feb 2029 (renews every 3 years), transfer protection ON, DNSSEC off. Hosting (cluster113) and the Anycast DNS option renew on 1 Feb 2027. Domain HOLDER is a person (Julien Bertiaux), contacts are OVH codes: consider moving the holder to Click Click Graphics SRL later (not urgent). Let's Encrypt certificates of the old domain expire 24 Nov 2026: check ~20 Nov that they renewed. OVH MX Plan (old mailboxes): renewal date still to look up.

**Express on/off switch (DONE and live 23 Oct 2026):** a button in the calculator-v2 Dashboard switches urgent orders off for the shop (option greyed out with a message in EN/FR/NL, server refuses express) and for the chat (stops offering it). Tested on the live shop; the chat part is still to test (switch off, ask the chat for urgent flyers, switch on again). The setting is stored in `shop/api/data/express_off.json` and survives the test-data wipe. The go-live `index.html` in `go-live-ready/shop/` already contains it. Local copies of the uploaded files: `upload-express/`.

**Test orders wiped 23 Oct 2026** (shop restarts from zero, Dashboard empty); repeat on opening day before the live Mollie key. The old `/shop-v2/` folder is deleted from the server (404).

**Progress 23 Oct 2026 (OVH hosting):** the old site runs on OVH web hosting `cluster113` (login `clickcli`, SFTP port 22, hosting renews Feb 2027, domain Feb 2029). The old domain has HTTPS (Let's Encrypt, valid to 24 Nov 2026) and www already 301s to the apex. The redirect of the whole domain is therefore done with an `.htaccess` rule on that hosting (lines in `go-live-ready/ovh-old-domain-redirect.htaccess.txt`), not with the OVH Redirection tab.

**Progress 23 Oct 2026 (OVH, MX):** incoming test mail from Gmail to info@ arrived; outgoing test passes SPF, DKIM and DMARC again after the change. mx1 and mx3 (OVH) removed from the DNS zone; only `5 mta-gw.infomaniak.ch` remains. The OVH MX Plan with 6 old mailboxes (archives, 2 to 5.5 GB each) is NOT cancelled; decide later what to do with the archives. All live users (Kris, Jeremy, and the others) use Infomaniak; Manon left years ago; info-bis@ was a backup copy of info@ after an OVH incident two years ago.

**Progress 23 Oct 2026 (OVH):** Kris has the OVH login (no colleague needed). DMARC added and verified (`p=none`, reports to info@). The 301 redirect of the old domain is set on opening day (screenshot the DNS zone first). DKIM, SPF and DMARC all PASS in a Gmail test (selector `20250814`, sent via Infomaniak).

**Progress 23 Oct 2026 (later):** staging copy checked in wp-admin (24 pages, no preview addresses, 149 pictures load, still closed); Smush WebP is Pro only, so images stay JPEG; sample page and privacy draft deleted; WordPress Importer deactivated; WP Super Cache installed and activated, caching OFF until opening day (checklist D4).

**Progress 23 Oct 2026:** rate limit with robot pop-up live; CMYK note under the upload box (EN/FR/NL); staff tools page uploaded as `/tools/index-v2.html` (swap with index.html when happy); icon files in the web root (apple-touch-icon.png, apple-touch-icon-precomposed.png, favicon.ico).

**Progress 22 Oct 2026:** the new shop folder is already renamed `shop-v2` -> `shop` on the server (Kris) and theme 1.4.9 is uploaded; the old folders (shop, legal, papers, flyers, print-calculator) are in `/sites/Archive-2026-10`. Follow-ups after the rename: chat-api `lib/shop.php` (SHOP_DIR_NAME = 'shop'), calculator-v2 `index.html` (Dashboard source `/shop/api/api-dashboard.php`), the 3 Task Scheduler addresses, `shop/.htaccess` ErrorDocument path.

## A. Prepare now (nothing changes on the server)

1. Download a copy of the **root `.htaccess`** of /sites/clickclick.be/ and of the old `/shop/` folder (backup, keep in ops/server-backups/).
2. Make a list of the 17 old clickclickgraphics.com addresses and where each goes (start below).
3. Theme 1.4.9 ready: footer links `clickclick.be/shop-v2/…` become `/shop/…` (file `inc/strings.php`). Do not upload before cutover.
4. Shop folder ready under the final name: links `preview.clickclick.be` in `index.html` and `lib/site-chrome/site-chrome.js` become `https://clickclick.be`.
5. Chat: `SHOP_DIR_NAME` in `chat-api/lib/shop.php` becomes `shop`; the chat's login gate removed at cutover.
6. Mollie: live key ready; webhook and return address `https://clickclick.be/shop/api/webhook.php` and `/shop/api/thankyou.php`.
7. DNS mail check on clickclickgraphics.com (DNS is at OVH, nameservers dns200/ns200.anycast.me): one SPF record (done, correct), DMARC (add now), DKIM (check selector in the Infomaniak mail panel).
   **(Update 23 Oct 2026: Kris now has the OVH login; DMARC is done.) Earlier: Kris had no access to OVH (22 Oct 2026)**: whoever owns the OVH account must add the DMARC TXT record, set the 301 redirect on the old clickclickgraphics.com hosting, and later release the domain if it moves. First find out who has the OVH login (invoices, the person who built the old site, the registrant e-mail).
   DMARC record to add in the OVH DNS zone as TXT: name `_dmarc`, value `v=DMARC1; p=none; rua=mailto:info@clickclickgraphics.com; fo=1`.
   Check afterwards: `dig +short TXT _dmarc.clickclickgraphics.com`. After 3-4 weeks of clean reports (everything passes) change `p=none` to `p=quarantine`, later `p=reject`.
8. DECIDED 23 Oct 2026: page cache = WP Super Cache (installed and activated, caching OFF until opening day; switch on per OPENING-DAY-checklist D4); quiet time = a Tuesday or Wednesday evening from 18:30, after the live Mollie account is ready.
9. ~~Rate limit on `shop/api/calc.php`~~ DONE and live 23 Oct 2026 (see section D).

## B. Cutover (about 2 hours, one evening)

Order matters. Rollback at the end.

1. **Freeze**: tell colleagues, put shop-v2 on maintenance if orders are expected. Take a backup of: WordPress files + database of the preview site, the server's `/shop/` (old), the root `.htaccess`.
2. **Old shop out of the way**: rename `/sites/clickclick.be/shop` to `shop-old` (keep 30 days; the dashboard of the old shop keeps working from there if needed).
3. **New shop in place**: rename `shop-v2` to `shop`. Keep `api/secrets.php`, `api/orders/`, counters (they move with the folder). Upload the final files (items A4, A9). Update the `.htaccess` login gate: public except the staff areas (`api/admin/`, `api/api-dashboard.php`).
4. **WordPress to the root**: copy the preview site (files + database) into `/sites/clickclick.be/` root, next to the existing app folders. Search-replace `https://preview.clickclick.be` to `https://clickclick.be` in the database (serialized-safe, dry run first).
   Decided 22 Oct: first a CLOSED staging copy on clickclick.be (plugin password stays on, noindex stays, robots.txt stays Disallow); the preview stays; opening to the public is a separate later step.
   Full runbook with commands, traps and rollback: `ops/WORDPRESS-MOVE-preview-to-clickclick.be.md`.
   Method proposed (to be confirmed): on the server over SSH, `cp -a` the WordPress folders from `/sites/preview.clickclick.be/` (wp-admin, wp-content, wp-includes, root files) to `/sites/clickclick.be/`; make a COPY of the database (new database in the Infomaniak Manager, import a dump of `eu3mfw_WP1528242`, table prefix `wp_1528242_`), point the copied `wp-config.php` at the copy, then `wp search-replace`. The preview site stays untouched as the rollback. Merge `.htaccess`: remove the old redirect of `/` to clickclickgraphics.com, add the WordPress rules, keep the rules for the app folders. WordPress ignores real folders, so /shop/, /chat/, /PaperStock/ etc. keep working.
5. Switch off: Password Protected plugin, "discourage search engines", `robots.txt` Disallow (new robots.txt with the sitemap line).
6. Upload theme 1.4.9 (footer links to /shop/). Check Polylang URLs (/nl/, /fr/, /en/) and the SEOPress canonical addresses.
7. Chat: upload `lib/shop.php` change; remove the login for /chat/ and /chat-api/.
7b. (MOVED TO THE END, see section D) **Remove all test data** (decision 22 Oct 2026: no old test orders, no test receipts): in `shop/api/orders/` delete everything EXCEPT `.htaccess` (the deny-all file): the `TEST-…json` orders, `Receipt_TEST-…pdf`, the artwork sub-folders, `temp/`, `.ratelimit/`, the test counter `invoice_counter_test_….txt`, `paid_orders.log`, `audit*.log`, `cleanup.log`, `retention.log`, `backup.log`, and `calc_log.jsonl` (browse log of the tests, in `api/` or `orders/`). The app recreates counters and logs by itself; the first real invoice becomes 2026-001. Do this BEFORE the live Mollie key goes in, so the first real order is the first thing in the folder. Check on the Dashboard that it shows no orders. Same for the old shop's orders in `Archive-2026-10`: only tests, nothing to keep (confirm there is no real paid order first).
8. (MOVED TO THE END, see section D) Mollie: live key in `secrets.php`, `allow_live` on in `config.php`, webhook address in the Mollie profile. First a small real order, refund it.
9. Task Scheduler (Infomaniak): backup, retention, cleanup get the new `/shop/api/…` addresses.
10. **Test list**: home NL/FR/EN, Printing/Signage/Your project/A good file, legal and paper guide, shop price + order + upload, receipt PDF and mails (check spam), chat (price + request, mail arrives), Dashboard, calculator-v2, staff tools, `curl -u` on gated folders (a plain 401 proves nothing), PaperStock, phone view.
11. **Cancel the old static pages**: after a backup, delete the folders `/legal/` and `/papers/` on the server (decided 22 Oct 2026). Paste `go-live-ready/root-htaccess-redirects.txt` into the root `.htaccess` so old links (older mails, receipts, search results) land on the new WordPress pages. Then the 301 redirects of the old clickclickgraphics.com (on the OVH hosting).
12. Search engines: Google Search Console property for clickclick.be, submit the sitemap, then the **"Change of address"** tool for clickclickgraphics.com (the old site, if it is verified there), IndexNow on, page cache on (only after the password is gone), speed measured again.

Rollback (before OVH or anything else is cancelled): put the old root `.htaccess` back (clickclick.be/ redirects to clickclickgraphics.com again), rename `shop` to `shop-v2` and `shop-old` back to `shop`. Takes minutes; nothing else is deleted during cutover.

## Archive of old server folders (decided 22 Oct 2026)

Archive = a folder OUTSIDE the web root, so nothing in it can be opened from the internet:
`/archive-2026-10/` at the top level of the Infomaniak account (next to `sites`, `backups`, `tmp`). Never inside `/sites/clickclick.be/`.
Moving inside the same server is instant (drag and drop in FileZilla's remote panel, nothing is downloaded). Moving back is the same drag = rollback.

At cutover (step 2/3/5/11): `shop` -> `archive-2026-10/shop-old`, `legal`, `papers`, `flyers`, and the root files `index.html` (it would hide the WordPress home!), `robots.txt` (replaced), old root `.htaccess` (kept as backup copy, then merged).
After 30 days without problems: `print-calculator`, and `calculator` if it is still on the server (old backend and old orders: copy `orders/` and the invoice counters to the backup first; they are tax records).
Stay: `Dashboard-MP-CC`, `PaperStock`, `mail-analyse`, `mail-analyse-api`, `calculator-v2`, `chat`, `chat-api`, `shop` (= the renamed shop-v2), `tools`.

**Opening day: the detailed checklist is `ops/OPENING-DAY-checklist.md`** (order: rate limit, wipe test data, live Mollie, first real order, open shop and chat, THEN open the website).

## D. Last steps, after everything else works (decided 22 Oct 2026: Mollie goes last)

1. ~~Rate limit on `shop/api/calc.php`~~ **DONE 23 Oct 2026, live and tested**: 5 price calculations per visitor per hour, then a pop-up (robot icon, button "I'm not a robot"); after the click 120 per 10 minutes for 2 hours. Files: `api/calc.php`, `api/lib/humancheck.php`, `lib/i18n.js`, `index.html`; max items per order stays 10. The go-live `index.html` in `go-live-ready/shop/` contains it.
2. Remove all test data (step 7b above): the 9+ test orders, receipts, artwork, counters, logs. Check the Dashboard is empty.
3. Mollie live key in `secrets.php` + `allow_live` in `config.php`, webhook/return addresses, then one small REAL order and refund it yourself. Look at the PDF (address with an apostrophe must read correctly: fix in invoice.php uploaded 22 Oct).
Until step 3 the shop stays in test mode on the public site: the red TEST bar is already removed in `go-live-ready/shop/index.html`, so decide whether to upload that file only together with step 3.

## C. After

- Watch 404s two weeks (WordPress SEOPress 404 monitor or the Infomaniak logs), add 301s.
- Update: Google Business Profile, Instagram, Facebook, QR codes, Mollie website address, invoices/receipts text. E-mail signatures stay on clickclickgraphics.com (decided 23 Oct 2026).
- After 30 days without problems: remove `shop-old`, the old `calculator`, `print-calculator`, `flyers` (see mind map "To retire").
- Update SERVER-MAP.md and the mind map.
- OVH (hosting, DNS, domain registration for clickclickgraphics.com): decide later, it is not part of this go-live.

## Redirect of the old clickclickgraphics.com (decided 22 Oct 2026: one domain redirect, no per-page map)

The whole old domain gets ONE permanent (301) redirect to the new site, www included:
`clickclickgraphics.com` and `www.clickclickgraphics.com`, every path  ->  `https://clickclick.be/`
(all 17 old addresses, including the 10 old project posts, land on the new home page; nothing is mapped one by one).
Made by Kris (own OVH login, 23 Oct 2026) as a rule in the .htaccess of the old OVH hosting (cluster113, login clickcli, SFTP), NOT with the OVH Redirection tab, so that https on the old domain keeps working. Ready-made lines: `go-live-ready/ovh-old-domain-redirect.htaccess.txt`. Keep the hosting renewal on (next Feb 2027).
Check afterwards: `curl -sI https://clickclickgraphics.com/contact/` shows `301` and `location: https://clickclick.be/`.
Then: Google Search Console, "Change of address" for the old domain (if it is verified there), update Google Business Profile,
social profiles to clickclick.be (e-mail signatures stay as they are, decided 23 Oct). Mail addresses @clickclickgraphics.com are not affected by the redirect.

Still true: the old static pages of clickclick.be itself (`/legal/`, `/papers/`) are redirected by `go-live-ready/root-htaccess-redirects.txt`.
