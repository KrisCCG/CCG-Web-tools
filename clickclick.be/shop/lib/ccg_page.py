#!/usr/bin/env python3
"""
Shared page kit for the standalone Click Click pages — /papers/, /legal/, and
whatever comes next.

WHAT THIS IS FOR
    The shop at /flyers/ is a single application page. Around it sit small,
    public, content-only pages: the paper guide, the legal notice. They are
    password-free, script-free, one file per language, and they all have to
    look like tiegel.be. This module holds that look in one place so a fourth
    page costs a content file and nothing else — and so a change to the bar,
    the palette or the type lands on every page at once instead of on one of
    them.

WHAT LIVES HERE
    The company constants, the palette, the whole stylesheet, the page shell,
    and render()/write_all(). Everything that is page-specific — the copy, the
    section list, the nav labels — is passed in.

WHAT A PAGE LOOKS LIKE
    papers/build.py is the worked example. In short:

        import sys, os
        sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..', 'lib'))
        import ccg_page as kit

        CONTENT = {"en": {...}, "fr": {...}, "nl": {...}}
        kit.write_all(CONTENT, os.path.dirname(__file__))

DESIGN NOTES WORTH NOT UNDOING
    · No <script>, anywhere. The .htaccess on these directories ships
      `script-src 'none'`, so anything needing JS silently will not run. The
      photo-pair fallback is CSS for exactly this reason.
    · Fonts are a LOCAL copy in each page directory (fonts/*.woff2). /flyers/
      is password-gated, so a public page loading fonts from there gets a 401
      and falls back to a system face with no visible error.
    · The palette is sampled from tiegel.be and is identical to the restyled
      shop: indigo #4200ff, cream #fff5ef, peach #ffe5d4.
"""

# ── Company ──────────────────────────────────────────────────────────────────
COMPANY       = "Click Click Graphics SRL"
LEGAL_ADDRESS = "Rue d’Alost 7-11, 1000 Bruxelles"      # official, registered form
SHOP_ADDRESS  = "Aalststraat 7-11 Rue d’Alost, 1000 Brussel – Bruxelles"
VAT           = "BE 0732.984.161"
TEL           = "+32 2 880 70 36"
MAIL_INFO     = "info@clickclickgraphics.com"
MAIL_SHOP     = "shop@clickclickgraphics.com"

# ── Languages ────────────────────────────────────────────────────────────────
LANGS      = ("en", "fr", "nl")
FILES      = {"en": "index.html", "fr": "fr.html",           "nl": "nl.html"}
LABEL      = {"en": "EN",         "fr": "FR",                "nl": "NL"}
LEGAL_HREF = {"en": "/legal/",    "fr": "/legal/fr.html",    "nl": "/legal/nl.html"}
PAPER_HREF = {"en": "/papers/",   "fr": "/papers/fr.html",   "nl": "/papers/nl.html"}
SHOP_HREF  = "/shop/"
# The site home / "Services" destination, and where the wordmark points.
# Chosen 19 Aug 2026. Kept as one constant so moving it to
# clickclickgraphics.com later is a single edit here plus the matching line in
# flyers/index.html and clickclick-chat/chat/index.html.
SITE_HREF  = "https://tiegel.be/"

