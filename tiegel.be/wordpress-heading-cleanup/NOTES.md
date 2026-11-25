# tiegel.be: heading cleanup (started 7 Oct 2026)

Theme: Lay Theme (French admin). Custom CSS goes in Apparence > Personnaliser > **CSS**
(WordPress outputs it as `#wp-custom-css`). The grid editor does NOT load that CSS,
so new `cc-` styles look plain black in the editor and only show on the live site / Aperçu.

## Files here
- `custom-css-cleaned.css`: the full CSS as published on 7 Oct 2026 (duplicate thumbnail block removed, `cc-` rules added).
- `homepage-intro-block.html`: HTML element for the homepage intro: 3 lines of text, each button beside its own line (replaces the text box id-65 and button elements id-5 / id-26). Not yet pasted into WordPress. Open it in a browser to preview; copy its source with a code/text editor.

## Naming system (new, existing styles untouched)
- `cc-title` = page H1 (60px, uppercase), once per page
- `cc-h2` = section title (40px)
- `cc-h3` = small heading (30px, same look as old "We are here")
- `cc-lead` = large intro paragraph (30px)
- `p + .cc-h3 { margin-top: 30px }` adds space above a heading that follows a paragraph
- Old classes `_Les_Titres` / `_Les_services` / `_Default` stay as they are.

## Done
- Footer (opening hours box): all headings are `<h3 class="cc-h3">`, "We work only by appointment." is a `<p>`. Live on all pages.
- Footer "By appointment only" is no longer an H1.

## Status (8 Oct 2026)
Standard tag styles (`standard-text-styles.css`, in Customizer CSS) apply to pages in the `:is(...)` slug list:
slug-printing, slug-signage, slug-consulting, slug-a-good-file. To add a page, add its slug in every list.
Lay text format `_cc_title` (Lay Options > Textformats) is used for page titles (H1).

Done: footer, home headline, Printing, Signage, Consulting (content complete; check phone button width),
A good file (title + 8 H2s live; text still French, captions still blue paragraphs).

## Still to do
- Graphic design: skipped until all text is in (Graphic identity / Layout / Website are H1 -> H2).
- Natrimoine: 1 H1, no section headings yet. Quotes: no H1, 1 title still a paragraph.
- Home: section titles (WHAT DO WE DO?, Case studies, ...) are still H1.
- A good file: pills + anchors DONE (ids on the 8 h2 titles; CSS `h2[id]{padding-top:90px;margin-top:-90px}` because Lay's own anchor scroll ignores scroll-margin). Still open: French text corrections, French vs English, swatch name spelling (Cutcontour/CutContour), overprint on varnish, page-count rule, caption style.
- Printing title: change inline line-height 0.8 -> 1 (two-line title is cramped).
- Consulting: two LET'S TALK buttons on desktop (one per column) - confirm; phone button element is 2 columns wide, make it 6.
- Printing bottom buttons block pasted? (printing-buttons-block.txt, live per check 7 Oct).
- Home grid: Printing tile uses a cat placeholder picture (set Printing project thumbnail). Video dropped (30 MB).
- Backup taken 8 Oct 2026: DB dump on Desktop (move to tiegel.be/site/); site files copy was stopped (incomplete).

## Links to update at go-live (scanned on the live site, 7 Oct 2026)
The URLs are typed into several places, so each one has to be changed everywhere. Re-scan with curl after changing.
- `https://clickclick.be/flyers/` (shop, temporarily password-protected): ORDER ONLINE button in the homepage intro block, plus 2 per page (menu/footer) on all pages.
- `https://clickclick.be/chat/` (chat): LET'S TALK button in the homepage intro block, 2 per page on all pages, plus the new Printing bottom buttons.
- `/quote`: ORDER ONLINE buttons on Home (1), Printing (3), Consulting (2), Graphic design (2), A good file (1). Decide whether these should become the shop link.
- `/shop`: 2 per page on all pages (probably the menu "Shop" entry and one more).
- Printing bottom buttons: block saved as `printing-buttons-block.txt` (ORDER ONLINE -> /quote for now, LET'S TALK -> chat). Not yet pasted in WordPress (desktop element id-65, phone element id-107).

