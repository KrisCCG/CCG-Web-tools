# clickclick.be — URL → folder → gate

Status codes verified live 21 Aug 2026, 16:30. Re-verify with
`ops/sftp-tree.py` plus `curl` after any deploy.

## Deploy mapping — proven 21 Aug 2026

Round-trip test through the `shop` deploy unit:
`flyers/_deploy-test.txt` uploaded → landed at exactly
`clickclick.be/flyers/_deploy-test.txt`, nothing stray appeared at the server
root (`/flyer-calculator`, `/clickclick.be`, `/deploy`, `/_deploy-test.txt` all
absent), test file removed. **The 1:1 mapping is correct.**

Full comparison of every deployable file against the server:

| unit | deployable | identical on server | differs | absent |
|---|---|---|---|---|
| flyer-calculator | 64 | 59 | 1 | 4 |
| clickclick-chat | 33 | 32 | 1 | 0 |

Everything unaccounted for is explained below.

### Fixed during the check

**`.claude/` was not in either `sftp.json` ignore list.** It holds a Claude Code
worktree containing full copies of `multipress-extract` and
`clickclick-calculator` — **110 MB across 2,628 files** that a plain
"upload folder" would have pushed to the server root. Added `.claude` and
`.gitignore` to the ignore list of both deploy units. Also deleted the
`localhost` stub at `legal/.vscode/sftp.json`.

### Fixed by deploy — 21 Aug 2026

**`/legal/.htaccess` was the wrong file.** The server held a byte-identical copy
of `calculator/.htaccess` (5,607 bytes) instead of `legal/.htaccess` (1,648) —
an earlier upload had put the calculator's config into `/legal/`.
`/papers/.htaccess` was checked and was correct.

It was serving 200 only by luck: the correct file's own comment calls
`RewriteEngine Off` "the single most important line", the misplaced file did not
contain it, and the pages worked because the root WordPress rewrite skips files
that exist on disk.

Deployed the correct file through the `shop` unit. Verified after:
server copy is byte-identical to local, `RewriteEngine Off` present at line 12,
and `/legal/`, `/legal/fr.html`, `/legal/nl.html` render at full size
(23,356 / 25,464 / 24,191 bytes). `/papers/`, `/flyers/` and `/calculator/calc.php`
were probed before and after and were unchanged. Previous server bytes kept at
`ops/server-backups/legal.htaccess.server-2026-08-21`.

### Still open

Two, both benign:

* `calculator-test/.htaccess` has never landed — the known unfinished
  `/calculator-test/` deploy. It is 404, so nothing is exposed.

---

## Live

| URL | gate | deploy unit | local path |
|---|---|---|---|
| `/flyers/` | 401 | flyer-calculator | `clickclick.be/shop/flyers/` |
| `/calculator/` | mixed | flyer-calculator | `clickclick.be/shop/calculator/` |
| `/calculator/print.html` | 401 | flyer-calculator | built from `build/internal-calculator/` |
| `/print-calculator/` | 401 | flyer-calculator | `clickclick.be/shop/print-calculator/` |
| `/print-calculator-test/` | 401 | flyer-calculator | `clickclick.be/shop/print-calculator-test/` |
| `/legal/` | **public** | flyer-calculator | `clickclick.be/shop/legal/` |
| `/papers/` | **public** | flyer-calculator | `clickclick.be/shop/papers/` |
| `/tools/` | 401 | flyer-calculator | `clickclick.be/shop/tools/` |
| `/chat/` | 401 | clickclick-chat | `clickclick.be/clickclick-chat/chat/` |
| `/chat-api/` | 403 | clickclick-chat | `clickclick.be/clickclick-chat/chat-api/` |
| `/PaperStock/` | 401 | paper-stock | `clickclick.be/paperstock/PaperStock/` |
| `/Dashboard-MP-CC/` | 401 | — | built from `~/Desktop/Claude-workfolder/Dashboard-CC/` |
| `/mail-analyse/` | 401 | mail-analyse | `~/Desktop/Claude-workfolder/Mail-analyse/mail-analyse/` |
| `/mail-analyse-api/` | 401 | mail-analyse | `~/Desktop/Claude-workfolder/Mail-analyse/mail-analyse-api/` |

