GO-LIVE READY FILES  (prepared 22 Oct 2026)  --  DO NOT UPLOAD BEFORE THE CUTOVER
These are copies of the live files with ONLY the go-live changes. The files in clickclick.be/ stay the test versions.
Plan and order: ../GO-LIVE-clickclick.be.md

folder here            goes to (on the server, AFTER shop-v2 has been renamed to shop)     what changed
shop/.htaccess         /sites/clickclick.be/shop/.htaccess          login gate removed, error page path /shop/ (staff parts keep their own login)
shop/index.html        /sites/clickclick.be/shop/index.html         red TEST bar removed, paper guide link relative
shop/lib/site-chrome/site-chrome.js   /shop/lib/site-chrome/        SITE = '' (WordPress is on the same domain)
chat/.htaccess         /sites/clickclick.be/chat/.htaccess          login gate removed
chat/lib/site-chrome/site-chrome.js   /chat/lib/site-chrome/        SITE = ''
chat-api/.htaccess     /sites/clickclick.be/chat-api/.htaccess      login gate removed (cron scripts keep theirs)
chat-api/lib/shop.php  /sites/clickclick.be/chat-api/lib/shop.php   SHOP_DIR_NAME = 'shop'
theme/clickclick-1.4.9.zip   WordPress > Appearance > Themes > Add > Upload      footer + buttons link to /shop/ instead of /shop-v2/
root-htaccess-redirects.txt  root .htaccess of clickclick.be        301 for the old /legal/ and /papers/ pages
                             (the old folders /legal/ and /papers/ are cancelled = deleted at cutover, after a backup)

NOT in this folder (done by hand at cutover): live Mollie key + allow_live in shop/api, Task Scheduler addresses,
WordPress files + database copy and the address search-replace, robots.txt, the rate limit on shop/api/calc.php (last).
Order warning: upload chat-api/lib/shop.php and rename the shop folder in the same minute, otherwise the chat looks for a folder that does not exist.
