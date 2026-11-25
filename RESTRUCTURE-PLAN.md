# Node-CCG → ClickClick-Web-tools

Freeze the current `Node-CCG` folder and rebuild it as four scoped folders
whose shape tells you what is online, where it lives, and how to deploy it.

Written 20 Aug 2026, revised with the naming and repo decisions.
Every status code below was checked live today.

---

## STATUS — local restructure DONE, 21 Aug 2026

**Executed.** `Node-CCG` is gone; the four folders exist and are verified.
`~/Desktop/Claude-workfolder/` now holds `ClickClick-Web-tools/` (753 MB),
`Dashboard-CC/` (1.2 GB), `ClickClick-Business/` (308 KB) and
`_Archive/Node-CCG-2026-08-21/` (2.2 GB).

What was done, in order:

1. Froze `Node-CCG` to `_Archive/Node-CCG-2026-08-21` — verified identical,
   35,638 files and 2.6 GB on both sides.
2. Committed and pushed all three repos. All are private on GitHub, so the
   tracked pricing spreadsheet is not publicly exposed.
   `shop` a017333, `clickclick-chat` 3810ad4,
   `tiegel-request-bot` already current.
3. Retired the outer `Node-CCG/.git` — its 11,107 files were never committed.
4. Built the tree and moved every item per §2.
5. Moved `sftp-tree.py` out of `shop/tools/` into `ops/` and
   committed the removal (79e1e40) — `tools/` is the deploy source for
   `/tools/`, so an ops script cannot live in it.
6. Wrote `README.md` and `SERVER-MAP.md`; kept the old project README as
   `CCG-CONTEXT.md`.
7. Deleted `Node-CCG` after confirming every remaining item existed in the
   archive, and pruned `node_modules` from the archive copy (2.6 → 2.2 GB).

**Not done — deliberately left for you.** §5 below is server-side and
untouched by this: the root WordPress, `/test-pdf.php`, and the two nested
`/flyers/papers/` duplicates. The archive still duplicates `multipress-extract`
(1.1 GB) and `Design new tiegel site` (577 MB), both of which now live in the
new tree — deleting those two from the archive would bring it to ~500 MB.

Steps 5 and 6 of §3 are **done** — 21 Aug 2026. The deploy configs were fixed
(`.claude` and `.gitignore` added to both ignore lists, the `localhost` stub
deleted) and the mapping was proven with a round-trip upload. See the
"Deploy mapping — proven" section of `SERVER-MAP.md` for the results and for
the one item it surfaced: `/legal/.htaccess` on the server is the calculator's
file, not the legal one.

---

## 0. What is online — verified

From the complete tree (18:29) plus live status checks (18:45). This, not
`SITE-MAP.md`, is now the authoritative list.

| URL | status | what it is | source folder |
|---|---|---|---|
| `/` | 301 | → clickclickgraphics.com. **Covers `/` only — see §5** | — |
| `/flyers/` | 401 | the shop — the customer route | `shop/flyers/` |
| `/calculator/` | mixed | pricing + payment backend for everything | `shop/calculator/` |
| `/calculator/print.html` | 401 | internal calculator UI (built) | `build/internal-calculator/` |
| `/print-calculator/` | 401 | red wrapper that iframes the above | `shop/print-calculator/` |
| `/print-calculator-test/` | 401 | unfinished parallel build | `shop/print-calculator-test/` |
| `/legal/` | **200** | legal notice, privacy, terms EN/FR/NL | `shop/legal/` |
| `/papers/` | **200** | paper guide EN/FR/NL | `shop/papers/` |
| `/chat/` | 401 | the AI quote assistant | `clickclick-chat/chat/` |
| `/chat-api/` | 403 | its backend | `clickclick-chat/chat-api/` |
| `/tools/` | 401 | internal hub | `shop/tools/` |
| `/PaperStock/` | 401 | paper stock tool | `clickclick.be/PaperStock/` |
| `/Dashboard-MP-CC/` | 401 | MultiPress dashboard | `Dashboard-CC/` (standalone) |

**Gone since the notes were written** — all four now 404, confirmed:
`calculator/tiegel.be/`, `calculator/Archive 15maart2026/`, `/deploy/`,
`/Clickclick-shop/`. The 18:02 tree was taken before those
deletions and stopped partway through `calculator/`; disregard it.

**Not in any previous note** — a full WordPress at the site root, three nested
duplicate deploys, and a publicly downloadable pricing spreadsheet. All
confirmed live. See §5.

---

