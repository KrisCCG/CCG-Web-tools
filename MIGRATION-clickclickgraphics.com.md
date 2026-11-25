# Migration: preview.clickclick.be → clickclickgraphics.com (leave OVH entirely)

Decisions (2026-10-20): canonical host is **clickclickgraphics.com WITHOUT www** (www 301 → apex).
Mailboxes are at Infomaniak. Goal: nothing left at OVH (hosting, DNS, domain registration, mail).

State found 2026-10-20 (read-only checks):
- preview.clickclick.be → Infomaniak 185.125.27.208, WordPress-like, noindex + password plugin.
- clickclickgraphics.com → OVH hosting 213.186.33.24 (+ IPv6 AAAA 2001:41d0:1:1b00:213:186:33:24). www 301 → apex.
- Registrar: **OVH, SAS**. Created 2013-02-26, expires 2029-02-26. Status clientTransferProhibited (normal lock; must unlock + get auth code).
- DNS: OVH anycast (dns200/ns200.anycast.me).
- MX: Infomaniak mta-gw (prio 5) + **OVH mx1 (6), mx3 (100)** → leftovers, remove once verified.
- mail./smtp./imap. CNAME → ssl0.ovh.net (OVH mail hosts) → leftover, check no one still uses them.
- SPF at apex: probing showed more than one SPF-looking record (one with mailgun / rrpproxy / OVH IPs). ONLY ONE SPF record may exist.
- Other TXT seen (verify in the OVH zone view, my probe was noisy): google-site-verification x4, atlassian, apple, MS=, amazonses.

## Phase 0 — before touching anything
1. OVH Manager → Domain → DNS zone → **export/copy the whole zone as text**. Save it in ops/ (do not upload).
2. Full backup of the OVH site: files (FTP/SSH) + MySQL dump. Keep OVH hosting alive ≥ 30 days after cutover.
3. Ask each mailbox user (and check Infomaniak Mail panel): is anything still received/sent via OVH (mx1/mx3, ssl0.ovh.net)? If yes, migrate it first.
4. Note which services rely on the DNS TXT verifications (Google Search Console, Atlassian, Apple, Microsoft 365?, Amazon SES, Mailgun). Keep every one still in use.
5. Lower TTL of A/AAAA/MX records at OVH to 300 s, 24 h before step 2 below.

## Phase 1 — prepare the new site on Infomaniak (no DNS change yet)
1. In WordPress on preview: take a backup; then `wp search-replace 'https://preview.clickclick.be' 'https://clickclickgraphics.com' --all-tables --dry-run`, then run without --dry-run (serialized-safe). Check theme/page-builder settings for URLs.
2. Hard-coded URLs outside WP: calculator (calc.php, pay.php, webhook.php, thankyou.php), chat/chat-api (API base, CORS), email templates. grep for `preview.clickclick.be`, `clickclick.be`, `tiegel.be`.
3. Mollie: update redirect + webhook URLs (test mode first). Webhook must be publicly reachable over https at the final host.
4. Decide shop URL layout; write the 301 map (old clickclickgraphics.com pages → new; old clickclick.be/flyers/ etc. → new shop pages). Keep clickclick.be root redirecting to the apex.
5. Infomaniak Manager: add **clickclickgraphics.com** (and www) as domain/alias of this site. Do not issue the certificate until DNS points there (or use DNS validation if offered).
6. .htaccess: www → apex 301, http → https. robots.txt must NOT disallow; sitemap URL = https://clickclickgraphics.com/sitemap_index.xml.
7. Dashboard-MP-CC and PaperStock: confirm Basic-auth gate works with `curl -u` (see memory apache-basic-auth-401-vs-500). Never publish .claude/ or deploy-files/.

## Phase 2 — move DNS to Infomaniak (do this BEFORE the site cutover, one change at a time)
1. Infomaniak Manager → Domains → add/transfer-in zone: create the DNS zone for clickclickgraphics.com.
2. Recreate records from the OVH export: MX (Infomaniak only), ONE SPF (v=spf1 include:spf.infomaniak.ch + any sender you still use, e.g. mailgun/SES, ~all), DKIM (Infomaniak provides), DMARC (start p=none), all TXT verifications still needed. Skip mx1/mx3 OVH and ssl0.ovh.net CNAMEs unless step 0.3 says needed.
3. Temporarily keep A/AAAA pointing at OVH (site stays live) — add the zone at Infomaniak first, verify with `dig @<infomaniak-ns> ...`.
4. At OVH registrar: **unlock** the domain, get the **authorization (EPP) code**. At Infomaniak: order the domain transfer (.com ~ 1 year added to expiry; takes up to 5-7 days, can be accelerated by approving at OVH). Registrant email must be reachable for confirmation.
5. When transfer completes, nameservers switch to Infomaniak automatically (or set them manually: ns11.infomaniak.ch / ns12.infomaniak.ch).
   Alternative: switch NS first (Infomaniak zone ready), transfer registration afterwards. Either order is fine; never both unfinished at once without the zone ready.

## Phase 3 — cutover (quiet time, e.g. Sunday evening)
1. In the Infomaniak zone: set A for clickclickgraphics.com and www → site IP (confirm in Manager; currently same as preview 185.125.27.208). **Delete the AAAA** to the OVH IPv6 (or set Infomaniak's, if provided).
2. `dig +short clickclickgraphics.com` from a few resolvers until it shows the new IP. Issue / enable Let's Encrypt cert at Infomaniak. `curl -sI https://clickclickgraphics.com` and www → 301 to apex.
3. WordPress: Settings → Reading: untick "discourage search engines"; deactivate Password Protected plugin; remove X-Robots noindex. Update robots.txt (remove Crawl-delay 10 if not wanted).
4. Smoke tests: home NL/FR/EN, product pages, calculator, Mollie test payment then 1 real small order, upload, order email arrives (check spam), chat bot, contact form, admin login.
5. Mail test both ways incl. SPF/DKIM/DMARC pass (send to a Gmail and check "show original").

## Phase 4 — after
1. Search Console: add property for https://clickclickgraphics.com (domain property via DNS TXT), submit sitemap, check Coverage/Redirects. Bing Webmaster Tools: import from GSC.
2. Watch 404s for 2 weeks; add 301s. Update Google Business Profile URL, social profiles, email signatures, QR codes (never tiegel.be).
3. After ≥30 days with no issues: cancel OVH hosting + email plans. Keep the domain registration at Infomaniak (auto-renew on). Confirm nothing else at OVH (invoices/Manager list).
4. Update memory/SERVER-MAP.md with the new layout.

## Rollback
Until OVH hosting is cancelled: set A/AAAA back to 213.186.33.24 / 2001:41d0:1:1b00:213:186:33:24 (TTL 300 → back within minutes).
