# ClickClick-Web-tools

Everything that is, or becomes, a URL. Built 21 Aug 2026 from the old
`Node-CCG` folder — see `RESTRUCTURE-PLAN.md` for why, and
`CCG-CONTEXT.md` for the business and pricing context.

`NAMING.md` holds the naming rules. The local renames it proposed were applied
on 21 Aug 2026; the server-side `/calculator/` vs `/print-calculator/` split
is still open.

## The shape

    clickclick.be/          the live site. One folder per deploy unit.
      shop/       git repo — the shop, pricing + payment backend,
                              legal, papers, tools hub, print-calculator
      clickclick-chat/        git repo — the AI quote assistant
      paperstock/             the paper stock tool

    tiegel.be/              staging only. Never point anything permanent here.
      site/                   WordPress. Holds live DB credentials — see below.

    build/                  compiles INTO the above. Never uploaded.
      print-calculator/       Vite source → clickclick.be/calculator/print.html

    brand/                  GT Pressura, logos
    ops/                    sftp-tree.py, check-dashboard-exposure.sh
    archive/                retired. Outside every deploy path.

## The two rules

**1. Open the deploy unit as the VS Code workspace root.** Never
`clickclick.be/`, never `ClickClick-Web-tools/`, never the Desktop.

Each deploy unit's own root maps to the *server* root. So with
`shop/` open, `flyers/` deploys to `clickclick.be/flyers/`, and
with `clickclick-chat/` open, `chat/` deploys to `clickclick.be/chat/`. They
overlay the same server root without touching each other.

Open a *parent* folder instead and the SFTP client faithfully recreates the
extra level on the server. That is where `/deploy/`,
`/Clickclick-shop/`, `/flyers/papers/flyers/` and `/legal/legal/`
all came from. After changing a workspace root, run **Developer: Reload
Window** — the extension caches the old one and fails silently otherwise.

**2. A folder is either uploaded, or it produces something uploaded — never
both.** `node_modules`, `src/` and Vite configs live under `build/` and cannot
reach the server. This is why `sftp-tree.py` moved out of
`shop/tools/`: that folder is the deploy source for `/tools/`.

## Deploying

**Use VS Code + the SFTP extension.** It reads the same `sftp.json` that was
verified on 21 Aug 2026, so the mapping is known good — but only if the
workspace root is right.

1. **File → Open Folder** → the deploy unit *itself*:
   `clickclick.be/shop/` or `clickclick.be/clickclick-chat/`.
   Not `clickclick.be/`, not `ClickClick-Web-tools/`.
   You should see `flyers/`, `calculator/`, `legal/`… at the top of the
   Explorer. If you see `shop/` as a folder, the root is wrong.
2. **Reload the window** — `Cmd+Shift+P` → `Developer: Reload Window`.
   Needed after changing the workspace root *or* after any edit to
   `sftp.json`: the extension caches both when the window opens, and uses the
   stale copy silently. Not needed if you just used File → Open Folder, which
   already starts a fresh window.
3. Right-click a file or folder → **SFTP: Upload**.
4. Verify what actually landed:

       python3 ops/verify-deploy.py

   Read-only. Every file should report "present & identical". `.DS_Store` and
   `.vscode` entries showing as absent is correct — they are ignored and never
   uploaded.

A safety property worth knowing: `sftp.json` now exists **only** inside the two
deploy units. Open a parent folder as the root and the extension finds no
config at all, so it cannot upload to the wrong place — it simply does nothing.


## Handle with care

* **`tiegel.be/site/`** holds `wp-config.php` and a full database dump
  (`eu3mfw_WP1384671.sql`). It sits outside every `sftp.json` root by design.
* **`archive/tiegel-request-bot/`** holds customer PII in `request-api/requests/`
  and `logs/`.
* **`archive/paper-stock-snapshots/`** — every snapshot contains a `.htpasswd`.
* Both live repos gitignore their `secrets.php`, `.htpasswd` and `sftp.json`.
  Keep it that way.

## Adding something new

* **A new site** → a folder beside `clickclick.be/`, its own deploy unit and
  `sftp.json`.
* **A new app on clickclick.be** → a folder inside an existing deploy unit,
  named exactly as its URL, plus a row in `SERVER-MAP.md`.
* **Something that compiles** → source under `build/`, output committed into
  the deploy unit.
* **Something retired** → `archive/<name>-<date>/`.
* **Something with no URL at all** → `~/Desktop/Claude-workfolder/ClickClick-Business/`. This is
  the rule that stops this folder becoming a junk drawer again.
