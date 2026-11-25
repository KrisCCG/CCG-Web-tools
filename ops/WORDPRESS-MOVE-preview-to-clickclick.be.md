# Moving the WordPress site from preview.clickclick.be to clickclick.be (runbook, 22 Oct 2026)

Goal: the WordPress site (24 pages, NL/FR/EN, theme "clickclick" 1.4.9) answers on `https://clickclick.be/` while the apps
(shop, chat, calculator-v2, PaperStock, Dashboard-MP-CC, mail-analyse, tools) keep working in their own folders.
The preview site is NOT touched: it stays as the rollback and as a staging copy.

## What was found (22 Oct 2026, read-only)

- Preview WordPress: files in `/sites/preview.clickclick.be/` (wp-admin, wp-content, wp-includes + root files).
  Database `eu3mfw_WP1528242` on `eu3mfw.myd.infomaniak.com`, table prefix `wp_1528242_`, charset utf8mb4, MariaDB 10.11, PHP 8.4. Not multisite.
- Address stored in WordPress: `https://preview.clickclick.be` (siteurl and home). Permalinks `/%postname%/`. Languages as folders: /nl/, /fr/, /en/ (Polylang).
- Plugins (active): Easy HTTPS Redirection, Instant Images, **Password Protected** (off at go-live), Polylang 3.9.1, SEOPress 10.3, Smush 4.3.5 (WebP on),
  WordPress Importer (can go), WP Armour, Yoast Duplicate Post. Inactive: VOD Infomaniak (delete).
- All 24 pages use relative links; the only full addresses are 474 picture addresses `https://preview.clickclick.be/wp-content/uploads/...` (handled by the search-replace).
- Target `/sites/clickclick.be/` today: the app folders + `.htaccess` (1379 B), `index.html` (2096 B), `robots.txt` (26 B, `Disallow: /`), `Logo.jpg`,
  `.user.ini`, `.infomaniak-maintenance.html`. No WordPress there, no `wp-*` files. `clickclick.be/` redirects to the old site now.

## Traps to handle

1. `index.html` in the target root hides WordPress (Apache serves it first): move it to the archive.
2. `robots.txt` says `Disallow: /`: replace with the go-live version (allow, sitemap line) or delete so SEOPress's virtual robots.txt answers.
3. Root `.htaccess`: one merged file. Needed parts: (a) the WordPress block from the PREVIEW `.htaccess`, (b) anything Smush added for WebP (look for "webp" in the preview .htaccess),
   (c) the 301 rules in `go-live-ready/root-htaccess-redirects.txt`, (d) the existing rules of the clickclick.be root that must stay (security headers etc. — read the old file first).
   DO NOT copy: the old redirect of `/` to clickclickgraphics.com, and any password/Basic-auth lines of the preview.
4. WordPress ignores real folders (`-d` / `-f` rewrite conditions), so /shop/, /chat/, /PaperStock/ ... keep working. Test each after the move.
5. Do not copy the preview's `wp-config.php` blindly: it must point to the COPY of the database (new name) and must not contain `WP_HOME`/`WP_SITEURL` with the preview address.
6. Smush WebP, SEOPress and Polylang store the address inside the database: serialized values, so ONLY use WP-CLI `search-replace` (or a tool that handles serialized data), never a plain text replace on the SQL dump.
7. Mail from WordPress: none used (no form plugin), nothing to change.

## Method A (recommended): SSH + WP-CLI

Needs: an SSH login to the hosting (the account has a user that works with SSH; test first), WP-CLI available (`wp --info`), and permission to create a database in the Infomaniak Manager.

0. Backups first: Infomaniak Manager backup of the preview site AND of the clickclick.be site; copy of the root `.htaccess`, `index.html`, `robots.txt` into `/sites/Archive-2026-10/root-backup/`.
1. Manager > Web & Domain > Hosting > Databases: create a NEW database (name it e.g. `eu3mfw_clickclick_be`), note user and password (do not paste them in chat).
2. SSH, dump and import (from the preview WordPress folder):
   `cd ~/sites/preview.clickclick.be && wp db export /tmp/preview-wp.sql`
   then import into the new database with the mysql client using the new credentials.
3. Copy the files (does not touch the preview): from `~/sites/preview.clickclick.be/` to `~/sites/clickclick.be/` copy `wp-admin`, `wp-content`, `wp-includes` and the root `*.php` files (`index.php`, `wp-*.php`, `xmlrpc.php`, ...), plus `license.txt`/`readme.html` are not needed. Use `cp -a` so dates and permissions stay.
   NOT: `wp-content/cache`, `wp-content/uploads` is copied (all pictures).
4. Edit the NEW `wp-config.php` in `~/sites/clickclick.be/`: database name, user, password of the new database. Keep the same salts (users stay logged in) or new ones (everyone logs out).
5. `index.html` and `robots.txt` of the target root: already moved to the archive (see trap 1 and 2). Merge `.htaccess` (trap 3).
6. Search-replace, DRY RUN first, from `~/sites/clickclick.be/`:
   `wp search-replace 'https://preview.clickclick.be' 'https://clickclick.be' --all-tables --dry-run`
   check the counts (about 474 in pages plus options), then run again without `--dry-run`. Then `wp cache flush` and `wp rewrite flush`.
7. In wp-admin on clickclick.be: Plugins > deactivate Password Protected; Settings > Reading > uncheck "Discourage search engines"; deactivate and delete WordPress Importer and VOD Infomaniak; Polylang > Settings: check the languages and the URL setting; SEOPress > check the sitemap and canonical addresses; Smush > check WebP still serves.
8. Theme, shop and chat go-live files (see `go-live-ready/README.txt`), root redirects, test list in `GO-LIVE-clickclick.be.md`.

## Method B (no SSH): plugin or manual

- Plugin: a migration plugin that handles serialized data (Duplicator or All-in-One WP Migration): export on the preview, install WordPress fresh on clickclick.be (Infomaniak installer into the root would clash with the apps: do NOT use the one-click installer on the root; upload only wp files by FileZilla), import. More clicks, bigger upload limits to watch.
- Manual: FileZilla download of `wp-content` (all pictures) and upload to the target; phpMyAdmin export of the preview database and import into a new database; search-replace with a plugin such as "Better Search Replace" (serialized-safe), never a text replace.

## Test list after the move (curl -u for gated areas)

`/` and `/nl/ /fr/ /en/` open; all 24 pages; pictures load (no `preview.` addresses left: view source); menu and language switch; footer links to /shop/ and /chat/ open in a new tab;
`/shop/`, `/chat/`, `/calculator-v2/`, `/PaperStock/`, `/Dashboard-MP-CC/`, `/mail-analyse/`, `/tools/` answer as before; `/wp-login.php` works;
`https://clickclick.be/robots.txt` allows search engines; `https://clickclick.be/sitemap_index.xml` (or SEOPress sitemap) exists; phone view; page speed measured again.

## Rollback

Put the old root `.htaccess`, `index.html` and `robots.txt` back from `/sites/Archive-2026-10/root-backup/` (clickclick.be/ redirects to the old site again) and delete the copied `wp-*` files from the root.
The preview site and its database were never changed, so nothing else has to be restored.