## 1. The four folders

    ~/Desktop/Claude-workfolder/
    ├── ClickClick-Web-tools/    ← everything that is or becomes a URL
    ├── Dashboard-CC/            ← the MultiPress export. Standalone.
    ├── ClickClick-Business/     ← documents, no web role
    └── _Archive/                ← frozen, read-only, dated

`Dashboard-CC` stays out of `ClickClick-Web-tools` by decision: it is a
MultiPress export, not part of the clickclick.be site. One caveat to record
rather than argue with — its built output *is* served from
`clickclick.be/Dashboard-MP-CC/`. That deploy step lives inside `Dashboard-CC`
and is noted in `SERVER-MAP.md`, so the link is not lost.

### ClickClick-Web-tools

    ClickClick-Web-tools/
    ├── README.md               ← start here: what exists, how to deploy
    ├── SERVER-MAP.md           ← URL → deploy unit → gate → source
    │
    ├── clickclick.be/
    │   ├── shop/   ← git repo, unchanged. The shop + backend.
    │   ├── clickclick-chat/    ← git repo, unchanged. The AI assistant.
    │   └── PaperStock/         ← the paper stock tool
    │
    ├── tiegel.be/              ← staging only. Never anything permanent.
    │   └── site/
    │
    ├── build/                  ← compiles INTO the above. Never uploaded.
    │   └── print-calculator/   → clickclick.be/calculator/print.html
    │
    ├── brand/                  ← GT Pressura, logos
    ├── ops/                    ← sftp-tree.py, make-deploy.py, check scripts
    └── archive/                ← retired web things only, dated

**How the repos and the mirror coexist.** The three repos stay separate, as
decided. Each deploy unit's own root already maps to the server root — that is
how both `sftp.json` files are written today, and it is correct. So
`shop/` opened as the VS Code workspace root deploys `flyers/`,
`calculator/`, `legal/`, `papers/`, `tools/` and `print-calculator/` into their
matching server folders, and `clickclick-chat/` deploys `chat/` and `chat-api/`
into theirs. They overlay the same site root without touching each other.

The rule that prevents every stray upload you have had: **open the deploy unit
itself as the workspace root — never `clickclick.be/`, never
`ClickClick-Web-tools/`, never the Desktop.** Every stray so far
(`/deploy/`, `/Clickclick-shop/`, `calculator/tiegel.be/`) came
from a workspace root sitting at a different depth than the remote root, so
the client faithfully recreated the extra folder on the server.

**The `build/` split** is the second rule: a folder either **is uploaded** or
it **produces something uploaded**, never both. `node_modules`, `src/` and
Vite configs live under `build/` and physically cannot reach the server.

---

## 2. Where every item goes

Complete — nothing in `Node-CCG` is left unassigned.

### → ClickClick-Web-tools

| now | becomes | note |
|---|---|---|
| `Clickclick-shop/` | `clickclick.be/shop/` | repo kept, remote unchanged |
| `clickclick-chat/` | `clickclick.be/clickclick-chat/` | repo kept, remote unchanged |
| `Paper stock/server/` | `clickclick.be/PaperStock/` | **rename `stock` → `PaperStock`** — see §4 |
| `Paper stock/server NNNN/` | `archive/paper-stock-snapshots/` | five dated 18 Jun snapshots |
| `Paper stock/*.zip`, `*.xlsx` | `archive/paper-stock-snapshots/` | |
| `internal-print-calculator/` | `build/internal-calculator/` | Vite source; not a repo |
| `Design new tiegel site/` | `tiegel.be/site/` | **holds live DB credentials — see §4** |
| `GT Pressura/`, `Logo.jpg` | `brand/` | |
| `Logo - Anthony/` | `brand/panier-switch/` | a cart-arrow HTML/SVG experiment |
| `tools/sftp-tree.py` | `ops/` | currently inside flyer-calculator |
| `make-deploy.py` | `ops/` | |
| `check-dashboard-exposure.sh` | `ops/` | |
| `tiegel-request-bot/` | `archive/tiegel-request-bot/` | retired 17 Aug. **Archive its PII first — §4** |
| `Archive/tools-hub-2026-06-19/` | `archive/` | |

### → Dashboard-CC (standalone)

| now | becomes |
|---|---|
| `multipress-extract/` | `Dashboard-CC/` |
| `Multipress-Swiss-Backup/BACKUP-PLAN.md` | `Dashboard-CC/` |

### → ClickClick-Business

`Accounting - 2026/` · `Claude finance/` · `Fabriekstraat/` ·
`HPE Proliant ML350/` · `Signatures - change/` ·
`DIGITAAL_PRIX Konica.pdf` · `Prix Konica C12010s.xlsx`