CSS = """
/* GT Pressura — the Click Click brand face, and the face tiegel.be is set in.
   Italic is load-bearing, not decoration: the "!" in the wordmark and the "!!"
   sign-off are italic glyphs. */
@font-face{font-family:'GT Pressura';font-style:normal;font-weight:400;font-display:swap;src:url(fonts/gt-pressura-400.woff2) format('woff2')}
@font-face{font-family:'GT Pressura';font-style:italic;font-weight:400;font-display:swap;src:url(fonts/gt-pressura-400-italic.woff2) format('woff2')}
@font-face{font-family:'GT Pressura';font-style:normal;font-weight:700;font-display:swap;src:url(fonts/gt-pressura-700.woff2) format('woff2')}
@font-face{font-family:'GT Pressura';font-style:italic;font-weight:700;font-display:swap;src:url(fonts/gt-pressura-700-italic.woff2) format('woff2')}

/* Sampled off tiegel.be; identical to the restyled shop. */
:root{--cream:#fff5ef;--peach:#ffe5d4;--ink:#000;--ink-dim:#4a4a4a;--indigo:#4200ff;--line:#e6d6c3}
*,*::before,*::after{box-sizing:border-box}
html,body{margin:0;padding:0;background:var(--cream);color:var(--ink);
  font-family:'GT Pressura',system-ui,sans-serif;font-weight:400;-webkit-font-smoothing:antialiased}
body{overflow-x:hidden}
em{font-style:italic}
a{color:var(--indigo)}

/* ── Top bar — full-bleed indigo, cream wordmark ─────────────────────────── */
.bar{background:var(--indigo);color:var(--cream);position:sticky;top:0;z-index:5}
/* One line, always. The bar must never wrap: the FR labels are the longest
   (Boutique / Papier / Conditions) and used to push the nav onto a second row
   below ~500px. Instead of wrapping, the WORDMARK steps down — full, then
   without "Graphics", then to the bare "!!" brand mark — which buys back the
   width the longer language needs. */
.bar .in{max-width:1280px;margin:0 auto;padding:14px 28px;display:flex;
  justify-content:space-between;align-items:center;gap:20px;flex-wrap:nowrap}
.bar a{color:var(--cream);text-decoration:none}
.mark{font-weight:700;font-size:19px;letter-spacing:.02em;text-transform:uppercase;
  white-space:nowrap;flex:none}
.mark .mk-min{display:none}
.bar nav{display:flex;align-items:center;gap:22px;font-size:15px;white-space:nowrap;flex:none}
.bar nav a.cur{text-decoration:underline;text-decoration-thickness:2px;text-underline-offset:5px}
.langs{display:flex;gap:2px;background:rgba(255,245,239,.16);border-radius:999px;padding:3px}
.langs a{padding:4px 10px;border-radius:999px;opacity:.6;font-size:12px;font-weight:700;
  letter-spacing:.08em;text-transform:uppercase}
.langs a.on{background:var(--cream);color:var(--indigo);opacity:1}

.wrap{max-width:1280px;margin:0 auto;padding:0 28px}

/* ── Hero ────────────────────────────────────────────────────────────────────
   The page title takes the same rule-above / rule-below treatment as the
   section headings, just larger. Sentence case, no "!!" mark. */
.hero{padding:52px 0 44px}
hr.r{border:0;border-top:1.5px solid var(--indigo);margin:0}
h1{color:var(--indigo);font-weight:400;line-height:1.1;letter-spacing:-.015em;
  font-size:clamp(34px,6vw,64px);margin:0;padding:12px 0 16px;text-wrap:balance}
/* No `max-width` in ch anywhere in the body copy: the text fills whatever
   column it is in and reflows with the viewport, rather than sitting in a
   fixed block with dead space beside it. In the photo sections the grid
   column is the measure; in full-width sections the page is. */
.sub{font-size:clamp(17px,2vw,23px);line-height:1.35;margin:24px 0 22px}
.back{margin:0;font-size:15px}
.back a{text-decoration:none;border-bottom:1px solid var(--indigo);padding-bottom:1px}

/* Pill button. Unused by /papers/ — kept because it is a real component of
   this look (tiegel's "Order online"), and the next page will want it. */
.pill{display:inline-block;background:var(--indigo);color:var(--cream);text-decoration:none;
  font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;
  padding:11px 20px;border-radius:999px;margin:0 8px 14px 0}
.pill:hover{background:#3300c1}
.pill.ghost{background:transparent;color:var(--indigo);border:1.5px solid var(--indigo)}
.pill.ghost:hover{background:var(--indigo);color:var(--cream)}

/* ── Contents + section headings — tiegel's rule/heading/rule sandwich ───── */
nav.toc{margin:0 0 56px}
nav.toc ol{list-style:none;margin:0;padding:14px 0 0;columns:2;column-gap:44px}
nav.toc li{margin:0 0 9px;break-inside:avoid;font-size:16px}
nav.toc li a{text-decoration:none;border-bottom:1px solid transparent}
nav.toc li a:hover{border-bottom-color:var(--indigo)}
nav.toc .n{opacity:.55;font-weight:700;font-size:12px;letter-spacing:.1em;margin-right:9px}
nav.toc .lbl{display:block;padding:14px 0 0;color:var(--indigo);font-size:12px;
  font-weight:700;letter-spacing:.14em;text-transform:uppercase}
section{padding:0 0 68px;scroll-margin-top:76px}
h2{color:var(--indigo);font-weight:400;font-size:clamp(25px,3.5vw,40px);line-height:1.15;
  letter-spacing:-.01em;margin:0;padding:14px 0 16px;display:flex;gap:.6em;align-items:baseline}
h2 .n{font-size:.42em;opacity:.55;font-weight:700;letter-spacing:.1em;flex:none}

/* ── Section body ───────────────────────────────────────────────────────── */
.body{padding-top:26px}
.lede{font-size:clamp(18px,2.1vw,25px);line-height:1.3;letter-spacing:-.01em;
  margin:0 0 26px;color:var(--ink)}
/* h3 is the small label (/papers/: "How it feels"). h3.plain is a real
   sentence-case subheading (/legal/: "Prices", "Your artwork"). */
h3{font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:var(--indigo);
  margin:24px 0 8px}
h3.plain{font-size:18px;letter-spacing:-.01em;text-transform:none;color:var(--ink);
  margin:26px 0 8px}
p,li{font-size:15.5px;line-height:1.6}
p{margin:0 0 14px}
sup{font-size:.68em;line-height:0;vertical-align:.45em}
ul{padding-left:20px;margin:0 0 14px}
li{margin-bottom:7px}
dl{margin:0 0 18px;display:grid;grid-template-columns:210px 1fr;gap:9px 20px;font-size:15.5px}
dt{color:var(--indigo)}
dd{margin:0}
.tw{overflow-x:auto;margin:0 0 18px;clear:both}
table{border-collapse:collapse;width:100%;font-size:14.5px;min-width:480px}
th,td{text-align:left;padding:10px 12px;border-bottom:1px solid var(--line);vertical-align:top}
th{font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--indigo);
  border-bottom:1.5px solid var(--indigo)}
.note{background:var(--peach);padding:18px 22px;margin:0 0 20px;clear:both}
.note p:last-child{margin:0}
.note.warn{border-left:4px solid #ef3226}

/* ── Photo pair ──────────────────────────────────────────────────────────────
   Two columns: the photo pair holds the left, all prose runs down the right.
   Explicit grid placement, NOT a float — a float plus `max-width` on <p>
   squeezes the paragraph's line boxes to zero and drops every paragraph below
   the image, which is exactly the bug this replaced.

   The photo is a CSS background on a pseudo-element, NOT an <img>. These
   directories ship `script-src 'none'`, so a JS onerror fallback would never
   fire and a not-yet-uploaded photo would show a broken-image icon on a live
   page. A background that 404s simply paints nothing and the pending label
   underneath shows through. */
.sec-photos .body{display:grid;grid-template-columns:minmax(0,40%) minmax(0,1fr);
  column-gap:44px;align-items:start}
.sec-photos .body > *{grid-column:2;min-width:0}
.sec-photos .body > .shots{grid-column:1;grid-row:1/span 99}
.shot{margin:0 0 14px}
.shot .frame{position:relative;aspect-ratio:4/3;background:var(--peach)}
.shot .ph{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
  text-align:center;padding:16px;font-size:11px;line-height:1.4;color:var(--indigo);
  letter-spacing:.1em;text-transform:uppercase;opacity:.75}
.shot .frame::after{content:"";position:absolute;inset:0;
  background-image:var(--shot);background-size:cover;background-position:center;
  background-repeat:no-repeat}
.shot figcaption{font-size:12px;line-height:1.35;padding:7px 0 0;color:var(--ink-dim)}
/* Pairs set inline between subheadings rather than beside a column of prose. */
.inline-shots .shots{width:100%;margin:14px 0 18px;
  display:grid;grid-template-columns:1fr 1fr;gap:16px}
.inline-shots .shot{margin:0}

footer{background:var(--peach);margin-top:20px;padding:34px 0 40px}
footer .in{max-width:1280px;margin:0 auto;padding:0 28px;font-size:13.5px;line-height:1.6;
  color:var(--ink-dim)}
.updated{color:var(--ink-dim);font-size:12.5px;margin:14px 0 0;opacity:.8}
footer em{color:var(--indigo);font-style:italic;font-weight:700}

@media(max-width:760px){
  .sec-photos .body{display:block}
  .shots{margin:0 0 22px;display:grid;grid-template-columns:1fr 1fr;gap:14px}
  .shot{margin:0}
  nav.toc ol{columns:1}
  .bar .in{padding:11px 18px;gap:12px}
  .mark{font-size:15px}
  .mark .mk-long{display:none}
  .bar nav{gap:14px;font-size:14px}
  .wrap,footer .in{padding:0 18px}
  dl{grid-template-columns:1fr;gap:2px 0}
  dt{margin-top:10px}
}
@media(max-width:560px){
  .bar .in{padding:10px 14px;gap:10px}
  .bar nav{gap:10px;font-size:12.5px}
  .langs{padding:2px}
  .langs a{padding:3px 6px;font-size:10px;letter-spacing:.05em}
}
@media(max-width:460px){
  .shots,.inline-shots .shots{grid-template-columns:1fr}
  /* Below this the full wordmark cannot share a line with three nav labels
     and the language pill, so it drops to the "!!" mark alone. */
  .mark .mk-short{display:none}
  .mark .mk-min{display:inline;font-size:20px}
}
"""

