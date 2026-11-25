# Opening day: clickclick.be goes public (checklist, prepared 22 Oct 2026)

State today: the WordPress copy on clickclick.be is closed (Password Protected plugin, noindex, robots Disallow); the shop and chat are behind
the staff login and the shop is in Mollie TEST mode; the preview is an untouched twin. Plan background: `GO-LIVE-clickclick.be.md`.

ORDER MATTERS (decided with Kris, "Mollie last"): real money flows only after everything else works, and the website itself is opened LAST,
so no visitor ever meets a login box or a test shop. Sequence: prepare > rate limit > wipe test data > live Mollie key (still behind the login)
> first real order > open shop and chat > open the website > redirects and search engines > watch.

Variables for every SSH block (re-set them after reopening the console):
`H=/home/clients/bb59b9bb68f9d5f0bbd421f23f3ae8ed; P=$H/sites/preview.clickclick.be; T=$H/sites/clickclick.be; A=$H/sites/Archive-2026-10; alias wp="/opt/php8.4/bin/wp-cli"`

## A. Days before (nothing visible to the public)

- [ ] **Mollie live account ready** (can take days): Mollie dashboard in LIVE mode: organisation verified, bank account for payouts, a website profile for `https://clickclick.be`,
      payment methods switched on (Bancontact, cards, Apple Pay, ...). Create the LIVE API key (starts with `live_`; keep it in the password manager, never in chat).
- [ ] **Decide**: the date and a quiet evening (decided 23 Oct: a Tuesday or Wednesday evening from 18:30, 2 hours of room, not Friday or weekend; the live Mollie account must be ready first); who is reachable if something breaks. Page cache: decided, WP Super Cache (see D4).
- [x] **OVH**: Kris has the login (23 Oct). DMARC record added and verified. Before opening day: take a screenshot of the OVH DNS zone of clickclickgraphics.com (so the web entries can be restored after the redirect).
- [ ] **Content freeze** on the website from the day before (edit only on clickclick.be; the preview is not used any more).
- [ ] Content check one last time on clickclick.be (24 pages, 3 languages, phone number on Contact page, Woodstock example photo if wanted).
- [ ] Accountant question: how to treat the very first live order (see B3). Suggested answer to propose: the first live order is a REAL job for yourselves, so nothing has to be refunded.
- [ ] Tell the team the date; Google Business Profile, Instagram, Facebook: prepare the new address text (changed after opening, not before).

## B. Opening evening, part 1: the shop (still behind the login, nobody outside sees it)

1. **Backups** (SSH):
   ```bash
   wp --path="$T" db export "$A/root-backup/clickclick-copy-before-opening.sql" && chmod 600 "$A/root-backup/clickclick-copy-before-opening.sql"
   cp -a "$T/.htaccess" "$A/root-backup/htaccess.before-opening"
   ```
   Also download `shop/api/orders/` and `shop/api/secrets.php`, `shop/api/config.php` via FileZilla into a local folder outside the website folders (keep until the first real order has worked).
2. **Rate limit** (DONE: uploaded, live and tested on 23 Oct; nothing to do on opening day except that the go-live `index.html` replaces the test one in step 6): 5 price calculations per visitor per hour, then a pop-up with a robot icon and a button "I'm not a robot" (cookie 2 h, then 120 per 10 min). Check: click through 6 options, click the button, the price returns.
   Also: the Dashboard express button must say ON before opening (calculator-v2 > Dashboard), and test the chat once with express OFF if not done yet.
3. **Wipe the test data**: in `shop/api/orders/` delete everything EXCEPT `.htaccess`; the Dashboard must show no orders afterwards.
4. **Live Mollie**: in `shop/api/secrets.php` put the live key (`mollie_key`); in `shop/api/config.php` set `allow_live` to true. Mollie dashboard: nothing else to do (the webhook address is sent with each payment).
5. **First real order**, still logged in: a small real order (best: something the shop really needs). Check: Mollie shows it LIVE and paid; thank-you page WITHOUT the red "test" notice;
   mail to the customer address and the copy to shop@ arrive (spam folder too); receipt PDF has NO "TEST PAYMENT" line, VAT 21 %, address with apostrophe correct, number `2026-001`;
   Dashboard shows it (no TEST tag). If it is a throw-away order: refund it in Mollie and ask the accountant about the receipt.
   STOP here if anything is wrong: switch back (`allow_live` false, test key) and tell Claude.
