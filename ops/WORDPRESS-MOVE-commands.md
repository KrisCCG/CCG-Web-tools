# WordPress move: the commands (SSH), 22 Oct 2026

**MODE (decided 22 Oct 2026): STAGING COPY.** The site is copied to clickclick.be, the preview stays untouched, and the copy stays
CLOSED to the public: the Password Protected plugin stays ON, "discourage search engines" stays ON, and `robots.txt` stays `Disallow: /`.
Opening to the public is a later, separate step (section "Opening day" at the end). Blocks 0 to 8 below are written for this staging copy.

Run these one block at a time in the SSH session, read the output before the next block.
Nothing here changes the preview site. Passwords are NEVER typed on a command line: the mysql client and `nano` ask or let you type them.
Everything marked STOP: if the output is not what is described, stop and send me the output.

## Block -1 — getting a shell on the Infomaniak hosting

Known from the VS Code settings: SFTP host `eu3mfw.ftp.infomaniak.com`, port 22, user `eu3mfw_system` (this one is SFTP; SSH must be switched on for it).
1. Infomaniak Manager > Web & Domain > Hosting > the hosting of clickclick.be > **FTP / SSH** (menu on the left).
2. Open the user (or create a separate FTP/SSH user for this job), tick **Enable SSH** (shell bash), save. The same page shows the **SSH server address** (my guess: `eu3mfw.ssh.hosting-ik.com`; use what the Manager shows).
3. Mac Terminal: `ssh eu3mfw_system@<SSH server address>` , answer `yes` to the question about the fingerprint, type the password (nothing shows while typing).
4. At the prompt type `pwd`. Expect `/home/clients/bb59b9bb68f9d5f0bbd421f23f3ae8ed`. Then `exit` leaves again.
"This service allows sftp connections only" means SSH is not enabled for that user yet (step 2).

## Block 0 — variables and checks

```bash
H=/home/clients/bb59b9bb68f9d5f0bbd421f23f3ae8ed
P=$H/sites/preview.clickclick.be      # WordPress now
T=$H/sites/clickclick.be              # target: the clickclick.be web root
A=$H/sites/Archive-2026-10            # archive, outside the web folders
echo "$P"; ls "$P" | head -30
echo "--- target root"; ls -a "$T"
command -v wp || echo "no wp-cli in PATH"; wp --info 2>/dev/null | head -4; php -v | head -1
du -sh "$P/wp-content/uploads" "$P/wp-content/themes" "$P/wp-content/plugins" 2>/dev/null
df -h "$H" | tail -1
```
Expect: `wp-admin wp-content wp-includes wp-config.php index.php ...` in the preview; no `wp-*` and no `index.php` in the target; PHP 8.4; free disk space larger than the sizes above.
STOP if the target root already has `wp-admin`, `wp-content` or `index.php`.
If `wp` is not found: `ls ~/bin ~/*.phar 2>/dev/null` and tell me; the alternative is `php wp-cli.phar` from a downloaded phar.

## Block 1 — backups (nothing is deleted)

```bash
mkdir -p "$A/root-backup"
cp -a "$T/.htaccess" "$T/index.html" "$T/robots.txt" "$A/root-backup/"
[ -f "$T/Logo.jpg" ] && cp -a "$T/Logo.jpg" "$A/root-backup/"
cp -a "$P/.htaccess" "$A/root-backup/preview.htaccess"
cp -a "$P/wp-config.php" "$A/root-backup/preview-wp-config.php"
chmod 600 "$A/root-backup/preview-wp-config.php"
wp --path="$P" db export "$A/root-backup/preview-wp.sql" && ls -la "$A/root-backup"
```
Expect the `.sql` file (some MB) and the copies. The folder is outside the web root. (The sql file holds user password hashes: keep it in the archive only.)

## Block 2 — show the two .htaccess files (send me the output)

```bash
echo "=== OLD ROOT .htaccess (clickclick.be)"; cat "$T/.htaccess"
echo; echo "=== PREVIEW .htaccess"; cat "$P/.htaccess"
```
Paste both outputs in the chat (they hold no passwords; hide an `AuthUserFile` path if you want). I write the merged file for you. Do not continue to block 6 before that.

## Block 3 — the new database (Manager) and the import

In the Infomaniak Manager: Web & Domain > Hosting > Databases > Add a database. Note the database host, name and user (keep the password for yourself). Then:

```bash
DBHOST=...        # host of the NEW database, e.g. eu3mfw.myd.infomaniak.com
DBNAME=...        # new database name
DBUSER=...        # new database user
mysql -h "$DBHOST" -u "$DBUSER" -p "$DBNAME" < "$A/root-backup/preview-wp.sql"    # asks the password
mysql -h "$DBHOST" -u "$DBUSER" -p "$DBNAME" -e "SHOW TABLES;" | head -5; 
```
Expect tables starting with `wp_1528242_`. (The old database `eu3mfw_WP1528242` is only read, never written.)