SHELL = """<!doctype html>
<html lang="{lang}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{title}</title>
<meta name="description" content="{desc}">
<link rel="icon" href="data:,">
<style>{css}</style>
</head>
<body>
<header class="bar"><div class="in">
  <a class="mark" href="{site}" aria-label="Click Click Graphics"><span class="mk-short">Cl<em>!</em>ck Cl<em>!</em>ck</span><span class="mk-long"> Graphics</span><span class="mk-min"><em>!!</em></span></a>
  <nav>{nav}<span class="langs">{langs}</span></nav>
</div></header>

<div class="wrap">
<div class="hero">
  <hr class="r"><h1>{h1}</h1><hr class="r">
  <p class="sub">{sub}</p>
  <p class="back"><a href="{back_href}">&larr; {back_label}</a></p>
</div>

<nav class="toc"><hr class="r"><span class="lbl">{toc_label}</span><ol>{toc}</ol><hr class="r"></nav>
{body}
</div>
<footer><div class="in">{footer}
<p class="updated">{updated}</p></div></footer>
</body>
</html>
"""


def photo_pair(slug, cap_a, cap_b, pending="Photo to come", kinds=("blank", "print")):
    """A pair of sample photographs, as CSS backgrounds rather than <img>.

    Files live in the page's img/ directory as <slug>-<kind>.jpg. A file that
    is not there yet paints nothing and the `pending` label shows through — see
    the CSS note above for why this must not become an <img> with an onerror.

    `pending` must not repeat the captions, or a missing shot reads as a
    deliberate empty frame rather than one still being photographed.
    """
    def fig(kind, cap):
        return (f'<figure class="shot">'
                f'<div class="frame" role="img" aria-label="{cap}" '
                f'style="--shot:url(&#39;img/{slug}-{kind}.jpg&#39;)">'
                f'<span class="ph">{pending}</span>'
                f'</div>'
                f'<figcaption>{cap}</figcaption></figure>')
    return (f'<div class="shots">'
            f'{fig(kinds[0], cap_a)}{fig(kinds[1], cap_b)}</div>')


