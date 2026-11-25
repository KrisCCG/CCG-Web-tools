import math, html
B = [  # key, title, subtitle, status, status text
 ("website","Website","clickclick.be (preview now)","test"),
 ("shop","Shop","online ordering","test"),
 ("chat","Request chat","AI quote assistant","todo"),
 ("prices","Prices","one Excel file","live"),
 ("staff","Staff tools","behind a login","live"),
 ("domain","Domain & mail","clickclick.be, mail stays","todo"),
 ("safety","Rules & safety","backups, privacy, logins","todo"),
 ("retire","To retire","after go-live","retire"),
 ("golive","Go-live list","what is still open","todo"),
]
STATUS = {"live":("Live","var(--live)"),"test":("Test / preview","var(--test)"),"todo":("Work open","var(--todo)"),"retire":("Retire later","var(--gone)")}
cx, cy, rx, ry, nw, nh = 480, 330, 355, 250, 184, 58
svg=[f'<svg viewBox="0 0 960 660" role="img" aria-label="Overview of the Click Click web setup: nine branches around the centre" class="radial">']
nodes=[]
for k,(key,t,s,st) in enumerate(B):
    a=math.radians(-90+k*360/len(B)); x=cx+rx*math.cos(a); y=cy+ry*math.sin(a); nodes.append((x,y))
    svg.append(f'<line x1="{cx}" y1="{cy}" x2="{x:.0f}" y2="{y:.0f}" class="ln" style="stroke:var(--b-{key})"/>')
svg.append(f'<g><ellipse cx="{cx}" cy="{cy}" rx="112" ry="62" class="root"/><text x="{cx}" y="{cy-6}" class="rt1" text-anchor="middle">Click Click</text><text x="{cx}" y="{cy+17}" class="rt2" text-anchor="middle">web setup</text></g>')
for (key,t,s,st),(x,y) in zip(B,nodes):
    lab,col=STATUS[st]
    svg.append(f'<a href="#{key}"><g class="nd"><rect x="{x-nw/2:.0f}" y="{y-nh/2:.0f}" width="{nw}" height="{nh}" rx="12" style="stroke:var(--b-{key})"/>'
               f'<circle cx="{x-nw/2+16:.0f}" cy="{y-nh/2+17:.0f}" r="6" style="fill:{col}"/>'
               f'<text x="{x-nw/2+30:.0f}" y="{y-nh/2+22:.0f}" class="nt">{html.escape(t)}</text>'
               f'<text x="{x-nw/2+16:.0f}" y="{y-nh/2+43:.0f}" class="ns">{html.escape(s)}</text></g></a>')
svg.append('</svg>')
SVG="\n".join(svg)

def chip(k,t=None):
    m={"live":"Live","test":"Test","todo":"To do","dec":"Decide","retire":"Retire"}
    return f'<span class="chip {k}">{t or m[k]}</span>'
def ul(items): return '<ul class="t">'+''.join(f'<li>{i}</li>' for i in items)+'</ul>'
def grp(label, items): return f'<div class="grp"><h4>{label}</h4>{ul(items)}</div>'
def code(s): return f'<code>{s}</code>'

SECTIONS = []
def sec(key,title,tag,status,intro,groups):
    SECTIONS.append(f'<details class="branch" id="{key}" style="--bc:var(--b-{key})" open><summary><span class="bt">{title}</span><span class="tg">{tag}</span>{chip(*status) if isinstance(status,tuple) else chip(status)}</summary><p class="intro">{intro}</p><div class="groups">{"".join(groups)}</div></details>')

