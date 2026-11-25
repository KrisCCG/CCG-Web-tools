# tiegel.be — content extracted from the database

Source of truth for the new block theme. Everything here was read straight out of
`tiegel.be/eu3mfw_WP1384671.sql` (no DB server), via `scratchpad/dump.py`.

Extracted 2026-08-06. Live homepage = page id **717**, WordPress 7.0.2.

> **Why this file exists:** the current site stores none of its content in `post_content`.
> It all lives in Lay Theme's proprietary `_gridder_json` postmeta blobs. When Lay is
> dropped, that content becomes unreadable unless it's been pulled out first. This is it.

---

## Brand tokens (for `theme.json`)

| Token | Hex | Where it came from |
|---|---|---|
| cream / background | `#fce4d4` | `.fond-beige` in the homepage inline SVGs |
| blue-violet / accent | `#4200ff` | `.contour-bleu`, and the marquee border colour |

The log described these as "purple/cream" — `#4200ff` is a vivid blue-violet, worth naming
accurately in the palette.

---

## Homepage structure (desktop `_gridder_json`, 12-column grid, 21 elements)

In reading order by row:

| Row | Type | Content |
|---|---|---|
| 0 | text | `<h1 class="_Les_Titres"><em>!!</em> We love</h1>` |
| 0 | html | social icons (inline CSS + SVG, flex, 12px gap) |
| 1 | text | `<h1 class="_Les_Titres"><em>!!</em> <span id="jjj">... </span></h1>` ← rotating word |
| 2 | html ×2 | two inline SVG blocks carrying the brand colours |
| 3 | marquee | 16 images, speed 120, 5px `#4200ff` top+bottom border |
| 4 | hr | |
| 5 | text | **WHAT DO WE DO? OUR SERVICES** |
| 6 | hr | |
| 7 | img ×4 | four images at cols 1/4/7/10 |
| 8 | elementgrid | the services grid (see below) |
| 9 | hr | |
| 10 | text | **Case studies** |
| 11 | hr | |
| 12 | postThumbnail ×4 | case-study thumbnails, 3 cols each |

A **separate mobile layout** exists in `_phone_gridder_json` (25 KB), authored independently
of the desktop one. The rebuild should collapse these into a single responsive system rather
than porting two layouts.

### Services grid (`elementgrid`, 4 columns)

| Post ID | Link | Image |
|---|---|---|
| 52 | `/printing/` | `2026/05/ewuuwzwuwve.jpg` |
| 120 | `/graphic-design/` | `BC-Cartevisite-1-1.jpg` |
| 123 | `/signage/` | `Installation.jpg` |
| 127 | `/consulting/` | `20260414_141914-e1776170602205.jpg` |

### Marquee (16 items)

Alternates brand SVGs with photography:
`CLICK_CLICK_GRAPHICS-20.svg`, `test-patchwork-printing.jpg`, `Installation.jpg`,
`CLICK_CLICK_GRAPHICS-25.svg`, `DSC06770.jpg`, `CLICK_CLICK_GRAPHICS-17.svg`, `IMG_6692.jpg`,
`CLICK_CLICK_GRAPHICS-23.svg`, `Aldente9.jpg`, `Ronger1.jpg`, `CLICK_CLICK_GRAPHICS-26.svg`,
`DSC06744.jpg`, `Click_Click_Graphics-25.png`, `IMG_6710.jpg`, `CLICK_CLICK_GRAPHICS-20.svg`,
`BC-touslesvinssanslogo.jpg`

---

## The rotating headline

Template: **"!! We love ___"**. The blank is `<span id="jjj">`, filled by JS stored in the
Lay option `misc_options_analytics_code` (i.e. hidden in the analytics-code field). A
near-duplicate script drives the mobile variant `.jjjmobil`.

Words, in order:

> Screenprint · printing · Brussels · Digital printing · Graphic design · Offset printing ·
> Technical guidance · Letterpress printing · Ecological advice · Consulting · Book design ·
> Local prints · Ethical

Timing: `setInterval(…, 1000)` — one word per second — with a 200 ms opacity fade to 0 and
back, plus an animated underline (`background-size` 0%→100%, 0.8s ease).

**Two defects to fix in the rebuild:**
1. *Grammar.* Several entries don't complete "We love ___": "Ethical", "Technical guidance",
   "Ecological advice". Either rewrite the list or change the template.
2. *Timing.* 1s per word with a 200ms fade means the text is mid-fade a fifth of the time,
   and the 0.8s underline transition never finishes before the next word. That is the
   "low-contrast / broken mid-animation state" from the review. Slow the interval and make
   the fade duration a fraction of it.

---

## Navigation (`Menu_en`, 12 items)

| # | Label | Target |
|---|---|---|
| 1 | Services | `#services` (anchor) |
| 2–5 | *(inherit post titles)* | posts 52, 120, 123, 127 — the four services |
| 6 | Case studies | `#case_studies` (anchor) |
| 7 | More? | `#about` (anchor) |
| 8 | Contact | `#contact` (anchor) |
| 9 | *(inherits)* | page 94 — Quotes |
| 10 | A good file? | page 65 |
| 11 | Shop | `/shop` |
| 12 | Langues | `#pll_switcher` |

**This is a one-page site.** Most nav items are in-page anchors, not routes. That is the
governing architectural fact for the rebuild.

Note for review flaw #5: Contact is *already* in the main nav. The actual problems are that
it's an anchor rather than a real page, and that "More?" (#7) is the vague label.

---

## Content inventory

- **10 pages** — 4 published, 5 private, 1 draft.
  - `717` Click Click Graphics - Print & Design !! — **the live homepage**
  - `713` / `714` — FR and NL homepage variants, both **private**
  - `494` / `10` — `Home BACKUP` / `Home-BACKUP` duplicates, private (drop these)
  - `496` `/home` private · `198` `menu burger` draft
  - published: `94` Quotes · `65` a good file? · `190` footer
- **8 published posts**, in two categories of 4:
  - *Services*: Printing (52), Graphic Design (120), Signage (123), Consulting (127)
  - *Case Studies*: Anima (53), Dou (54), Natrimoine (55), Nona (50)
  - Four of these still carry `hello-world-2*` slugs — worth fixing on rebuild.
- **237 attachments**, 240 revisions.

---

## Polylang: configured, but empty

| Language | Registered | Objects |
|---|---|---|
| English | yes | **157** |
| Français | yes | **0** |
| Nederlands | yes | **0** |

`Menu_fr` exists with **zero** items. Only one `post_translations` group (3 objects) — the
homepage trio, whose FR/NL versions are `private`.

**Consequence for the plan:** there is no FR/NL content to migrate. The theme can be built
translation-ready, but the French and Dutch copy has to be written from scratch by someone.
This needs an explicit scope decision from the owner.

---

## Migration gotchas

- Media URLs in the grid JSON are **absolute and `http://`** (`http://tiegel.be/wp-content/...`),
  despite the https-redirection plugin. A local import needs a search-replace, and the
  rebuild should emit relative or `https://` URLs.
- Lay stores three copies of each layout — `_gridder_json`, `_gridder_json_last`,
  `_gridder_json_preview` — which drift apart. `_gridder_json` is the live one.
- `overlay_settings` is a 65-key PHP-serialised blob per page (slide-from-top overlay,
  100% width, white background at 20% opacity, top-right close). Only relevant if the
  overlay/burger menu behaviour is being preserved.