def render(lang, c, section_classes=None):
    """Render one language of one page.

    `c` is that language's content dict. Required keys: title, desc, h1, sub,
    back_label, back_href, updated, toc_label, footer, nav, sections.

    `nav`  — list of (label, href) pairs. The pair whose href matches this
             language's own file is marked current automatically.
    `sections` — list of (id, title, body_html) tuples, numbered in order.
    `section_classes` — optional {section_id: "extra css class"}.
    """
    section_classes = section_classes or {}
    self_file = FILES[lang]
    toc, body = [], []
    for i, (sid, stitle, sbody) in enumerate(c["sections"], 1):
        n = f"{i:02d}"
        toc.append(f'<li><a href="#{sid}"><span class="n">{n}</span>{stitle}</a></li>')
        extra = section_classes.get(sid, "")
        cls = f"sec {extra}".strip()
        body.append(
            f'<section id="{sid}" class="{cls}">\n'
            f'<hr class="r">\n<h2><span class="n">{n}</span>{stitle}</h2>\n<hr class="r">\n'
            f'<div class="body">{sbody}</div>\n</section>')
    nav = "".join(
        f'<a class="{"cur" if href == self_file else ""}" href="{href}">{label}</a>'
        for label, href in c["nav"])
    langs = "".join(
        f'<a class="{"on" if l == lang else ""}" href="{FILES[l]}">{LABEL[l]}</a>'
        for l in LANGS)
    return SHELL.format(
        lang=lang, css=CSS, title=c["title"], desc=c["desc"], site=SITE_HREF,
        nav=nav, langs=langs, h1=c["h1"], sub=c["sub"],
        back_label=c["back_label"], back_href=c["back_href"],
        updated=c["updated"], toc_label=c["toc_label"], toc="".join(toc),
        body="\n".join(body), footer=c["footer"])


def write_all(content, out_dir, section_classes=None):
    """Write index.html / fr.html / nl.html into out_dir. Returns the paths."""
    import os
    written = []
    for lang in LANGS:
        path = os.path.join(out_dir, FILES[lang])
        with open(path, "w", encoding="utf-8") as f:
            f.write(render(lang, content[lang], section_classes))
        written.append(path)
        print(f"wrote {FILES[lang]}")
    return written