sec("website","Website","the public pages in NL, FR and EN",("test","Preview: built, not live"),
 "The WordPress site that sends customers to the shop and the chat. Everything is written and checked; it only needs switching on.",
 [grp("Where it is",[
    f"Today: {code('preview.clickclick.be')} behind a password plugin, set to noindex (hidden from Google).",
    f"Final address: {code('clickclickgraphics.com')} (no www)."]),
  grp("What is on it",[
    "24 pages = 8 pages in 3 languages: Home, Printing, Signage, Your project, Good file, Contact, Paper guide, Legal and privacy.",
    "Plugins that matter: Polylang (languages), SEOPress (search texts), Smush (image compression), Password Protected (staging gate)."]),
  grp("Finished on 21 Oct 2026",[
    "Search title and description on all 24 pages (SEOPress).",
    "Alt text on every picture, NL / FR / EN (456 pictures). Default alt text in the media library is English.",
    "Pictures compressed (Smush, WebP on); the two label badges on Printing cut from 700 KB to 20 KB.",
    "iPhone: the intro carousel pictures on the Printing page load again.",
    "Privacy page (EN, NL, FR): new row in the retention table, receipt copies are kept on the server.",
    "French text round (FR only): the two buttons in the pages now say Commander / Parlons-en like the footer; the double blue title on the three Your project pages is gone; the legal page and the paper guide have Kris's new wording (two paper sections rewritten in all five papers); the chat reply time reads \"sous un ou deux jours ouvrables\".",
    "\"Two ways in\" section is full width on Printing, Signage, Your project and A good file? in NL, FR and EN, with the same white space above the footer (76 px on desktop). The FR page \"Un bon fichier ?\" got the section it lacked.",
    "Footer FR: \"le chat\" and \"Ouvrir une carte\" (theme version 1.4.7). The French changes were also copied to NL and EN by Kris.",
    "22 Oct 2026: every Order / Chat button on the website (18 pages, footer, theme layouts) opens in a new browser tab; the FR and EN buttons pass the language to shop and chat like the NL ones (#lang=fr / #lang=en). Theme 1.4.9 is installed on the preview.",
    "Master texts and sheets: "+code("ClickClick-Web-tools/tiegel.be/")+" (seo-titles-descriptions and alt-texts spreadsheets)."]),
  grp("Still open",[
    chip("todo")+" Contact page has no phone number. Paper guide has no printed example for Woodstock Bianco."]),
  grp("Good to know",[
    "If the pictures in a carousel stay blank on an iPhone, suspect Smush's Lazy Load setting. This happened on the Printing page on 21 Oct 2026 and was fixed by changing that setting.",
    "Alt text lives in each page's content (per language). The media library only holds an English default.",
    "The header and footer texts (EN, FR, NL) are not editable in the page editor. They live in the theme file "+code("inc/strings.php")+". Theme zips: "+code("Website-Design/wordpress-theme/clickclick-1.4.7.zip")+" (newest). To change a footer text: edit that file, make the next zip version, upload under Appearance, Themes, Add Theme.",
    "A section placed as last block of a page needs bottom space of its own: the theme only adds space after blocks that are not groups."]),
  grp("At go-live",[
    "Switch off the Password Protected plugin and WordPress \"discourage search engines\".",
    "Switch IndexNow on, submit the sitemap in Google Search Console, then switch on the page cache (WP Super Cache, already installed).",
    "Never add a page cache before the password is gone: it could show the private site to anyone."])])

sec("shop","Shop","order flyers, cards, posters, folders and brochures online",("test","Test: payments are Mollie test mode"),
 "The new shop. Since 22 Oct 2026 it sits at /shop/ (renamed from shop-v2; the old shop is archived). It works end to end in test mode.",
 [grp("Where it is",[
    f"Today: {code('clickclick.be/shop/')} with a login and a red TEST bar. The local source folder is still called {code('shop-v2')}.",
    f"Final versions of the files that change at go-live (login off, TEST bar off) are ready in {code('ClickClick-Web-tools/go-live-ready/')}, not uploaded.",
    f"Old QR codes: the redirect {code('/flyers/')} to {code('/shop/')} goes into the root .htaccess at cutover ({code('go-live-ready/root-htaccess-redirects.txt')})."]),
  grp("Rules a customer meets",[
    "All prices include 21 % VAT on every product (the brochure had 6 % until 21 Oct 2026).",
    "Delivery by DPD €15 (Belgium). Express (48 h) is pick-up only. Production is 5 working days; orders after 15:00 count from the next day.",
    "No cancellation once production starts (written on the legal page). Files up to 100 MB; PDF, JPG, PNG or TIFF."]),
  grp("Payment and mail",[
    "Mollie. Only a test key works until the setting "+code("allow_live")+" is switched on in "+code("api/config.php")+".",
    "Customer mail and thank-you page follow the language chosen in the shop (NL, FR, EN). The PDF receipt is English with FR and NL lines.",
    "Mail is sent through the Infomaniak mailbox shop@ (login in secrets.php) so it is not marked as spam.",
    "Test orders are numbered TEST-2026-001 and up, are marked [TEST] and are never mailed to the accountant."]),
  grp("Test result",[
    "20 and 21 Oct 2026: orders in Dutch and French paid with the Mollie test page. Thank-you page, both mails and the PDF were correct and arrived in the inbox."]),
  grp("Where the files are",[
    "Local source: "+code("ClickClick-Web-tools/clickclick.be/shop-v2/")+" (README.txt and BACKUP.md explain the rest).",
    "On the server the same folder. Inside "+code("api/")+": prices, pay, upload, webhook, thank-you, PDF receipt, backup, retention, erase, dashboard feed.",
    "Orders live in "+code("api/orders/")+" (blocked from the web). "+code("api/secrets.php")+" exists only on the server."])])

