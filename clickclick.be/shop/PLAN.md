# clickclick.be — getting the site into shape

Written 20 Aug 2026, from what this week established. Ordered by risk first,
then by how much future trouble each step prevents.

Anything marked **unverified** has not been confirmed against the server.

---

## Status — 21 Aug 2026

Every item below re-checked against the server over SFTP, which is the only
way to tell "deleted" from "gated" (see the note in Phase 1b).

| phase | done | open |
|---|---|---|
| 0 Verify | all | — |
| 1 Remove | 1, 2, 3, 4 | **5** (chunks — held on purpose) |
| 1b Tree findings | xlsx, nested duplicates, both `build.py`, clutter | `papers/text/`, two `README.txt`, space-grotesk fonts, `test-pdf.php` |
| 2 Close gaps | 7, 8, 9 | **6** (`.htpasswd` above root) |
| 3 Prevent | 10, 11 | 12 (needs 6) |
| 5 In flight | — | 13, 14, 15, 16 |

Also done 21 Aug, not in the original plan: the local restructure
(`Node-CCG` → `ClickClick-Web-tools`), the deploy mapping proven end-to-end,
`.claude/` added to the sftp ignore list (110 MB / 2,628 files that would
otherwise have uploaded), and `/legal/.htaccess` corrected — the server had
been serving a copy of `calculator/.htaccess` there.

---

## Phase 0 — Verify (15 min, no changes) — ~~DONE 20-21 Aug 2026~~

Full tree listed 20 Aug (1,420 lines, 241 dirs, 1,146 files, 80.4 MB) and every
questionable path status-checked. The script now lives in `ops/` and the
`-u` flag matters — without it a dropped connection loses the buffered tail,
which is what truncated the first two runs.

    python3 ops/sftp-tree.py --sizes --depth 8 \
      --config clickclick.be/shop/.vscode/sftp.json 2>&1 \
      | tee ops-trees/clickclick-tree-$(date +%Y-%m-%d-%H%M).txt

There is also a read-only check that compares every deployable file against the
server — run it after any upload:

    python3 ops/verify-deploy.py

    for u in \
      "/calculator/tiegel.be/wp-login.php" \
      "/calculator/Archive%2015maart2026/print-calculator-values.xlsx" \
      "/calculator/customer.html" \
      "/PaperStock/" ; do
      printf "%-58s %s\n" "$u" \
        "$(curl -s -o /dev/null -w '%{http_code}' "https://clickclick.be$u")"
    done

200 = open. 401 = gated. 403/404 = fine.

---

## Phase 1 — Remove what should not be there (30 min)

> **Items 1-4 completed and verified 20 Aug 2026.** Only item 5 remains,
> and it is deliberately held until the new build is uploaded.
> Re-confirmed over SFTP 21 Aug: `calculator/customer.html` and
> `customer.js` are genuinely gone. Over HTTP that path answers 401, not 404,
> because Apache checks authorisation before existence — the file is absent.

In this order. Each is a deletion in FileZilla; none needs a rebuild.

1. ~~**`calculator/tiegel.be/`**~~ — DONE 20 Aug 2026. A complete WordPress. `wp-login.php` answered
   200, so an unpatched login form is live inside the shop's backend directory.
   `wp-content/updraft/` is UpdraftPlus's backup folder and usually holds
   database dumps. **Download `wp-config.php` first** — the server's is 3663
   bytes against 3506 locally, so it is not the same file and holds the live DB
   credentials. Everything else is recoverable from
   `Node-CCG/Design new tiegel site/tiegel.be/`.

2. ~~**`calculator/Archive 15maart2026/`**~~ — DONE 20 Aug 2026. A 24 Feb build snapshot. Its
   `print-calculator-values.xlsx` sits outside the protected `data/`, so your
   raw paper costs, margins and click tiers may be downloadable. Local copies of
   that era exist in `Archive/clickclick-calculator/` and `Backup/files16MAR2026/`,
   but both are smaller than the server's 17,224 bytes — pull it first if you
   want that exact revision.