### → _Archive/Node-CCG-2026-08-20 (frozen, then thinned)

| now | why |
|---|---|
| `Node-CCG/.git` + `.gitignore` | the outer repo — 11,107 uncommitted files, remote `Dashboard-CC.git`, wrapping three real repos. **Retired, not carried forward.** |
| `Archive/` (167 MB) | incl. `clickclick-calculator/` 69 MB and a second WordPress copy `tiegel.be/` 98 MB |
| `Backup/` (142 MB) | eight overlapping snapshots, two of them 69 MB each |
| `PaperStock-pw-fix/whereami.php` | one-off debug probe |
| `access.log`, `error.log` | May server logs |
| `receipt.pdf`, `receipt_preview.*` | calculator test output |
| `chat-prototype.json` | superseded by the shipped chat |
| `.DS_Store`, `.claude/`, `.vscode/sftp.json.disabled` | machine-local |

---

## 3. Migration

Nothing is deleted until the new tree is proven. The old folder stays
byte-for-byte until step 7.

**Step 1 — freeze (5 min).**

    mkdir -p ~/Desktop/_Archive
    cp -a ~/Desktop/Node-CCG ~/Desktop/_Archive/Node-CCG-2026-08-20

**Step 2 — push the three repos (10 min).** Get the history off this machine
before anything moves. They stay separate; only their location changes.

    for r in Clickclick-flyer-calculator clickclick-chat tiegel-request-bot; do
      git -C ~/Desktop/Node-CCG/$r add -A
      git -C ~/Desktop/Node-CCG/$r commit -m "checkpoint before restructure"
      git -C ~/Desktop/Node-CCG/$r push
    done

Moving a repo folder does not affect its remote — nothing to reconfigure
afterwards.

**Step 3 — retire the outer repo.** Do *not* commit its 11,107 files. Step 1
has it; it is not carried forward.

**Step 4 — build the new tree (30 min).** Create the folders, then `mv` each
item per the table in §2.

**Step 5 — fix the deploy configs.**
* `shop/.vscode/sftp.json` — keep as is. Add `build`, `ops`,
  `archive`, `brand`, `*.md` to `ignore`.
* `clickclick-chat/.vscode/sftp.json` — keep as is.
* **Delete `legal/.vscode/sftp.json`** — a `localhost` stub that can only
  cause confusion.
* Confirm `**/.htpasswd` and `**/secrets.php` are in every `ignore` list.
* Then **Developer: Reload Window** in VS Code. The extension caches the old
  root and will otherwise keep using it silently.

**Step 6 — prove it before trusting it.** Deploy one harmless file, then:

    cd ~/Desktop/Claude-workfolder/ClickClick-Web-tools
    python3 -u ops/sftp-tree.py --sizes --depth 8 \
      --config clickclick.be/shop/.vscode/sftp.json 2>&1 \
      | tee ../ops-trees/clickclick-tree-$(date +%Y-%m-%d-%H%M).txt

If the file landed where §5 says it should and nothing new appeared at the
server root, the mapping is right. Write the result into `SERVER-MAP.md`.

**Step 7 — only now, reclaim space.** Drop `node_modules` from the archive
copy (215 MB total, 68 MB of it inside `Archive/`) and thin the duplicate
snapshots. Target: 2.6 GB down to roughly 300 MB.

---

## 4. Four things to handle carefully during the move

1. **`Design new tiegel site/tiegel.be/` holds live secrets** — `wp-config.php`
   and `eu3mfw_WP1384671.sql`, a full database dump. It must never sit inside
   a folder a deploy mirrors. Under `tiegel.be/site/` it is outside every
   `sftp.json` root, which is the point of putting it there.

2. **The paper stock folder is misnamed locally.** The tool deploys to
   `/PaperStock/` — capital P, capital S — but the local folder is `stock/`,
   and `/stock/` on the server is a 404. Rename it to `PaperStock/` so folder
   name and URL match, which is the whole convention. The live copy is
   `Paper stock/server/` (19 Jun 22:53); the five `server NNNN/` folders are
   older 18 Jun snapshots.

3. **`tiegel-request-bot` holds customer PII.** Archive
   `request-api/requests/` and `logs/` before the folder moves — customer
   quote requests and business records.

4. **Every paper-stock snapshot contains a `.htpasswd`.** Confirm they are
   inside `archive/`, which no `sftp.json` root can reach.

---

## 5. Server-side items — revised against the full tree

The complete tree (1,420 lines, 241 dirs, 1,146 files, 80.4 MB) changed this
list. Every status code below was checked live at 18:45 today.