sec("chat","Request chat","AI assistant for anything that is not a simple order",("todo","Gated: login since 19 Aug 2026"),
 "Customers describe a bigger job; the assistant asks the right questions and sends one complete request to info@.",
 [grp("Where it is",[
    f"{code('clickclick.be/chat/')} and {code('/chat-api/')}. Source: {code('ClickClick-Web-tools/clickclick.be/clickclick-chat/')}.",
    "The website already tells customers to use it, but the login still blocks them."]),
  grp("How it works",[
    "Prices come from the shop's price files, read straight from the shop folder, so chat, shop and calculator always agree.",
    "The folder name is set in "+code("chat-api/lib/shop.php")+" (SHOP_DIR_NAME = shop since 22 Oct 2026, tested: the price card shows).",
    "Conversations are kept 90 days (as the privacy page says). It uses the Anthropic API."]),
  grp("Changed 21 Oct 2026",[
    "VAT answers only quote the shop's figures (incl., excl., difference), always with the euro sign. The chat may not calculate prices or VAT itself.",
    "To send a request the chat needs an email, or a name and a phone number. It never invents contact details; placeholder emails count as empty. The summary stays on screen after Edit my request.",
    "Limits per visitor: 5 new conversations per hour, 3 submitted requests per day."]),
  grp("Open",[
    "Retested 21 Oct 2026 and working: name + phone, email only, no made-up contact details, Edit my request keeps the summary, sending tested by Kris.",
    "Decided 22 Oct 2026: the chat opens to the public at go-live. The login is removed with the go-live files (chat/.htaccess and chat-api/.htaccess in go-live-ready/); limits and captcha stay.",
    chip("todo")+" After opening: watch the Anthropic cost and the request mails in the first weeks; adjust the limits in secrets.php if needed."])])

sec("prices","Prices","one Excel file feeds everything",("live","Live"),
 "Prices, papers, machines and products live in one Excel file. The shop, the chat and the staff calculator all read a copy of it.",
 [grp("The file",[
    code("ClickClick-Web-tools/pricing/print-calculator-values_2026-10-14.xlsx")+" (the date in the name is the original date; the Change log tab shows every edit).",
    "Previous versions: "+code("pricing/_backup/")+"."]),
  grp("To change a price",[
    "1. Edit the Excel and close it.",
    "2. Double-click "+code("Update prices.command")+": it checks every tab and recalculates 8,760 test prices.",
    "3. Upload the two folders in "+code("pricing/upload/")+" with FileZilla. Always upload "+code("price-data.xlsx")+" and "+code("selftest.json")+" together."]),
  grp("Built-in safety",[
    "Shop, chat and calculator check themselves against selftest.json. On any difference they refuse to show a price. The calculator shows a green or red bar.",
    "The master formula is "+code("pricing/check_prices.py")+"; engine.js (calculator) and engine.php (shop) are copies that must stay identical."]),
  grp("Decisions",[
    "22 Oct 2026: flyers are priced at the floor: cost + 25 %, no minimum job price, no online discount, only on papers from 80 to 170 g/m2 (all current flyer papers). Four new optional columns in the Products tab; other products keep the general rules (30 % minimum margin, EUR 50 minimum, online discount).",
    "21 Oct 2026: VAT is 21 % for all products (Products tab, column VAT rate is empty). Brochure prices including VAT rose about 14 %; prices excluding VAT did not change.",
    "Online price is the Multipress price minus 10 %, as long as the margin stays at least 30 %. Minimum job price €50."]),
  grp("Done 21 Oct 2026",["VAT-update price files and engine.js uploaded to /calculator-v2/ and /shop-v2/api/data/ and checked on the server (brochure A4 16 p x50 = EUR 270.00 incl. / 223.14 excl.). Chat files uploaded."])])