3. ~~**`calculator/customer.html` + `customer.js`**~~ — DONE, both 404. — the retired customer
   calculator. `calculator/.htaccess` line 104 exempts it from the gate by name.
   Source is parked in `internal-print-calculator/parked-customer-calculator/`.

4. ~~**`print-calculator/.htpasswd`**~~ — DONE. `/print-calculator/` and
   `/calculator/print.html` both still return 401 with credentials, so the
   gate correctly reads `flyers/.htpasswd`. — byte-identical to the flyers one, which is
   what every `AuthUserFile` actually points at. Its own `.htaccess` says so.

5. **`calculator/chunks/dateHelpers-*.js`** — **STILL OPEN**, on purpose.
   Confirmed 21 Aug: `calculator/chunks/` still holds 2 files. The live
   `print.js` still loads one of these. Upload `internal-print-calculator/dist/`
   first (the current build needs no chunks at all), confirm
   `/print-calculator/` works, then delete the whole `chunks/` folder. — two stale build outputs, 490 KB and
   501 KB. Uploads only add, so old hashed chunks accumulate. Delete both, then
   re-upload the current build so the one in use is present.

---

## Phase 1b — What the full tree listing found (20 Aug 2026)

The listing reached every directory. It found more of the same fault plus two
new categories. Ordered by severity.

### Act first

* ~~**`/calculator/print-calculator-values.xlsx`**~~ — **DONE 20 Aug 2026.** The
  updated `calculator/.htaccess` is live (server file went 5008 -> 5607 bytes)
  and the path now returns 403 rather than 200. The rule denies `.xlsx`
  throughout `/calculator/`, so a stray copy anywhere in that folder is covered
  by default — the category is closed, not just this instance. Stray file also
  deleted.
* ~~**`/flyers/papers/.htpasswd`**~~ — **DONE**, removed with the parent tree.

### Nested duplicates — the same upload fault, three more times

* ~~`/flyers/papers/`~~ — **DONE 20 Aug 2026**, verified 404 with real
  credentials. Took the nested `/flyers/papers/flyers/`, the fourth `.htpasswd`
  copy, and that tree's `build.py` + `text/` with it.
* ~~`/legal/legal/`~~ — **DONE 20 Aug 2026**, verified 404.

NOTE ON VERIFYING ANYTHING UNDER /flyers/, /tools/, /chat/ OR /print-calculator/:
Apache checks authorisation BEFORE existence, so an unauthenticated request —
or one with deliberately wrong credentials — returns 401 whether the file is
there or not. Only a real login distinguishes deleted from present:

    curl -s -o /dev/null -w '%{http_code}\n' -u CCG https://clickclick.be<path>

### Build tools that were never meant to be uploaded — partly done

Neither `papers/.htaccess` nor `legal/.htaccess` blocks `.py` or `.txt`, so these
are readable. Status re-checked over SFTP 21 Aug:

* ~~`/papers/build.py`~~ — **DONE**, gone.
* ~~`/legal/build.py`~~ (37 KB — the full source of the terms) — **DONE**, gone.
* ~~`/papers/text/{en,fr,nl}.txt`~~ — **DONE 21 Aug 2026**, deleted with the
  empty `text/` folder. The local copies stay: they are the build sources.
* ~~`/papers/img/README.txt` and `/flyers/README.txt`~~ — **DONE 21 Aug 2026.**
  `img/` kept, empty, awaiting the twelve paper photos.

The Phase 3 ignore list is in place (`**/*.py`, `**/*.txt`, `papers/text`), so
these cannot come back — but the ignore list does not remove what is already
on the server. These four need deleting by hand.

### Clutter

