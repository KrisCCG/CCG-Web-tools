# Naming — the rules, and the renames they imply

Written 21 Aug 2026. The **local renames below were applied the same day**;
the server-side split is still open and marked as such. Rules first, then the
renames they imply.

---

## The problem

Two naming systems are mixed, and one of them is historical.

* **Server folders** are named for *what URL serves them* — `/flyers/`,
  `/calculator/`, `/legal/`.
* **Local folders** are named for *what project they came from* —
  `flyer-calculator`, `internal-print-calculator`, `multipress-extract`.

The second kind ages badly. `flyer-calculator` was a flyer calculator once.
It then absorbed the shop, the payment backend, legal, papers and the staff
hub, and today owns **eight URLs**: `/flyers/`, `/calculator/`, `/legal/`,
`/papers/`, `/tools/`, `/print-calculator/`, `/print-calculator-test/` and
`/calculator-test/`. Naming it after one of its eight parts is what makes the
tree unreadable.

The sharpest symptom: **"calculator" currently means five different things.**

| name | what it actually is |
|---|---|
| `flyer-calculator/` | a deploy unit owning eight URLs |
| `/calculator/` | the PHP pricing + payment backend (32 files, no UI) |
| `/print-calculator/` | a 1-file wrapper that iframes `/calculator/print.html` |
| `/calculator/print.html` | the staff pricing UI itself |
| `build/print-calculator/` | the Vite source that builds that UI |

Same fault elsewhere: `Dashboard-CC` (folder), `/Dashboard-MP-CC/` (URL),
`multipress-extract` (its former name) and `Dashboard-CC.git` (the retired
repo's remote) are four names for one thing.

---

## The four rules

**1. Server folders are named by their URL.** Already true — keep it. The
folder name *is* the address. `/PaperStock/` is capital P and capital S because
the URL is; `/stock/` is a 404.

**2. Deploy units are named for what the bundle is — never for one of its
parts.** A unit that ships eight URLs cannot be named after one of them.

**3. Build sources are named for what they produce.** Not for the tool that
builds them, not for what they were called when they started.

**4. One word, one meaning.** Retire bare "calculator" as a general term:

| say this | when you mean |
|---|---|
| **shop** | the customer-facing store at `/flyers/` |
| **backend** | the PHP pricing + payment engine at `/calculator/` |
| **internal calculator** | the staff pricing tool (`/print-calculator/` + `/calculator/print.html`) |
| **chat** | the AI assistant at `/chat/` + `/chat-api/` |
| **paperstock** | the stock tool at `/PaperStock/` |
| **dashboard** | the MultiPress dashboard at `/Dashboard-MP-CC/` |

---

## The renames these imply

### Free — local only, zero server risk — ~~DONE 21 Aug 2026~~

A deploy unit's folder name is **purely local**. `remotePath` in `sftp.json` is
absolute, and files map by their path *inside* the unit — so renaming these
folders cannot change what lands on the server. Git remotes are unaffected too.

| now | becomes | why |
|---|---|---|
| `clickclick.be/flyer-calculator/` | `clickclick.be/shop/` | it is the shop and everything supporting it |
| `clickclick.be/clickclick-chat/` | `clickclick.be/chat/` | matches the URLs it owns |
| `clickclick.be/paper-stock/` | `clickclick.be/paperstock/` | one spelling, matching `/PaperStock/` |
| `build/print-calculator/` | `build/internal-calculator/` | named for what it produces |
| ~~`Dashboard-CC/`~~ | — | **not renamed, by decision.** Left as `Dashboard-CC`. |

Applied 21 Aug 2026. `ops/verify-deploy.py` was updated to match, and re-run:
the deploy is unaffected, as predicted. **Re-open each unit as its own VS Code
workspace root and reload the window** — the old paths are gone, so a window
still pointing at `flyer-calculator/` will not find its config.

Also dropped at the same time, by decision: the `calculator-test/` and
`print-calculator-test/` folders. See below.

### Not free — needs a server change and a redirect

The `/calculator/` vs `/print-calculator/` split. This is Phase 4 of
`clickclick.be/shop/PLAN.md`, and it is the change that actually removes the
ambiguity rather than working around it:

    /internal/      the wrapper and the built staff UI, together
    /calculator/    pricing and payment backend ONLY

It needs a rebuild with `--base=/internal/`, the wrapper's `index.html` moved
in beside it, the `/tools/` link repointed, and a redirect left at
`/print-calculator/` for existing bookmarks. Do it only after the open items in
`PLAN.md` Phases 1–3.

### The `-test` folders — dropped 21 Aug 2026

`calculator-test/` and `print-calculator-test/` are gone locally (3 files, all
tracked in git, so recoverable from history). `/calculator-test/` never existed
on the server; `/print-calculator-test/` was deleted from the server the same
day (2 files) and now returns 404.

They were never working: `/print-calculator-test/` iframed
`/calculator-test/print.html`, which did not exist. The 404 inside that frame
was not a broken build — it pointed at nothing.

### Deliberately not renamed

* **`/Dashboard-MP-CC/`** — the URL stays. Renaming it buys little and breaks
  every existing bookmark. The *folder* becomes `multipress-dashboard`; the URL
  keeps its name, and `SERVER-MAP.md` records the pairing.
* **The GitHub repos.** `Clickclick-flyer-calculator.git` and
  `Clickclick-chat.git` keep their names. GitHub redirects renamed repos, but
  any clone elsewhere silently keeps the old remote. Not worth it for a
  cosmetic gain.

---

## The test

*Can you name the URL a folder serves, by looking only at the folder name?*

Inside a deploy unit the answer is already yes — the folder name is the URL.
These rules extend the same property upward, so that the name of a unit tells
you what it is, and the name of a build source tells you what it makes.