sec("staff","Staff tools","internal, behind the shared login, never linked from the public site",("live","Live"),
 "Everything here stays on clickclick.be. Login realm \"Click Click — dashboard\" (some tools have their own).",
 [grp("Tools",[
    f"{code('/calculator-v2/')} Calculator v2 and Dashboard. Costs and margins; the Dashboard tab shows production tick boxes, paid orders and browse statistics for both shops. Test orders are tagged and left out of revenue.",
    f"{code('/Dashboard-MP-CC/')} MultiPress business dashboard (source: Dashboard-CC folder, own login).",
    f"{code('/PaperStock/')} paper stock tool. Capital P and capital S; /stock/ does not exist.",
    f"{code('/mail-analyse/')} and {code('/mail-analyse-api/')} turn a customer's quote email into a clear brief.",
    f"{code('/tools/')} link page for the team. "+chip("todo")+" Add calculator v2 and remove the old calculator."]),
  grp("Not on the web at all",["ClickClick-Business (accounting), HR, Clovis financial plan, Website-Design working copy, Backup and _Archive."])])

sec("domain","Domain & mail","where the names live",("todo","Go-live plan written"),
 "Since 22 Oct 2026 clickclick.be is the final address of everything customers see (it was clickclickgraphics.com before).",
 [grp("Domain",[
    "The new site, shop and chat all live on clickclick.be (already at Infomaniak, no DNS move needed). Plan and order: "+code("ClickClick-Web-tools/GO-LIVE-clickclick.be.md")+".",
    chip("live","Done")+" Kris now has the OVH login (23 Oct 2026), no colleague needed. DMARC is added. Still to do with the login: the 301 redirect on opening day (take a screenshot of the DNS zone first), and later release the domain.",
    "clickclickgraphics.com stays at OVH for now: the old WordPress there (17 pages) only redirects to clickclick.be. Leaving OVH completely (hosting, DNS, registrar) is postponed; the old plan is "+code("MIGRATION-clickclickgraphics.com.md")+".",
    "Today clickclick.be/ still redirects to the old site, and its robots.txt blocks all search engines: both change at the cutover. tiegel.be was only a staging copy: never use it for anything permanent."]),
  grp("Mail",[
    "Mailboxes at Infomaniak: info@ (general, chat requests) and shop@ (orders). Never use a personal address for customers.",
    "Checked 22 Oct 2026: one SPF record, correct (include:spf.infomaniak.ch). Mail addresses @clickclickgraphics.com stay as they are.",
    chip("live","Done")+" DMARC added at OVH on 23 Oct 2026 and checked with dig: v=DMARC1; p=none; rua=mailto:info@clickclickgraphics.com; aspf=r. After 3 to 4 weeks of clean reports: quarantine, later reject. The reports arrive at info@ from about 24 Oct.",
    chip("live","Done")+" DKIM, SPF and DMARC all PASS (Gmail test 23 Oct 2026, mail from info@clickclickgraphics.com via Infomaniak): dkim=pass s=20250814, spf=pass, dmarc=pass (p=none). The old OVH backup mail servers (mx1, mx3) were removed from the DNS on 23 Oct 2026 (OVH answers with one MX only). An incoming test mail from Gmail to info@ arrived (23 Oct). The OVH MX Plan (6 mailboxes, old archives of info, info-bis, jeremy, kris, manon, postmaster) stays until you decide what to do with the archives; find its renewal date."]),
  grp("Redirects",[
    "Written, not yet live: root redirects for the old /flyers/, /legal/ and /papers/ addresses ("+code("go-live-ready/root-htaccess-redirects.txt")+").",
    chip("todo")+" Old clickclickgraphics.com: one 301 redirect of the whole domain (www too) to https://clickclick.be/ at OVH; all 17 old addresses, including the 10 project posts, land on the new home page. Mailboxes are not affected."])])