# ─────────────────────────────────────────────────────────────────────────────
# Plain-text content files
# ─────────────────────────────────────────────────────────────────────────────
# A page's words can live in a .txt file instead of in Python, so the copy can
# be edited without touching code. One file per language. The format:
#
#     # lines starting with a hash are notes to yourself; they are ignored
#
#     title:    The paper — Click Click Graphics
#     h1:       The paper
#     sub:      Three stocks in the shop...
#     nav:      Shop > /flyers/ | Paper > SELF | Terms > /legal/
#
#     [choose]  Choosing in ten seconds
#     <p class="lede">The three papers...</p>
#
#     [multiart]  MultiArt Silk — coated, bright white
#     {photos multiart-silk | The blank sheet | The same stock, printed}
#     <h3>How it feels</h3>
#     <p>Smooth and cool...</p>
#
# Rules:
#   · `key: value` above the first [section] sets the page fields. A value can
#     run over several lines — indent the continuation lines.
#   · `[id]  Heading` opens a section. Everything after it, up to the next
#     [id], is that section's body, written as HTML.
#   · `{photos slug | caption A | caption B}` expands to a sample photo pair.
#   · `SELF` in a nav target means "this page, this language".
#
# The section's layout follows from how many photo pairs it holds: one pair
# sets it beside the prose, several set them inline, none is a plain section.
# That is a rule, not a lookup table, so a new paper section needs no code.

_PHOTO_RE = __import__("re").compile(r"\{photos\s+([^|}]+?)\s*\|\s*([^|}]+?)\s*\|\s*([^}]+?)\s*\}")

FIELD_ALIASES = {"back": "back_label", "contents": "toc_label"}


def load_text(path, lang):
    """Parse one language's .txt content file into a content dict."""
    import re
    raw = open(path, encoding="utf-8").read()
    meta, sections = {}, []
    cur_id = cur_title = None
    body = []
    last_key = None

    for line in raw.splitlines():
        if line.startswith("#"):
            continue
        m = re.match(r"^\[([A-Za-z0-9_-]+)\]\s*(.*)$", line)
        if m:
            if cur_id is not None:
                sections.append((cur_id, cur_title, "\n".join(body).strip()))
            cur_id, cur_title, body, last_key = m.group(1), m.group(2).strip(), [], None
            continue
        if cur_id is None:                                   # still in the header
            if not line.strip():
                continue
            km = re.match(r"^([a-z][a-z0-9-]*)\s*:\s*(.*)$", line)   # h1 has a digit
            if km:
                last_key = FIELD_ALIASES.get(km.group(1), km.group(1))
                meta[last_key] = km.group(2).strip()
            elif last_key and line[:1].isspace():            # indented continuation
                meta[last_key] = (meta[last_key] + " " + line.strip()).strip()
            else:
                raise ValueError(f"{path}: cannot read header line: {line!r}")
        else:
            body.append(line)
    if cur_id is not None:
        sections.append((cur_id, cur_title, "\n".join(body).strip()))

    missing = {"title", "h1", "sub", "back_label", "updated", "toc_label",
               "footer", "nav"} - set(meta)
    if missing:
        raise ValueError(f"{path}: missing field(s): {', '.join(sorted(missing))}")

    pending = meta.pop("photo-pending", "Photo to come")
    nav = []
    for item in meta["nav"].split("|"):
        label, _, href = item.partition(">")
        href = href.strip()
        nav.append((label.strip(), FILES[lang] if href == "SELF" else href))
    meta["nav"] = nav
    meta.setdefault("desc", meta["sub"])
    meta["back_href"] = meta.get("back_href", SHOP_HREF)

    def expand(mt):
        return photo_pair(mt.group(1), mt.group(2), mt.group(3), pending)
    meta["sections"] = [(i, t, _PHOTO_RE.sub(expand, b)) for i, t, b in sections]
    meta["_pair_counts"] = {i: len(_PHOTO_RE.findall(b)) for i, _, b in sections}
    return meta


def layout_classes(content_one_lang):
    """Section layout, derived from how many photo pairs each section holds."""
    out = {}
    for sid, n in content_one_lang.get("_pair_counts", {}).items():
        out[sid] = "sec-photos" if n == 1 else ("inline-shots" if n > 1 else "")
    return out


def build_from_text(text_dir, out_dir):
    """Read <text_dir>/{en,fr,nl}.txt and write the three HTML pages."""
    import os
    content = {l: load_text(os.path.join(text_dir, f"{l}.txt"), l) for l in LANGS}
    return write_all(content, out_dir, layout_classes(content["en"]))