## Block 4 — copy the WordPress files (the preview stays as it is)

```bash
cd "$P" && ls
cp -a wp-admin wp-content wp-includes "$T/"
cp -a index.php wp-activate.php wp-blog-header.php wp-comments-post.php wp-cron.php wp-links-opml.php wp-load.php wp-login.php wp-mail.php wp-settings.php wp-signup.php wp-trackback.php xmlrpc.php wp-config.php "$T/" 
ls "$T" | grep -E '^wp-|^index.php|^xmlrpc' 
```
Expect the `wp-*` entries now in the target. (`ls` first shows the real file list: if the preview has other root files, tell me.) Do NOT copy `.htaccess`, `.user.ini`, `robots.txt`, `index.html` here.

## Block 5 — point the copied wp-config.php at the NEW database

```bash
nano "$T/wp-config.php"
```
Change `DB_NAME`, `DB_USER`, `DB_PASSWORD` and `DB_HOST` to the new ones (type the password in the editor). Check that there is NO line with `WP_HOME` or `WP_SITEURL` (delete them if present). Save with Ctrl+O, Enter, exit with Ctrl+X. Then:

```bash
chmod 640 "$T/wp-config.php"
wp --path="$T" db check | tail -3          # expect: OK or all tables OK
wp --path="$T" option get siteurl          # still the preview address (database copy)
```

## Block 6 — the root: index.html, robots.txt, .htaccess  (only after I sent you the merged .htaccess)

```bash
mv "$T/index.html" "$A/root-backup/index.html.old"      # backup already copied in block 1; this removes it from the root
# robots.txt STAYS as it is (Disallow: /) while the copy is closed
cp -a "$T/.htaccess" "$A/root-backup/htaccess.before-merge"
# the merged text is in go-live-ready/root.htaccess-staging (22 Oct); paste it with the "cat > ... <<EOF" block given in the chat
```
From this moment `https://clickclick.be/` is served by WordPress (the old redirect of `/` to the old site is gone). The Password Protected plugin is active, so visitors only see the password page. The apps (/shop/, /chat/, ...) are not affected.

## Block 7 — replace the address in the database (dry run first!)

```bash
cd "$T"
wp search-replace 'https://preview.clickclick.be' 'https://clickclick.be' --all-tables --dry-run
```
Read the table: about 474 in the posts table plus a few in options. STOP if it shows 0 or thousands.
```bash
wp search-replace 'https://preview.clickclick.be' 'https://clickclick.be' --all-tables
wp option get siteurl; wp option get home      # both https://clickclick.be
wp rewrite flush; wp cache flush
```

## Block 8 — check from outside (any terminal), then in wp-admin

```bash
for u in "" nl/ fr/ en/ nl/drukken/ wp-login.php shop/ chat/ calculator-v2/ robots.txt; do printf "%-16s " "/$u"; curl -s -o /dev/null -w "%{http_code} %{redirect_url}\n" "https://clickclick.be/$u"; done
```
Expect: the WordPress pages answer 200 or 302 to the password page; shop/chat/calculator-v2 still 401 (login); robots.txt 200.
Then in `https://clickclick.be/wp-admin/` (log in with your WordPress account, same as the preview): LEAVE Password Protected active and "Discourage search engines" ticked. Check Polylang (languages, language URLs), SEOPress, Smush WebP, and click through the 24 pages: pictures must come from `https://clickclick.be/wp-content/uploads/...` (view source: no `preview.clickclick.be` left). WordPress Importer and VOD Infomaniak can be deleted now or at opening day.

## Opening day (later, separate from this copy)

Only then: in wp-admin deactivate Password Protected and untick "Discourage search engines"; replace `robots.txt` (allow, sitemap line) or delete it so SEOPress answers;
add `go-live-ready/root-htaccess-redirects.txt` to the root `.htaccess`; upload the go-live files for shop and chat (`go-live-ready/`); theme go-live version; Search Console, IndexNow, page cache. See `GO-LIVE-clickclick.be.md`.
If the content on the preview changed after the copy, repeat blocks 1, 3 and 7 (new export, import into the same database after emptying it, search-replace) or edit on clickclick.be from then on and leave the preview alone.

## Rollback (any time, takes a minute)

```bash
cp -a "$A/root-backup/htaccess.before-merge" "$T/.htaccess"
mv "$A/root-backup/index.html.old" "$T/index.html"
mv "$A/root-backup/robots.txt.old" "$T/robots.txt"
# optional: remove the copied WordPress files from the root
cd "$T" && rm -rf wp-admin wp-content wp-includes && rm -f index.php wp-*.php xmlrpc.php
```
(Check the path in `cd "$T"` before `rm -rf`: it must be the clickclick.be folder. The preview and its database were never changed.)