sec("safety","Rules & safety","backups, privacy and logins",("todo","Scripts ready, schedule not set"),
 "What protects the data, and the promises made on the privacy page.",
 [grp("Backups",[
    f"{code('api/backup.php')} makes an encrypted copy of all orders every night (04:00) on kDrive and keeps 30 days. Restore steps are in {code('shop-v2/BACKUP.md')}.",
    "The backup passphrase must also be in the password manager: without it the archives cannot be opened.",
    chip("live","Done")+" Task Scheduler jobs at the /shop/api/ addresses: backup (daily 04:00), retention (weekly), cleanup. Each was run once by hand on 22 Oct and worked."]),
  grp("Privacy promises (legal page)",[
    "Artwork deleted after 180 days, personal data after 2 years, security logs after 180 days, chats after 90 days. "+code("api/retention.php")+" does it weekly.",
    f"One customer's erasure request: {code('api/admin/erase.php?email=…')} (add &amp;dry=1 first).",
    "Decision 21 Oct 2026: receipt PDFs are kept for now. The privacy page says so (row added 21 Oct, EN, NL, FR)."]),
  grp("Logins",[
    "Shared password files sit above the web root ("+code("~/.htpasswd-ccg")+", -paperstock, -mpcc). Rotating a password = replacing that file, no upload.",
    chip("live","Done")+" The shared password that was exposed once has been rotated (confirmed 23 Oct 2026)."]),
  grp("Working rules",[
    "Open the deploy folder itself (shop or clickclick-chat) as the VS Code workspace, never a parent folder, or files land in the wrong place on the server.",
    "Never upload .claude/, deploy-files/, secrets or password files.",
    "The Mollie webhook must stay public (no login). It trusts nothing: it asks Mollie about every call."])])

sec("retire","To retire","after the new shop and site are live",("retire","Retire later"),
 "Do these only after go-live has run well for a while.",
 [grp("Old things",[
    f"Already moved on 22 Oct 2026 to {code('/sites/Archive-2026-10/')} (outside the web folders): the old shop, {code('legal')}, {code('papers')}, {code('flyers')}, {code('print-calculator')}. Delete after 30 days when nothing is missed. The old orders there were tests only.",
    "tiegel.be staging (keep the text drafts). OVH hosting and mail are not part of this go-live; decide later.",
    "Test data in the new shop (TEST orders, receipts, logs, counters): delete everything in "+code("shop/api/orders/")+" except .htaccess, just before the live Mollie key goes in."])])