6. Upload the go-live files from `go-live-ready/` (see its README): shop `.htaccess`, `index.html` (no test bar), `lib/site-chrome/site-chrome.js`; chat `.htaccess`, `lib/site-chrome/site-chrome.js`;
   chat-api `.htaccess` and `lib/shop.php` (already 'shop'). Upload them all in one go. From this moment /shop/ and /chat/ are PUBLIC.
6b. Tools page (`clickclick.be/tools/index-v2.html`, local source `clickclick.be/tools/`; when happy with it, rename it to index.html on the server (keep the old one as index-old.html)): remove the words "(login until opening day)" and "Payments are in Mollie test mode until opening day" for the shop and chat, and "Closed by a site password until opening day" for the website; upload the file.
7. Check from outside (a private window, no login): `/shop/` opens, a price shows, the chat answers a price question (it counts as 1 of 5 conversations per hour), `/calculator-v2/` and the staff tools still ask for a login:
   ```bash
   for u in shop/ chat/ calculator-v2/ PaperStock/ Dashboard-MP-CC/ shop/api/admin/erase.php shop/api/api-dashboard.php shop/api/backup.php shop/api/secrets.php; do printf "%-34s " "/$u"; curl -s -o /dev/null -w "%{http_code}\n" "https://clickclick.be/$u"; done
   ```
   Expect: 200, 200, 401, 401, 401, 401, 401, 401, 403.

## C. Opening evening, part 2: the website

1. **Plugin and search visibility** (SSH, or wp-admin):
   ```bash
   wp --path="$T" plugin deactivate password-protected
   wp --path="$T" option update blog_public 1
   wp --path="$T" plugin delete wordpress-importer vod-infomaniak
   wp --path="$T" cache flush
   ```
   In wp-admin check SEOPress > Titles & Metas > Advanced: "noindex" for the whole site must be OFF.
2. **robots.txt**: replace the root file with `go-live-ready/robots.txt` (allow everything public, block wp-admin and the API folders, sitemap line):
   ```bash
   cp -a "$T/robots.txt" "$A/root-backup/robots.txt.closed"
   cat > "$T/robots.txt" <<'EOT'
   User-agent: *
   Disallow: /wp-admin/
   Allow: /wp-admin/admin-ajax.php
   Disallow: /shop/api/
   Disallow: /chat-api/

   Sitemap: https://clickclick.be/sitemaps.xml
   EOT
   ```
   (remove the leading indentation if your console keeps it; the file must start at column 1)
3. **Redirects of the old addresses**: switch on the lines marked OPENING DAY ONLY in the root `.htaccess`:
   ```bash
   sed -i -E 's/^# (RewriteRule \^(flyers|legal|papers))/\1/' "$T/.htaccess"
   grep -n "^RewriteRule \^\(flyers\|legal\|papers\)" "$T/.htaccess"
   ```
   (7 lines should be listed.)
4. **Check from outside** (private window and curl):
   ```bash
   for u in "" nl/ fr/ en/ nl/drukken/ fr/contactez-nous/ robots.txt sitemaps.xml wp-login.php legal/ flyers/; do printf "%-22s " "/$u"; curl -s -o /dev/null -w "%{http_code} %{redirect_url}\n" "https://clickclick.be/$u"; done
   curl -s https://clickclick.be/nl/ | grep -io '<meta name=.robots.[^>]*>' || echo "no robots meta: good"
   ```
   Expect: `/` 302 to `/en/` (or the default language), pages 200, robots.txt 200 with the new text, sitemaps.xml 200, `/legal/` 301 to `/en/legal/`, `/flyers/` 301 to `/shop/`, and no `noindex` anywhere.
5. Click through the site as a visitor (phone and desktop): menu, languages, "Commander" and "Parlons-en" (new tab), a price in the shop, the chat, the legal and paper-guide pages.

## D. Right after opening