`/Dashboard-MP-CC/` is served from clickclick.be but its source is the
standalone `Dashboard-CC` folder (a MultiPress export), deliberately kept
outside this tree. Its deploy step lives there.

`/mail-analyse/` (added 19 Sep 2026) is the internal email-to-quote-brief
tool. Like Dashboard-CC, its deploy unit lives outside this tree, in
`~/Desktop/Claude-workfolder/Mail-analyse/` — own git repo, own sftp.json,
architecture forked from clickclick-chat (nothing shared at runtime). Both
paths gate on the shared `.htpasswd-ccg`, realm "Mail analyse".

**`/PaperStock/` is capital P and capital S.** `/stock/` is a 404. The local
folder is named to match — do not rename it.

## Known open on the server

Nothing. The server holds only what runs. (Build-source text files,
README.txt files and the superseded space-grotesk fonts all deleted 21 Aug;
every fonts/ folder now carries GT Pressura only.)

**Credentials now live above the web root** (moved 21 Aug 2026):
`~/.htpasswd-ccg` (shared: flyers, tools, print-calculator, calculator,
admin, chat, chat-api), `~/.htpasswd-paperstock`, `~/.htpasswd-mpcc`. No
`.htpasswd` exists under the web root or in any deploy folder any more — the
19 Aug outage class (deploy mirror overwrites/deletes the password file) is
structurally impossible. To rotate a password, replace the file above the
web root; no deploy is involved. Verified end-to-end 21 Aug: wrong creds →
401 on all nine gates, real login succeeds, public pages stay open.

The root WordPress is **gone** — removed 21 Aug 2026. The bare `/` still 301s
to clickclickgraphics.com: that redirect always lived in the root `.htaccess`,
not in WordPress. The root now holds only the ten app folders plus
`.htaccess`, `index.html` + `Logo.jpg` (fallback page), `robots.txt`,
`.user.ini` and `.infomaniak-maintenance.html`. The root `.htaccess` source of
truth is `clickclick.be/root/.htaccess` in this tree — the root is not a
routine deploy target, so it is kept outside the deploy units on purpose.

404s are now plain Apache (≈1 KB), not WordPress's 70 KB themed page — a 404
finally proves absence again (under gated paths, authorisation still answers
first; use SFTP to prove absence there).


## Fixed 20–21 Aug 2026

| path | was | now |
|---|---|---|
| `/calculator/print-calculator-values.xlsx` | 200 — pricing sheet public | 403 |
| `/calculator/customer.html` | 200 | 401 |
| `/calculator/customer.js` | 200 | 404 |
| `/legal/legal/` | 200 | 404 |
| `calculator/tiegel.be/` | full WordPress | 404 |
| `calculator/Archive 15maart2026/` | exposed pricing sheet | 404 |
| `/print-calculator-test/` | 401, iframed a folder that never existed | 404, deleted 21 Aug |
| `/calculator/print.js` | **200 — 532 KB bundle with click tiers, paper costs and margins** | 401, gated 21 Aug |
| `/calculator/chunks/` | 992 KB of dead build output, also public | 404, deleted 21 Aug |
| `/test-pdf.php` | 200, leaked PHP 8.2.31 + absolute server path | 404, deleted 21 Aug |
| `chat-api/lib/shop.php` | the 17 Aug build — no tall poster, no per-product size rules | 21 Aug build, deployed |
| root WordPress (7.1: `wp-admin/`, `wp-includes/`, `wp-content/`, 16 `wp-*.php`, `xmlrpc.php`, `readme.html`, `license.txt`) | live login form at `/wp-login.php`, version exposed, themed 404s masking absence | **4,867 files / 623 dirs deleted 21 Aug** — root `.htaccess` rewritten minimal, redirect kept, all apps unchanged |
| `/calculator/print.html` + `print.js` | the **6 May** build | 20 Aug build, deployed 21 Aug |
| `/deploy/`, `/Clickclick-shop/` | stray uploads | 404 |

## Still to do

2. Delete stale build chunks: `calculator/chunks/dateHelpers-*.js` (2 files,
   ~1 MB) and `Dashboard-MP-CC/assets/` (7 hashed bundles, ~2.8 MB, one in use).
3. Rotate the CCG htpasswd — deferred after the password went public.
4. Confirm `/Dashboard-MP-CC/` and `/PaperStock/` with `curl -u`.