sec("golive","Go-live list","everything still open, in one place",("todo","Open"),
 "Plan with the order of steps: "+code("ClickClick-Web-tools/GO-LIVE-clickclick.be.md")+". Final files ready, not uploaded: "+code("ClickClick-Web-tools/go-live-ready/")+".",
 [grp("Decided on 22 Oct 2026",[
    "The new website goes live on clickclick.be itself (WordPress in the web root). The OVH domain move is NOT part of go-live; clickclickgraphics.com only redirects to clickclick.be and keeps its mailboxes.",
    "The shop lives at clickclick.be/shop/, the chat at clickclick.be/chat/ and opens to the public (login off, limits and captcha stay). Staff tools stay behind their login.",
    "Old static pages /legal/ and /papers/ are cancelled; redirects to the new WordPress pages are kept. No old test orders or test receipts are kept.",
    "Mollie live comes LAST, after everything else works.",
    "Flyer prices: cost + 25 %, no minimum job price, only on papers 80 to 170 g/m2 (done and live, see below).",
    "Old clickclickgraphics.com: one permanent redirect of the whole domain to clickclick.be (no page-by-page map)."]),
  grp("Done so far",[
    "Shop folder renamed to /shop/; chat prices and Dashboard follow it. Old folders are in /sites/Archive-2026-10/ (outside the web folders).",
    "Task Scheduler: backup (new file name clickclick-shop-orders-...), retention and cleanup run at the /shop/api/ addresses.",
    "Full test order on /shop/ (22 Oct): price, upload, Mollie test payment, thank-you page, Dashboard, customer mail (FR) and shop mail (EN, by design) with the receipt. VAT 21 % correct.",
    "WordPress staging copy on clickclick.be (22 Oct): files, a separate database (eu3mfw_clickclick_be_wp) and the address replaced (304 replacements). It is CLOSED by the Password Protected plugin and robots.txt; the preview stays untouched. Root .htaccess merged (the redirect of / to the old site is gone); backups in /sites/Archive-2026-10/root-backup/. Commands: ops/WORDPRESS-MOVE-commands.md.",
    "PHP of clickclick.be on 8.4; theme 1.4.9 on the preview; buttons open in a new tab, FR and EN buttons carry the language.",
    "Flyer floor price live and checked on 22 Oct: shop, chat and staff calculator agree (8,760 of 8,760 test prices; A6 500 single = EUR 45). Receipt PDF apostrophe fix uploaded. Scripts in the staff calculator carry a version number, so an old engine can no longer stay cached.",
    "23 Oct: rate limit on the shop price request is live and tested: 5 calculations per visitor per hour, then a pop-up with a robot icon and the button \"I'm not a robot\" (then 120 per 10 minutes for 2 hours). Files: api/calc.php, api/lib/humancheck.php, lib/i18n.js, index.html. Max items per order stays 10.",
    "23 Oct: WordPress tidied: the default Sample page and the Privacy policy draft are deleted (24 pages left, all published); WordPress Importer is deactivated (deleted together with VOD Infomaniak on opening day). WP Super Cache is installed and activated, caching OFF.",
    "23 Oct, OVH (with Kris's own login): DMARC added; old backup mail servers mx1 and mx3 removed from the DNS (incoming and outgoing test mails fine); the OVH MX Plan with 6 old mailboxes was looked at and is kept for now; the old web hosting found (cluster113, login clickcli, SFTP) and the plan for the old-domain redirect changed to an .htaccess rule on that hosting (https keeps working).",
    "23 Oct: express (urgent) on/off switch is live: a button at the top of the Dashboard tab in the staff calculator. Off = the shop greys out the express option with a message (EN, FR, NL), the server refuses express prices and express payments, and the chat stops offering express. The setting is a small file in shop/api/data/ (wiping the orders does not touch it). Tested on the live shop; switched back ON afterwards. The chat part was not tested live yet.",
    "23 Oct: the test orders in the shop were wiped and the Dashboard shows nothing (to be repeated on opening day). The old /shop-v2/ folder is gone from the server (404).",
    "23 Oct: the shop says under the upload box that files must be in CMYK (CMJN (CMYK) in French), in EN, FR and NL.",
    "23 Oct: new staff tools page with correct links is on the server as /tools/index-v2.html (the old index.html is untouched until you swap them). The three icon files (apple-touch-icon.png, apple-touch-icon-precomposed.png, favicon.ico) are in the web root, so iPhone and iPad show the !! icon even before opening."]),
  grp("Still to do before the cutover",[
    chip("todo")+" Opening evening, last step, with your own OVH login: ONE permanent 301 redirect of the whole old clickclickgraphics.com (www too, every path) to https://clickclick.be/, written in the .htaccess of the old OVH hosting (cluster113, login clickcli, SFTP) so that https keeps working; NOT in the OVH Redirection tab. Lines ready in go-live-ready/ovh-old-domain-redirect.htaccess.txt. Screenshot the DNS zone and save the old .htaccess first. Keep the hosting renewal on (Feb 2027). The go-live does not wait for this, but the old address shows the old site until the redirect is set.",
    chip("live","Done")+" Staging copy on clickclick.be checked on 23 Oct (logged in): all 24 pages in NL, FR, EN open, no address of preview.clickclick.be left, 149 pictures all load, one title per page, canonical and language links correct, sitemap answers, search engines still discouraged and the password plugin still on. Smush: WebP and AVIF are a Pro (paid) feature, so no WebP is delivered; compression is working (154 images, 43 % saved) and 7 images are still to optimize. Content edits from now on: only in ONE place (clickclick.be), or repeat the copy.",
    chip("dec","Decided")+" Page cache: WP Super Cache, already installed and activated with caching OFF; switch it on after opening (checklist D4). Quiet evening: a Tuesday or Wednesday from 18:30, once the live Mollie account is ready. Still to pick: the actual date."]),
  grp("Opening day (one evening, checklist ready)",[
    "Detailed checklist: "+code("ClickClick-Web-tools/ops/OPENING-DAY-checklist.md")+". Order: wipe test data, live Mollie key (still behind the login), first real order, open shop and chat (logins off), THEN open the website (plugin off, robots.txt, redirects), then redirect of the old domain at OVH, search engines, watch the first days. Needs before: live Mollie account ready, date, page cache decision, OVH colleague.",
    "Opening day (the copy is already there): Password Protected plugin off and noindex off, robots.txt replaced (allow, sitemap), the old-address redirects in the root .htaccess switched on (lines marked OPENING DAY ONLY), theme go-live version, go-live files for shop and chat (logins off), then search engines: Search Console, sitemap, IndexNow, page cache.",
    "Rollback: put the old root .htaccess back (/sites/Archive-2026-10/root-backup/htaccess.before-merge) and index.html back. Takes minutes. Delete preview-wp.sql and preview-wp-config.php from the archive after opening day: they hold password hashes and an old database password."]),
  grp("Last steps, in this order",[
    chip("live","Done")+" 1. Rate limit on the shop's price request (api/calc.php): live and tested on 23 Oct, with the robot pop-up. Nothing left to do. The go-live version of index.html (go-live-ready/shop/) already contains it.",
    chip("todo")+" 1. Remove the test data in "+code("shop/api/orders/")+" (everything except .htaccess): 9 test orders, receipts, artwork, counters, logs. Check that the Dashboard is empty.",
    chip("todo")+" 2. Mollie live key in secrets.php and allow_live on in config.php, then one small real order that you refund yourself; check the PDF address."])])

