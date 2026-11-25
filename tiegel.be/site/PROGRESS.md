# Tiegel.be — Rebuild Progress

Living log for the tiegel.be (Click Click Graphics) website rebuild.
At the start of each session, read this file first, then continue from "Next actions".

Last updated: 2026-08-06

---

## Goal

Full rebuild of tiegel.be as a **custom WordPress block theme**, replacing the current
Lay Theme. Keep WordPress as the CMS (owner edits content there). Keep the existing
visual identity; fix the first-impression flaws found in the review.

Owner: Kris (k.dewaele@gmail.com). Business: Click Click Graphics — printing plant in
Brussels (books, posters, cards, brochures). Appointment-only.

---

## Decisions (agreed)

- **Design direction:** Keep current identity (purple/cream, bold typography, "!!" branding).
  Fix flaws, do not redesign the brand.
- **CMS:** Stay on WordPress. Drop the Lay theme — not important to the owner.
- **Languages:** EN + FR + NL. Polylang is already installed (v3.8.3) — build on it.
- **Shop:** KEEP the `/shop` nav link. Shop exists but is unfinished and is NOT in the
  files/database yet — nothing to build or preserve now. Wire the link to its future
  location (or a "coming soon" placeholder) until the shop is ready. (Earlier assumption
  of a finished shop w/ price calculator + Mollie was wrong — no such tables in the DB.)
- **Contact form:** Do NOT use Forminator. Keep it MINIMAL (name, email, message + any
  minimum the workflow needs). A bot/workflow will handle submissions downstream, so the
  form should hand off to that (webhook / endpoint / email TBD) rather than build its own
  mail logic. Get the handoff target from owner when building the contact page.
- **PHP:** Server currently on 8.2 (effectively EOL as of 2026). Recommend owner bump to
  8.3 (supported into late 2027) or 8.4 via Manager. Build theme compatible with 8.2–8.4.
- **Database engine:** MariaDB 10.11.15 (10.11 LTS). Import dump into matching MariaDB locally.
- **Server DB host:** eu3mfw.myd.infomaniak.com:3306. DB `eu3mfw_WP1384671` = tiegel.be (9.41 MB).

## First-impression fixes to build into the new theme (from the review)

1. Duplicate "ORDER ONLINE" buttons in hero — remove one; make the second a distinct
   action (e.g. "Get in touch" / "Book an appointment").
2. Hero doesn't say what the business is — add a tagline under the headline
   ("Printing plant in the heart of Brussels — books, posters, cards, brochures").
3. Rotating headline has a low-contrast/broken mid-animation state (pale lavender on
   cream). Fix contrast / speed transition. Also the rotating words don't all fit the
   "WE LOVE ___" template.
4. Blank-screen scroll: full empty viewports before scroll-triggered animations fire.
   Trigger earlier / lower threshold / reduce delay.
5. Nav: surface "Contact" in main nav; rename vague "More?"; contact is the key
   conversion path for an appointment-only business.
6. "By appointment only" is buried in the footer — move it up.
- Minor: domain (tiegel.be) vs brand (Click Click Graphics) naming consistency.

---

## Current stack (observed)

- WordPress core (readme reported "WordPress 7.0" via generator meta; verify actual core).
- Theme: `lay` (Lay Theme v11.0.4) + `laytheme-carousel` plugin. Layouts live in the DB
  (Lay visual grid editor), not in template files.
- Plugins present: akismet, duplicate-post, forminator (forms), honeypot,
  https-redirection, instant-images, laytheme-carousel, **polylang (3.8.3)**,
  updraftplus, vod-infomaniak, wordpress-importer, wp-super-cache.
- Host: Infomaniak (`.infomaniak-maintenance.html`, `.user.ini`).
- DB table prefix: `wp_1384671_`.
- DB name on server: `eu3mfw_WP1384671`.

## Content in the database (eu3mfw_WP1384671.sql — 4.5 MB, 20 tables)