1. **OVH redirect (Kris, own login), through the .htaccess of the old OVH hosting, NOT the OVH Redirection tab** (that tab would replace the A/AAAA rows with OVH redirect servers, and https on the old domain could break). Hosting: OVH Web Cloud > Hosting plans > clickclickgraphics.com > FTP - SSH: server `ftp.cluster113.hosting.ovh.net`, SFTP port 22, login `clickcli`, home `/home/clickcli`. Steps: (a) screenshot of the OVH DNS zone; (b) FileZilla SFTP: download the existing `.htaccess` of the site (folder `www`, confirmed 23 Oct: one site "Website-www", root folder `www`, domains clickclickgraphics.com, www and clickcli.cluster013.ovh.net; only the first two get redirected), keep it as `htaccess.before-redirect`; (c) put the lines of `go-live-ready/ovh-old-domain-redirect.htaccess.txt` at the very top and upload; (d) check: `curl -sI https://clickclickgraphics.com/contact/` and `curl -sI https://www.clickclickgraphics.com/` both show `301` and `location: https://clickclick.be/`; `dig +short TXT _dmarc.clickclickgraphics.com` still answers. Rollback: upload the saved `htaccess.before-redirect`. Keep the OVH hosting renewal on (next renewal 1 Feb 2027). Check around 20 Nov 2026 that the Let's Encrypt certificate of the old domain (expires 24 Nov 2026) has renewed itself in OVH > Hosting > SSL certificates; if not, click the certificate's menu to renew or re-enable it: the redirect only lives as long as the hosting.
2. **Search engines**: Google Search Console: add the property `https://clickclick.be` (verify with the SEOPress "Google" meta field or a DNS TXT at Infomaniak), submit `https://clickclick.be/sitemaps.xml`; if the old clickclickgraphics.com is verified there, use "Change of address". Bing Webmaster Tools: import from Google. IndexNow: SEOPress setting, if available.
3. **Update the address** in: Google Business Profile, Instagram, Facebook, Mollie website profile, business cards/QR codes for the future (never tiegel.be or preview). E-mail signatures stay on clickclickgraphics.com (decided 23 Oct).
4. **Page cache: WP Super Cache** (decided 23 Oct 2026; the plugin is already INSTALLED AND ACTIVATED on clickclick.be, caching is still OFF, nothing to install). Switch on only now, after the Password Protected plugin is off:
   - wp-admin > Settings > WP Super Cache > **Easy** tab: **Caching On (Recommended)** > **Update Status**.
   - **Advanced** tab: keep **Simple** (PHP) caching. If it offers to update the mod_rewrite rules / `.htaccess`: **No** (our merged root `.htaccess` stays as it is). Keep "Don't cache pages for logged in users" ticked.
   - **Test Cache** on the Easy tab: the second page must come from the cache.
   - Check in a private window: home in NL, FR and EN, the language switch, the Shop and Chat buttons (new tab). Edit a page in wp-admin: if the change does not show, **Delete Cache**.
   - Rollback: Easy tab > Caching Off (the pages are normal again at once).
   - Smush WebP: checked 23 Oct, it is a paid Pro feature, so images stay JPEG (compressed). Nothing to do unless you take Smush Pro or another WebP plugin.
5. **Mail check**: send a Gmail test from shop@ (a real order mail) and read "show original": SPF pass, DKIM pass (check the selector in the Infomaniak mail panel), DMARC pass.

## E. First days

- [ ] Next morning: a new backup file `clickclick-shop-orders-...` on kDrive (04:00) and the retention/cleanup jobs ran (kDrive / Task Scheduler history).
- [ ] Watch: Mollie dashboard, `shop@` and `info@` mailboxes, the chat requests, the Anthropic cost (chat limits: 5 conversations/hour and 3 sent requests/day per visitor), 404s in the Infomaniak logs or SEOPress.
- [ ] After 30 days without problems: delete `Archive-2026-10/` contents that are not needed; ALWAYS delete `root-backup/preview-wp.sql`, `preview-wp-config.php` and `clickclick-copy-before-opening.sql` (password hashes, old database password); decide about the preview site and its database `eu3mfw_WP1528242` (keep as a password-protected staging copy, or remove) and about the old database `eu3mfw_WP1384048`.

## Rollback (each part on its own)

- **Website closed again**: `wp --path="$T" plugin activate password-protected; wp --path="$T" option update blog_public 0`, put `robots.txt.closed` back, comment the 7 RewriteRule lines out again. 1 minute.
- **Shop and chat closed again**: put the previous `.htaccess` files back (the login lines) from your local backup; the copies are in `go-live-ready/` history and in the server archive.
- **Payments back to test**: `allow_live` false in `shop/api/config.php`, the test key (`test_...`) in `secrets.php`.
- **Everything**: the old root `.htaccess` is in `Archive-2026-10/root-backup/htaccess.before-merge`.