HEALTH=[("clickclick.be/shop/","401","new shop is there and gated"),("clickclick.be/calculator-v2/","401","staff calculator is there and gated"),
 ("clickclick.be/chat/","401","chat is there and gated"),("clickclick.be/shop-v2/","404","old name, gone since the rename"),
 ("clickclick.be/flyers/ , /legal/ , /papers/ , /print-calculator/","404","archived on 22 Oct; redirects come at cutover"),
 ("clickclick.be/PaperStock/ , /Dashboard-MP-CC/ , /mail-analyse/ , /tools/","401","staff tools gated"),
 ("preview.clickclick.be/nl/","302","goes to the password page (the staging gate is on)"),
 ("clickclickgraphics.com","200","old OVH website until the move"),
 ("POST clickclick.be/shop/api/webhook.php","400 No payment ID","public on purpose, answers politely"),
 ("clickclick.be/shop/api/secrets.php","403","must be blocked from the web")]
rows="".join(f'<tr><td class="pth">{html.escape(a)}</td><td class="n">{html.escape(b)}</td><td>{html.escape(c)}</td></tr>' for a,b,c in HEALTH)

TREE = """ClickClick-Web-tools/              everything that is or becomes a web address
  README.md  SERVER-MAP.md          how deploy works, which address maps to which folder
  MIGRATION-clickclickgraphics.com.md   the domain move plan
  clickclick.be/
    shop-v2/        the new shop (README.txt, BACKUP.md inside); on the server it is called /shop/
    shop/           old copy of the old shop: archived on the server, local folder is history only
  go-live-ready/    final versions of the files that change at go-live (NOT uploaded)
    clickclick-chat/   the request chat
    paperstock/     paper stock tool
  build/internal-calculator-v2/   staff calculator + Dashboard (plain HTML, no build step)
  pricing/          the price Excel, Update prices.command, upload/, _backup/
  tiegel.be/        website texts, SEO and alt-text sheets (staging WordPress copy is old)
  ops/              helper scripts (server tree, deploy check, secrets template)
  brand/            logo, GT Pressura font
Claude-workfolder/Dashboard-CC/      MultiPress dashboard (own folder)
Claude-workfolder/Mail-analyse/      mail analysis tool (own repo)
Claude-workfolder/_Archive/          frozen copies, never uploaded"""