### Do first — a live exposure

**`/calculator/print-calculator-values.xlsx` returns 200.** Your pricing
spreadsheet — paper costs, margins, click tiers — is downloadable by anyone
right now. The copy inside `data/` is correctly 403; this second copy sits at
the `/calculator/` root, where only `data/` is protected. This is exactly the
gap that the blanket-deny item predicted, and it is open.

    Delete it, then add to calculator/.htaccess beside the \.jsonl$ rule:
      <FilesMatch "\.xlsx$">  Require all denied  </FilesMatch>

### A WordPress at the site root, undocumented

`SITE-MAP.md` records the root as a 301 to clickclickgraphics.com. That is true
of `/` **only** — the WordPress underneath it is directly reachable:

| path | status | |
|---|---|---|
| `/wp-login.php` | **200** | a live login form, 52 KB |
| `/wp-admin/` | 302 | → the login form |
| `/readme.html` | **200** | reveals the WordPress version |
| `/license.txt` | **200** | same |
| `/xmlrpc.php` | 403 | already denied — good |
| `/wp-config.php` | 200, **0 bytes** | executes, does not leak. Fine. |

This is the same class of thing as the `calculator/tiegel.be/` install you
removed today, but at the root and still live. Decide whether this WordPress is
still needed at all; if it is, gate `wp-login.php` and delete `readme.html`
and `license.txt`. If it is not, it should go the way of the other one.

### Nested duplicate deploys — the stray-upload problem, visible

The tree shows folders uploaded into themselves:

* `/flyers/papers/` — a full copy of `/papers/`, **inside** the shop
* `/flyers/papers/flyers/` — and a full copy of the shop inside *that*, with
  its own 96 KB `index.html`, fonts and `.htpasswd`
* `/legal/legal/` — a full copy of `/legal/`, and **`/legal/legal/` is 200,
  publicly reachable**

None of these is referenced by anything. They are wrong-workspace-root
accidents of exactly the kind §1 removes, and they are the strongest argument
for the one-deploy-unit-per-workspace-root rule. Delete all three.

### Also live

* **`/calculator/customer.html` → 200.** The retired customer calculator.
  Delete it plus `customer.js`, then drop the line-104 `.htaccess` exemption.
* **`/test-pdf.php` → 200** at the site root. A leftover debug page that prints
  `PHP version: 8.2.31` and the absolute server path
  `/home/clients/bb59b9bb68f9d5f0bbd421f23f3ae8ed/...`. Delete it.
* **`/Logo.jpg`, `/_index.html`, `/index.html`** at the root — 200. Harmless,
  but decide whether they belong.

### Verified safe — no action

Worth recording so these are not re-investigated. All return 403:
`calculator/secrets.php`, `chat-api/secrets.php`, `flyers/.htpasswd`,
`calculator/data/print-calculator-values.xlsx`, `calculator/calc_log.jsonl`,
`calculator/orders/*.log`, `chat-api/logs/*.jsonl`.
`Dashboard-MP-CC/data/fakturen.csv` returns 401 — gated, but confirm with
`curl -u`, since an anonymous 401 only proves a gate was *declared*.

### Still open from before

1. Move `.htpasswd` above the web root. Six now exist — `flyers/`,
   `flyers/papers/`, `PaperStock/`, `print-calculator/`, `Dashboard-MP-CC/`
   and the one the deploys mirror. Consolidating them above the root is what
   stops a repeat of 19 Aug.
2. Delete the two stale `calculator/chunks/dateHelpers-*.js` (≈1 MB).
   `Dashboard-MP-CC/assets/` has the same accumulation — **7 hashed JS
   bundles, ~2.8 MB, where one is in use.**
3. Rotate the CCG htpasswd — deferred, still open.

---

## 6. Adding a new venture later

* **A new site** → a new domain folder beside `clickclick.be/`, its own
  deploy unit, its own `sftp.json`. Nothing else changes.
* **A new app on clickclick.be** → a folder inside an existing deploy unit,
  named exactly as its URL, plus a row in `SERVER-MAP.md`.
* **Something that compiles** → source under `build/`, output committed into
  the deploy unit.
* **Something retired** → `archive/<name>-<date>/`, out of every deploy path.
* **Something with no URL at all** → `ClickClick-Business/`. This is the rule
  that keeps the web folder from silently becoming a junk drawer again.

The test for whether the structure is holding: *can you name the URL a folder
serves, by looking only at the folder?* Inside a deploy unit the answer is
always yes, because the folder name **is** the URL.