* ~~`/calculator/IMG_0267.jpeg`, `Logo.jpg`, `log-calc.php`, `BACKUP.md`,
  `INCIDENT.md`, `README.txt`~~ — **all DONE 20 Aug 2026, verified 404.**
  `/calculator/` now holds only what runs: the PHP endpoints, `.htaccess`,
  `.user.ini`, the built `print.html`/`print.js`, and the
  `admin/ chunks/ data/ font/ lib/ orders/` folders.
* ~~`/chat/fonts/space-grotesk-*.woff2` and `/legal/fonts/space-grotesk-*.woff2`~~
  — **DONE 21 Aug 2026** (4 files). Checked first that no page still loads
  them — the three remaining "Space Grotesk" mentions are comments recording
  the replacement. Every fonts/ folder on the server is GT Pressura only.
* ~~`/test-pdf.php` at the site root~~ — **DONE 21 Aug 2026**, gone from the
  server root listing. It was a leftover probe checking whether `fpdf.php`,
  `invoice.php` and `orders/` resolved at the **root** — they live in
  `/calculator/`, so it had been failing and leaking `PHP version: 8.2.31`
  plus the absolute server path the whole time.

### Confirmed absent

* No `calculator-test/`. This is why `/print-calculator-test/` showed a 404 in
  its iframe: it points at a folder that was never created.

---

## Phase 2 — Close the gaps that remain (1 h)

6. ~~**Move `.htpasswd` above the web root.**~~ — **DONE 21 Aug 2026**, and
   wider than planned: all THREE credential files moved, not just the shared
   one — `~/.htpasswd-ccg` (shared CCG), `~/.htpasswd-paperstock`,
   `~/.htpasswd-mpcc`. Twelve `AuthUserFile` lines repointed across nine
   `.htaccess` files (flyers, tools, print-calculator, calculator ×3,
   calculator/admin, chat, chat-api ×2, PaperStock, Dashboard-MP-CC).
   Each gate deployed one at a time and probed with **wrong** credentials —
   401 = password file readable, 500 = broken path (the distinction
   KNOW-HOW-2026-08-17 documents). After deleting the old in-tree files,
   every gate still answered 401 to wrong creds — only the new paths can
   produce that. Local `.htpasswd` copies removed from all deploy folders;
   the hashes are kept in `ops/server-backups/htpasswd-2026-08-21/`.
   **The 19 Aug outage class is now structurally impossible.** Final check
   done by the user the same day: real-password login succeeds on the gated
   pages, and `/papers/` + `/legal/` open without a prompt — the moved files
   carry working hashes.

7. ~~**Add a blanket `.xlsx` deny to `calculator/.htaccess`**~~ — **DONE
   20 Aug 2026.** Server `.htaccess` went 5008 -> 5607 bytes and
   `/calculator/print-calculator-values.xlsx` now returns 403. The rule covers
   the whole folder, so the category is closed, not just the one instance.

8. ~~**Drop the `customer.html` exemption**~~ — **DONE 20 Aug 2026**, in the same
   `.htaccess` replacement. The file itself is gone too (SFTP-confirmed 21 Aug).

9. ~~**Verify `/PaperStock/`.**~~ — **RESOLVED 21 Aug 2026.** The gate is real:
   `PaperStock/.htpasswd` exists on the server (38 bytes) and the path returns
   401. The June observation of an open page no longer holds.

---

## Phase 3 — Stop it happening again (30 min)

The three stray uploads — `internal-print-calculator/`, `calculator/tiegel.be/`,
`deploy/` — all have one cause: the SFTP client reproducing a local path because
the workspace root was wrong.

10. ~~**Open the project folder itself as the VS Code workspace root.**~~ —
    **DONE 21 Aug 2026 and proven.** The unit now lives at
    `ClickClick-Web-tools/clickclick.be/shop/`. A round-trip test
    uploaded `flyers/_deploy-test.txt`, confirmed it landed at exactly
    `/flyers/_deploy-test.txt`, confirmed nothing stray appeared at the server
    root, then removed it. 60 of 63 deployable files are byte-identical to the
    server; the 3 exceptions are `.DS_Store` files (correctly ignored) and
    `calculator-test/.htaccess` (item 13).

    A structural safety net now backs this up: `sftp.json` exists **only**
    inside the two deploy units, so opening a parent folder as the root finds
    no config and cannot upload at all.