- 12 pages, 13 posts, 12 nav menu items, 237 media-library attachments.
- Forminator form tables present (frmt_*) — forms/entries.
- Action Scheduler tables present (background jobs).
- No WooCommerce / Mollie / shop tables.
- Uploads referenced only under: 2026/02, 2026/03, 2026/04, 2026/05
  (~266 distinct original files + WP-generated thumbnail sizes).

---

## Files status (in this connected folder)

Path root: `tiegel.be/`

- [x] WordPress core files — **was incomplete; fixed 2026-08-06.** `wp-includes/` (all 2,895
      files) was missing entirely, so WP could not have run. Restored from the official
      wordpress-7.0.2.zip. Also restored the bundled default themes (twentytwentythree/four/five)
      — not used by the site, but twentytwentyfive is a useful block-theme reference.
      Core integrity now verified against the official 7.0.2 checksum manifest.
- [x] wp-content/plugins/ (all listed above)
- [x] wp-content/themes/lay/ (reference only)
- [x] Database dump: `tiegel.be/eu3mfw_WP1384671.sql`
- [x] wp-content/uploads/ — DONE (2026-08-05). 379 MB, 1,760 files, months 2026/02–08.
      Coverage: 224/226 DB-referenced originals present. Only missing = test files
      (2026/03/V2_shake.svg, 2026/03/test.png) — ignore.
- [x] Shop code — N/A for now (shop unfinished, not in files; keep /shop link only)

**All required files collected. Ready to stand up WordPress locally.**

Security note: `wp-config.php` was included and contains DB credentials. It left the
server — owner may want to rotate that DB password. Do not read/expose it.

---

## Findings from the DB dump (2026-08-06)

Extracted directly from `eu3mfw_WP1384671.sql` with a standalone parser (no DB server needed):
`scratchpad/dump.py`. Re-usable — `dump.posts()`, `dump.options()`, `dump.rows(table)`.