DECS=[("23 Oct 2026","E-mail signatures and mail addresses stay on clickclickgraphics.com (only the website address changes to clickclick.be). The old OVH mail servers (mx1, mx3) are removed from the DNS; the OVH MX Plan with the old archives stays until you decide."),
("23 Oct 2026","Urgent (express) orders can be switched off by the team with one button in the Dashboard when there is too much work. It applies to the shop and the chat."),
("23 Oct 2026","Rate limit on the shop price request: 5 free calculations per visitor per hour, then a pop-up with a robot icon and the button \"I'm not a robot\". Page cache: WP Super Cache, switched on after opening."),
("22 Oct 2026","Flyer prices: cost + 25 % (the lowest allowed), no minimum job price, only on papers 80 to 170 g/m2; about 29 % cheaper on average. Four new Products columns hold the rule; other products unchanged."),
("22 Oct 2026","New website goes live on clickclick.be, shop at /shop/. OVH move postponed. Old static pages cancelled, redirects kept. No test orders or receipts kept. Buttons to shop and chat open in a new tab."),
("21 Oct 2026","VAT 21 % for all products. Receipt PDFs kept for now. Alt text on every picture."),
("20 Oct 2026","The new shop is tested in Mollie test mode only; a live key is refused until allow_live is set."),
("17 Oct 2026","Brochures (stapled only) and folders are in the new shop and staff calculator."),
("14 to 17 Oct 2026","One Excel for all prices; online price = Multipress price minus 10 %; minimum job price €50."),
("20 Oct 2026","Final address of everything customers see: clickclickgraphics.com, no www; leave OVH completely."),
("19 Aug 2026","Chat put behind a login; shared password files moved above the web root (21 Aug).")]
decs="".join(f'<li><b>{a}</b> {b}</li>' for a,b in DECS)

CSS = open('mindmap.css').read()
HTML = f"""<title>Click Click Web Map</title>
<meta name="claude:viewport-fit-sides" content="cover">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@500;600;700&family=Barlow:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap">
<style>
{CSS}
</style>
<div class="wrap">
<header>
  <h1>Click Click web map</h1>
  <p class="lead">One page that explains how the whole web setup fits together: what the public sees, what the team uses, where the files are and what is still open. Written for someone coming back after a year.</p>
  <p class="asof"><b>Snapshot of 21 Oct 2026.</b> State at that date: the new website and the new shop are built and tested on private addresses. Go-live has not happened yet. If this page is older than a few months, run the health check at the bottom before trusting it.</p>
  <div class="legend"><span><i class="dot" style="background:var(--live)"></i>Live</span><span><i class="dot" style="background:var(--test)"></i>Test or preview</span><span><i class="dot" style="background:var(--todo)"></i>Work open</span><span><i class="dot" style="background:var(--gone)"></i>Retire later</span></div>
</header>
<section class="overview"><h2>The whole setup at a glance</h2><p class="hint">Click a branch to jump to its details.</p>
{SVG}
</section>
<section class="branches">{"".join(SECTIONS)}</section>
<section class="plain"><h2>Where things live on the Mac</h2><pre class="tree-pre">{html.escape(TREE)}</pre></section>
<section class="plain"><h2>Health check in one minute</h2><p class="hint">Open each address (or run curl) and compare with the expected answer. The numbers are the web server's replies: 401 = there but asks for a login, 403 = blocked, 200 = open, 301 and 302 = redirects.</p>
<div class="tw"><table><thead><tr><th>Address</th><th>Expected</th><th>Meaning</th></tr></thead><tbody>{rows}</tbody></table></div>
<p class="hint">Command: <code>curl -s -o /dev/null -w "%{{http_code}}\\n" https://clickclick.be/shop/</code></p></section>
<section class="plain"><h2>Decisions worth remembering</h2><ul class="decs">{decs}</ul></section>
<footer class="foot">Fuller notes: ClickClick-Web-tools/SERVER-MAP.md (every address and its login), shop-v2/README.txt, shop-v2/BACKUP.md (backup, retention, erasure), pricing/README.txt, MIGRATION-clickclickgraphics.com.md.</footer>
</div>
"""
open('site-map.html','w',encoding='utf-8').write(HTML); print(len(HTML),'chars')