11. ~~**Add an `ignore` list to `.vscode/sftp.json`**~~ — **DONE**, 23 patterns
    live. Extended 21 Aug with `.claude` and `.gitignore`: `.claude/` held a
    Claude Code worktree with full copies of `multipress-extract` and
    `clickclick-calculator`, **110 MB across 2,628 files**, which a folder
    upload would have pushed to the server root.

    Also 21 Aug: `tools` **removed** from the ignore list. It had been added
    when `sftp-tree.py` lived inside `tools/`; that script now lives in `ops/`,
    and the ignore was silently blocking legitimate updates to the `/tools/`
    hub — the GT Pressura restyle of 18 Aug never reached the server because of
    it.

12. **Never put `.htpasswd` in an uploaded set.** Partly covered — `**/.htpasswd`
    is in the ignore list, so an upload cannot carry one. The structural fix is
    Phase 2 item 6, still open.

---

## Phase 4 — Name things so they stop confusing people (2 h, optional)

Today "the internal calculator" is two folders with three similar names:

    print-calculator/          the wrapper you open
    calculator/print.html      the app it displays
    internal-print-calculator  the source project (now deleted from the server)

Proposal — one folder, one name:

    internal/                  wrapper + built app together
    calculator/                pricing and payment backend ONLY

That means: build with `--base=/internal/`, move the wrapper's `index.html` in
beside it, repoint the `/tools/` link, and keep a redirect at
`/print-calculator/` for anyone with a bookmark. Do this only after Phases 1–3.

---

## Phase 5 — Finish what is in flight

13. **The test deploy — STILL OPEN.** Re-confirmed 21 Aug: still no
    `calculator-test/` on the server. There is no `calculator-test/` on the server, which is
    why `/print-calculator-test/` showed WordPress's 404 — its iframe pointed at
    nothing. Upload `internal-print-calculator/dist-test/*` into a new
    `calculator-test/`, **with `calculator-test/.htaccess`**, which carries both
    `RewriteEngine Off` and the password gate.

14. **Set `long_size_uplift`** in the Settings sheet. It is a 25% placeholder,
    and every tall-poster price quoted so far includes that guess.

15. **The twelve paper photos** for `/papers/` — **STILL OPEN**. Confirmed
    21 Aug: `papers/img/` on the server holds only `README.txt`. Names and a
    shot list are in it. Drop them in, upload, no rebuild.

16. **Click tiers are the last real divergence** between the two calculators.
    The shop reads the `Click Tiers` sheets from the spreadsheet; the internal
    calculator keeps its own copy in `src/utils/constants.js`. A change to the
    Konica lease reaches one and not the other.

---

## The target shape

    clickclick.be/
      flyers/            the shop            customer-facing, gated while in test
      papers/            paper guide         public
      legal/             legal & terms       public
      chat/  chat-api/   the assistant       gated
      calculator/        pricing + payment   backend, mixed gating
      internal/          internal calculator staff only          (Phase 4)
      tools/             staff hub           staff only
      PaperStock/        stock tool          staff only          (verify)
      Dashboard-MP-CC/   MultiPress dash     staff only

Everything else currently on the server is either build output that belongs
inside one of these, or arrived by accident.

Keep `SITE-MAP.md` beside this file current as things move.


---

## Where things stood at the end of 20 Aug 2026

### Done and verified by status code

* `calculator/tiegel.be/` — a full WordPress with a LIVE `wp-login.php` and an
  UpdraftPlus backup folder, inside the shop's backend directory. Deleted.
* `internal-print-calculator/` — the source project, uploaded whole by mistake,
  carrying a second UNGATED copy of `api-dashboard.php` (the orders API).