**Core version — settled.** WordPress **7.0.2**. All 590 `wp-admin` files match the official
7.0.2 checksum manifest exactly (0 mismatches), and the restored `wp-includes/version.php`
reports `$wp_db_version = 61833`, matching the DB's `db_version`. The site is on the current
release. Nothing to upgrade. (WP 7.0 requires PHP 7.4+, *recommends* PHP 8.3+ — so the
server's 8.2 runs fine but is below the recommendation. The plan to bump to 8.3 stands.)

**The site is a ONE-PAGE site.** The main menu (`Menu_en`, 12 items) is mostly in-page
anchors: `#services`, `#case_studies`, `#about`, `#contact`, plus `/shop` and a
`#pll_switcher`. This is the single most important fact for the rebuild — it is not a
multi-page architecture.

**All content lives in Lay's proprietary grid JSON, not in `post_content`.** Every page has
`post_content` = 0 chars. The real content is in postmeta `_gridder_json` (desktop) and
`_phone_gridder_json` (mobile) — for the live homepage (id 717) that's 47 KB and 25 KB.
Structure: `{colCount: 12, cont: [...]}`, 21 elements typed `text|html|marquee|hr|img|
elementgrid|postThumbnail`, each positioned by `row`/`col`/`colspan`. **Desktop and mobile
are two separately authored layouts** — the rebuild must reconcile them into one responsive
system.

**Real content counts** (PROGRESS.md previously said "12 pages, 13 posts" — actual):
- 10 pages: 4 published, 5 private, 1 draft. Live homepage = id **717**.
  Private ones are FR/NL homepage variants (713/714) + two `Home BACKUP` copies + `/home`.
- 8 published posts = the Services (Printing, Graphic Design, Signage, Consulting) and
  Case Studies (Anima, Dou, Natrimoine, Nona) — 4 each, via categories.
- 237 attachments, 240 revisions.

**Brand tokens recovered from the homepage SVGs** — for `theme.json`:
- cream / `fond-beige`: **`#fce4d4`**
- blue-violet / `contour-bleu`: **`#4200ff`** (note: the log called this "purple" — it's a
  vivid blue-violet)

**The rotating headline** (review flaw #3) is a `<span id="jjj">` filled by JS stashed in the
Lay option `misc_options_analytics_code` (an abuse of the analytics-code field). Separate
near-duplicate script for mobile (`.jjjmobil`). Word list:
Screenprint, printing, Brussels, Digital printing, Graphic design, Offset printing,
Technical guidance, Letterpress printing, Ecological advice, Consulting, Book design,
Local prints, Ethical.
Confirms the review: several don't fit "WE LOVE ___" (Ethical, Technical guidance), and the
timing is `setInterval(1000)` with a 200 ms opacity fade — a 1-second flicker, which is the
"broken mid-animation state".

**Polylang is configured but the translations do not exist.** Languages EN/FR/NL are all
registered, but the object counts are English **157**, Français **0**, Nederlands **0**.
`Menu_fr` exists with **0 items**. Only one `post_translations` group (count=3) — the three
homepage variants, and the FR/NL ones are `private`.
=> **Scope correction:** "build on the existing Polylang content" is not possible. There is
no FR/NL content to migrate. The new theme should be translation-*ready*, but someone has to
write the FR and NL copy. Confirm with owner whether FR/NL is in scope for this build.

**Correction to review flaw #5:** "Contact" IS already in the main nav (item 8). The real
issue is that it's an in-page anchor (`#contact`), not a page — and "More?" (item 7, `#about`)
is the vague one. Adjust the fix accordingly.

## Next actions

--- RESUME HERE next session (2026-08-06): ---

0. [ ] **BLOCKED — needs owner action.** Local tooling can't be installed. Homebrew on this
       Mac is owned by the `Admin` account (`/opt/homebrew`, group `admin`); user
       `clickclick` is not in the `admin` group and has no passwordless sudo, so
       `brew install` fails. Do NOT run brew's suggested
       `sudo chown -R clickclick /opt/homebrew` — that hijacks Homebrew from the Admin
       account. Fix by either adding `clickclick` to the `admin` group, or running from the
       Admin account: `brew install mariadb@10.11 php@8.3 wp-cli`.
       (Versions verified available and matched to the server: MariaDB 10.11.18,
       PHP 8.3.33, wp-cli 2.12.0.)
1. [ ] Once unblocked: stand up WordPress locally — import the .sql, point wp-config at a
       local DB (MariaDB 10.11), prefix `wp_1384671_`, get a reference baseline rendering.
2. [x] Confirm actual WP core version. — **WordPress 7.0.2**, verified by checksum.
3. [~] Extract real content from the DB. Homepage layout, brand colours, menu, rotating
       headline and Polylang state are done (see Findings). Still to pull: the `elementgrid`
       services block, the marquee, `overlay_settings`, the About/contact copy, and the
       mobile `_phone_gridder_json` layout.
6. [ ] Scaffold the custom block theme (theme.json = purple/cream palette + bold type
       tokens; templates: front page, service pages, case studies, contact).
7. [ ] Build front page with the 6 first-impression fixes baked in.
8. [ ] Wire Polylang across the new templates (EN/FR/NL).
9. [ ] Test against real content + media; check mobile, contrast, performance,
       no blank-scroll states.
10. [ ] Hand off: theme folder + migration/deploy notes for Infomaniak.

## Open questions

(The three earlier questions — PHP version, Forminator, `/shop` — are all answered in
"Decisions" above. Removed.)

- **Is FR/NL actually in scope?** Polylang is set up but there is zero FR/NL content
  (see Findings). Someone has to write it. Build translation-ready and defer the copy?
- Contact form handoff target — webhook / endpoint / email? Needed before building contact.
- Fix the Homebrew ownership problem (item 0 in Next actions).