* `calculator/Archive 15maart2026/` and the stray
  `calculator/print-calculator-values.xlsx` — cost prices and margins, readable
  at two separate paths.
* `calculator/customer.html` + `customer.js` — the retired customer calculator,
  publicly readable with a "Continue to order" button.
* `/flyers/papers/` (+ its nested `/flyers/papers/flyers/` and a 4th `.htpasswd`)
  and `/legal/legal/` — duplicate trees from misrouted uploads.
* `print-calculator/.htpasswd` — redundant.
* `calculator/BACKUP.md`, `INCIDENT.md`, `README.txt`, `IMG_0267.jpeg`,
  `Logo.jpg`, `log-calc.php` — clutter.
* `calculator/.htaccess` replaced (5008 -> 5607 bytes): `.xlsx` denied
  throughout the folder, and the `customer.html` exemption closed. The
  spreadsheet exposure is now prevented by rule, not by having spotted it.
* `.vscode/sftp.json` ignore list extended to 23 patterns — `**/*.py`,
  `**/*.md`, `**/*.txt`, `papers/text`, `lib`, `deploy`, `serve.json`.
  **Needs `Developer: Reload Window` before the next upload.**

`/calculator/` now contains only what runs.

### Next, in order — revised 21 Aug 2026

1. ~~**Delete the last build tooling.**~~ — **DONE 21 Aug 2026.** `/papers/`
   now holds only what it serves: `.htaccess`, `fonts/`, `img/` (empty),
   `index.html`, `fr.html`, `nl.html`.
2. ~~**Open the deploy unit as the workspace root**~~ — **DONE and proven.**
3. **Upload `build/internal-calculator/dist/`**, confirm `/print-calculator/`
   still loads, THEN delete `/calculator/chunks/` (2 files, ~1 MB).
4. ~~**Move `.htpasswd` above the web root**~~ — **DONE 21 Aug 2026**, see
   Phase 2 item 6. Zero `.htpasswd` files remain under the web root or in any
   deploy folder.
5. **Upload `tools/`** — the GT Pressura restyle has never reached the server,
   and `tools/fonts/` does not exist there at all, so `/tools/` is currently
   falling back to system-ui with a broken font reference.
6. ~~**Remaining clutter**~~ — **all DONE 21 Aug 2026.**
7. ~~**The root WordPress.**~~ — **DONE 21 Aug 2026.** Confirmed used only
   for the homepage redirect, which turned out to live in the root `.htaccess`
   itself (`RewriteRule ^$ ... [R=301,L]`), not in WordPress. Backed up
   `wp-config.php` + root files + a plugin/theme inventory to
   `ops/server-backups/wordpress-root-2026-08-21/`, swapped in a minimal root
   `.htaccess` (redirect + DEFLATE + git-block + `-Indexes`; source of truth:
   `clickclick.be/root/.htaccess`), verified all 12 paths unchanged, then
   deleted 4,867 files / 623 dirs. `wp-login.php`, `readme.html`,
   `license.txt`, `wp-json/` all 404 now — and 404s are plain Apache again
   instead of WordPress's 70 KB themed page. The DB still exists at
   Infomaniak, untouched; credentials are in the backed-up `wp-config.php`.
8. ~~**`chat-api/lib/shop.php`**~~ — **DONE 21 Aug 2026.** Deployed; both
   units are now byte-identical to the server. The change adds the tall
   poster (LP) and mirrors two calculator rules into the bot:
   `SHOP_SIZE_BY_PRODUCT` (matches `ITEM_SIZE_BY_PRODUCT` in
   `calculator/lib/items.php`) and `SHOP_PAPER_BY_SIZE` (matches
   `SIZE_PAPERS` in `calc.php`). Both were checked against their sources
   before deploying. **These are hand-kept mirrors — a change to either
   calculator rule must be copied across.**

### Still unverified

* `long_size_uplift` (item 14) and the click-tier divergence (item 16) — both
  are spreadsheet/source questions that cannot be checked from the server.
