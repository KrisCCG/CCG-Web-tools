#!/usr/bin/env python3
"""
Click Click Graphics — price data check.

What it does (nothing is uploaded, nothing on the server is touched):
  1. Takes the newest  print-calculator-values_YYYY-MM-DD.xlsx  in this folder.
  2. Checks every tab: missing values, unknown papers or sizes, odd prices.
  3. Calculates the test jobs with the new file AND with the previous version,
     and shows old price -> new price.
  4. Writes a report (reports/check_YYYY-MM-DD_HHMM.html) and opens it.
  5. Only if there are no errors: builds the folder  upload/  that mirrors the server
     (shop/api/data/) with the price file and selftest.json, and copies both into the
     local shop folder. Upload instructions: upload/UPLOAD.txt.
     Since 25 Oct 2026 there is ONE price file on the server: calculator-v2 reads the shop's
     copy through /shop/api/api-dashboard.php (staff login), so it has no copy of its own.

Run:  double-click "Check prices.command"   (or:  python3 check_prices.py)
Needs only Python 3 — no extra packages.

The price formula below must stay identical to the one on the server.
"""
import datetime as dt
import glob
import html
import math
import os
import re
import shutil
import sys
import webbrowser
import zipfile
import xml.etree.ElementTree as ET

HERE = os.path.dirname(os.path.abspath(__file__))
PATTERN = re.compile(r"^print-calculator-values_(\d{4}-\d{2}-\d{2})\.xlsx$")
NS = {"m": "http://schemas.openxmlformats.org/spreadsheetml/2006/main",
      "r": "http://schemas.openxmlformats.org/officeDocument/2006/relationships"}

# --------------------------------------------------------------------------- reading xlsx (standard library only)

def _col(ref):
    n = 0
    for ch in re.match(r"[A-Z]+", ref).group(0):
        n = n * 26 + ord(ch) - 64
    return n


def read_xlsx(path):
    """{sheet name: {(row, col): value}} using the values Excel saved (formula results included)."""
    z = zipfile.ZipFile(path)
    shared = []
    if "xl/sharedStrings.xml" in z.namelist():
        for si in ET.fromstring(z.read("xl/sharedStrings.xml")).findall("m:si", NS):
            shared.append("".join(t.text or "" for t in si.iter("{%s}t" % NS["m"])))
    wb = ET.fromstring(z.read("xl/workbook.xml"))
    rels = ET.fromstring(z.read("xl/_rels/workbook.xml.rels"))
    target = {r.get("Id"): r.get("Target") for r in rels}
    out = {}
    for s in wb.find("m:sheets", NS):
        rid = s.get("{%s}id" % NS["r"])
        t = target[rid].lstrip("/")
        p = t if t.startswith("xl/") else "xl/" + t
        cells = {}
        for c in ET.fromstring(z.read(p)).iter("{%s}c" % NS["m"]):
            ref = c.get("r"); typ = c.get("t")
            v = c.find("m:v", NS)
            if typ == "inlineStr":
                val = "".join(x.text or "" for x in c.iter("{%s}t" % NS["m"]))
            elif v is None or v.text is None:
                continue
            elif typ == "s":
                val = shared[int(v.text)]
            elif typ in ("str", "e"):
                val = v.text
            elif typ == "b":
                val = v.text == "1"
            else:
                f = float(v.text)
                val = int(f) if f.is_integer() else f
            if isinstance(val, str):
                val = val.strip()
                if val == "":
                    continue
            row = int(re.search(r"\d+", ref).group(0))
            cells[(row, _col(ref))] = val
        out[s.get("name")] = cells
    return out


def table(cells, header_row, first_col=1, stop_blank=True):
    """Rows under a header row as dicts. Stops at the first fully empty row."""
    heads = {}
    c = first_col
    while (header_row, c) in cells:
        heads[c] = str(cells[(header_row, c)])
        c += 1
    rows, r = [], header_row + 1
    while True:
        rec = {h: cells.get((r, c)) for c, h in heads.items()}
        if all(v is None for v in rec.values()):
            if stop_blank:
                break
        else:
            rec["_row"] = r
            rows.append(rec)
        r += 1
        if r > header_row + 2000:
            break
    return rows


def find_row(cells, col, text, start=1):
    for r in range(start, 400):
        if cells.get((r, col)) == text:
            return r
    return None

# --------------------------------------------------------------------------- load + check

class Report:
    def __init__(self):
        self.errors, self.warnings = [], []

    def err(self, tab, msg):
        self.errors.append((tab, msg))

    def warn(self, tab, msg):
        self.warnings.append((tab, msg))


def num(v):
    return isinstance(v, (int, float)) and not isinstance(v, bool)


def split(v):
    if v is None:
        return []
    return [x.strip() for x in str(v).split(",") if x.strip()]


REQUIRED_TABS = ["Papers", "Clicks", "Machine", "Pricing rules", "Sizes", "Cutting", "Folds", "Products", "Test jobs"]
CUT_KEYS = ["cut_sell_rate", "cut_cost_rate", "cut_setup", "trim_time", "cut_time_round", "sheets_per_pile",
            "precut_cuts_if_2", "precut_cuts_if_4", "precut_cuts_if_8"]
MACHINE_KEYS = ["machine_speed", "machine_speed_long", "machine_cost_rate", "machine_sell_rate", "labor_minutes", "press_time_round",
                "setup_sheets", "prepress_fee", "sra3_width", "sra3_height", "print_margin"]
RULE_KEYS = ["time_round_step", "online_discount", "paper_markup", "min_margin", "vat_rate", "express_markup", "round_step", "round_down_threshold",
             "long_size_uplift", "color_switch_fee", "min_job_price", "delivery_dpd"]
BRO_KEYS = ["stitch_cost_rate", "stitch_sell_rate", "stitch_time_round", "tower_when_copies_above", "tower_when_pages_above",
            "manual_setup", "manual_speed", "tower_setup_extra"]
FOLD_KEYS = ["fold_cost_rate", "fold_sell_rate", "fold_fixed_setup", "fold_time_per_version",
             "fold_extra_fold_time", "fold_max_width"]


def load(path, rep):
    wb = read_xlsx(path)
    d = {}
    for t in REQUIRED_TABS:
        if t not in wb:
            rep.err(t, "tab is missing (do not rename tabs)")
    if rep.errors:
        return None

    # settings tabs: setting / value
    def kv(tab, keys):
        out = {}
        for rec in table(wb[tab], 3):
            if rec.get("setting"):
                out[str(rec["setting"])] = rec.get("value")
        for k in keys:
            if k not in out:
                rep.err(tab, f'setting "{k}" is missing')
            elif not num(out[k]):
                rep.err(tab, f'"{k}" must be a number (now: {out[k]!r})')
        return out
    d["machine"] = kv("Machine", MACHINE_KEYS)
    d["rules"] = kv("Pricing rules", RULE_KEYS)
    d["cut"] = kv("Cutting", CUT_KEYS)
    cw = wb["Cutting"]
    hr = find_row(cw, 1, "cuts")
    cut_rows = []
    if not hr:
        rep.err("Cutting", "cut table not found")
    else:
        for rec in table(cw, hr):
            if num(rec.get("cuts")) and num(rec.get("format up to (mm)")) and num(rec.get("time (h)")):
                cut_rows.append((int(rec["cuts"]), rec["format up to (mm)"], rec["time (h)"]))
        if not cut_rows:
            rep.err("Cutting", "cut table is empty")
    d["cut_table"] = sorted(cut_rows)
    for k in ("paper_markup", "min_margin", "vat_rate", "express_markup", "long_size_uplift", "online_discount"):
        v = d["rules"].get(k)
        if num(v) and v > 1:
            rep.err("Pricing rules", f'"{k}" = {v}: percentages are typed as a fraction (0.30 = 30 %)')

    # papers
    papers = {}
    for rec in table(wb["Papers"], 3):
        pid = rec.get("paper_id"); row = rec["_row"]
        if not pid:
            rep.err("Papers", f"row {row}: paper_id is empty"); continue
        if pid in papers:
            rep.err("Papers", f'row {row}: paper_id "{pid}" is used twice'); continue
        if pid != pid.lower() or " " in pid:
            rep.err("Papers", f'row {row}: paper_id "{pid}" must be lowercase without spaces')
        for k in ("gsm", "SRA3 per big sheet", "price per big sheet (€)"):
            if not num(rec.get(k)) or rec.get(k) <= 0:
                rep.err("Papers", f'row {row} ({pid}): "{k}" must be a number above 0 (now: {rec.get(k)!r})')
        if rec.get("group") not in ("cmyk", "creative"):
            rep.err("Papers", f'row {row} ({pid}): group must be cmyk or creative')
        if rec.get("active") not in ("yes", "no"):
            rep.err("Papers", f'row {row} ({pid}): active must be yes or no')
        papers[pid] = rec
    d["papers"] = papers
    # odd prices: within one paper (same code, e.g. all multiart weights) the price per gram should be similar,
    # and a heavier weight should not be cheaper than a lighter one
    fam = {}
    for pid, p in papers.items():
        if p.get("group") == "cmyk" and "-" in pid and num(p.get("price per big sheet (€)")) and num(p.get("SRA3 per big sheet")) and num(p.get("gsm")) and p["SRA3 per big sheet"] > 0:
            fam.setdefault(pid.rsplit("-", 1)[0], []).append((p["gsm"], p["price per big sheet (€)"] / p["SRA3 per big sheet"], pid))
    for code, items in fam.items():
        items.sort()
        per_g = sorted(v / g for g, v, _ in items)
        med = per_g[len(per_g) // 2]
        for g, v, pid in items:
            if len(items) >= 3 and (v / g > med * 2 or v / g < med / 2):
                rep.warn("Papers", f"{pid}: {v:.4f} per SRA3 is far from the other {code} weights — typo?")
        for (g1, v1, p1), (g2, v2, p2) in zip(items, items[1:]):
            if v2 < v1:
                rep.warn("Papers", f"{p2} is cheaper per SRA3 than the lighter {p1} — check")
    cre = [(p["price per big sheet (€)"] / p["SRA3 per big sheet"], pid) for pid, p in papers.items()
           if p.get("group") == "creative" and num(p.get("price per big sheet (€)")) and num(p.get("SRA3 per big sheet")) and p["SRA3 per big sheet"] > 0]
    if len(cre) >= 3:
        med = sorted(v for v, _ in cre)[len(cre) // 2]
        for v, pid in cre:
            if v > med * 4 or v < med / 4:
                rep.warn("Papers", f"{pid}: {v:.4f} per SRA3 is far from the other coloured papers — typo?")

    # clicks: two side-by-side tables
    c = wb["Clicks"]
    def tiers(col0):
        out = []
        r = 4
        while num(c.get((r, col0))):
            row = [c.get((r, col0 + i)) for i in range(4)]
            if not all(num(x) for x in row):
                rep.err("Clicks", f"row {r}: every cell of a price row must be a number")
            out.append({"max": row[0], "cmyk": row[1], "white": row[2], "black": row[3]})
            r += 1
        if not out:
            rep.err("Clicks", "click price table is empty")
        for a, b in zip(out, out[1:]):
            if not a["max"] < b["max"]:
                rep.err("Clicks", '"up to" must go up from row to row')
        return out
    d["tiers"], d["tiers_xxl"] = tiers(1), tiers(6)
    costs = {}
    r = find_row(c, 1, "cmyk_cost")
    if r:
        for i in range(4):
            if c.get((r + i, 1)):
                costs[c[(r + i, 1)]] = c.get((r + i, 2))
    if not num(costs.get("cmyk_cost")):
        rep.err("Clicks", "cmyk_cost (what a click costs us) is missing")
    d["click_costs"] = costs

    # sizes
    sizes = {}
    for rec in table(wb["Sizes"], 3):
        s = rec.get("size")
        if not s:
            continue
        if not num(rec.get("pieces per SRA3")) or rec["pieces per SRA3"] < 1:
            rep.err("Sizes", f'{s}: "pieces per SRA3" must be 1 or more')
        ac, al = rec.get("pieces across"), rec.get("pieces along")
        if not (num(ac) and num(al) and ac >= 1 and al >= 1):
            rep.err("Sizes", f'{s}: "pieces across" and "pieces along" must be 1 or more')
        elif num(rec.get("pieces per SRA3")) and ac * al != rec["pieces per SRA3"]:
            rep.err("Sizes", f'{s}: pieces across x along ({ac} x {al}) is not "pieces per SRA3" ({rec["pieces per SRA3"]})')
        else:
            mm = d["machine"]
            if all(num(mm.get(k)) for k in ("sra3_width", "sra3_height", "print_margin")) and num(rec.get("width (mm)")) and num(rec.get("height (mm)")):
                pw = mm["sra3_width"] - 2 * mm["print_margin"]; ph = mm["sra3_height"] - 2 * mm["print_margin"]
                g = mm.get("print_gap") if num(mm.get("print_gap")) else 0
                w, h = rec["width (mm)"], rec["height (mm)"]
                need = lambda n, x: n * x + (n - 1) * g
                fits = any(need(ac, a_) <= pw and need(al, b_) <= ph for a_, b_ in ((w, h), (h, w)))
                if ac * al > 1 and not fits and not rec.get("banner feed (tall poster)") == "yes":
                    rep.warn("Sizes", f'{s}: {ac} x {al} pieces of {w} x {h} mm (+{g} mm gaps) do not fit in the printable {pw:g} x {ph:g} mm of the print sheet')
        for p in split(rec.get("only on papers (code, comma separated)")):
            if not any(pid.split("-")[0] == p for pid in papers):
                rep.err("Sizes", f'{s}: paper code "{p}" does not exist in Papers')
        sizes[s] = rec
    d["sizes"] = sizes

    # folds: machine block (rows 4..), operations, fold types
    f = wb["Folds"]
    fm = {}
    r = 4
    while f.get((r, 1)):
        fm[str(f[(r, 1)])] = f.get((r, 2)); r += 1
    for k in FOLD_KEYS:
        if not num(fm.get(k)):
            rep.err("Folds", f'folding setting "{k}" is missing or not a number')
    d["fold_machine"] = fm
    ro = find_row(f, 1, "operation")
    ops = {}
    if not ro:
        rep.err("Folds", 'table "Fold operations" not found')
    else:
        for rec in table(f, ro):
            oid = rec.get("operation")
            if not oid or not num(rec.get("pass length from (mm)")):
                continue
            op = ops.setdefault(oid, {"setup": None, "bands": []})
            if num(rec.get("setup time (h)")):
                op["setup"] = rec["setup time (h)"]
            op["bands"].append((rec["pass length from (mm)"], rec["pass length to (mm)"], rec["time per sheet (s)"]))
        for oid, op in ops.items():
            if op["setup"] is None:
                rep.err("Folds", f'operation {oid}: setup time missing')
    d["fold_ops"] = ops
    rt = find_row(f, 1, "fold_id")
    folds = {}
    if not rt:
        rep.err("Folds", 'table "Fold types" not found')
    else:
        for rec in table(f, rt):
            fid = rec.get("fold_id")
            if not fid or not rec.get("flat size"):
                continue
            key = (fid, rec["flat size"])
            for k in ("operation",):
                if rec.get(k) not in ops:
                    rep.err("Folds", f'{fid} {rec["flat size"]}: operation "{rec.get(k)}" does not exist')
            folds[key] = rec
    d["folds"] = folds

    # brochures (optional tab): stitching by hand or with the tower, closed -> flat size
    d["brochure"] = None
    if "Brochures" in wb:
        bw = wb["Brochures"]
        bs = {}
        for rec in table(bw, 3):
            if rec.get("setting"):
                bs[str(rec["setting"])] = rec.get("value")
        for k in BRO_KEYS:
            if not num(bs.get(k)):
                rep.err("Brochures", f'setting "{k}" is missing or not a number')
        if bs.get("cover_crease_operation") not in ops:
            rep.err("Brochures", f'cover_crease_operation "{bs.get("cover_crease_operation")}" does not exist in the tab Folds')
        bsz, st, sp = {}, [], []
        r = find_row(bw, 1, "closed size")
        if not r:
            rep.err("Brochures", 'table "closed size / flat size" not found')
        else:
            for rec in table(bw, r):
                cs, fs = rec.get("closed size"), rec.get("flat size")
                if not cs:
                    continue
                if fs not in sizes:
                    rep.err("Brochures", f'{cs}: flat size "{fs}" does not exist in Sizes')
                bsz[str(cs)] = str(fs)
        r = find_row(bw, 1, "stations")
        if r:
            for rec in table(bw, r):
                if num(rec.get("stations")) and num(rec.get("setup time (h)")):
                    st.append((int(rec["stations"]), rec["setup time (h)"]))
        if not st:
            rep.err("Brochures", 'table "stations / setup time" not found or empty')
        r = find_row(bw, 1, "copies from")
        if r:
            for rec in table(bw, r):
                if all(num(rec.get(k)) for k in ("copies from", "copies to", "copies per hour")) and rec["copies per hour"] > 0:
                    sp.append((int(rec["copies from"]), int(rec["copies to"]), rec["copies per hour"]))
        if not sp:
            rep.err("Brochures", 'table "copies from / copies to / copies per hour" not found or empty')
        d["brochure"] = {"s": bs, "sizes": bsz, "stations": sorted(st), "speed": sorted(sp)}

    # products
    prods = {}
    for rec in table(wb["Products"], 3):
        pid = rec.get("product")
        if not pid:
            continue
        active = rec.get("active")
        if active not in ("yes", "no"):
            rep.err("Products", f'{pid}: active must be yes or no')
        sz = split(rec.get("sizes (comma separated)"))
        pp = split(rec.get("papers (paper_id, comma separated)"))
        fl = split(rec.get("folds (fold_id, comma separated)"))
        qs = split(rec.get("quantities (comma separated)"))
        bad = rep.err if active == "yes" else rep.warn
        for s in sz:
            if s not in sizes:
                bad("Products", f'{pid}: size "{s}" does not exist in Sizes')
        for p in pp:
            if p not in papers:
                bad("Products", f'{pid}: paper "{p}" does not exist in Papers')
            elif papers[p].get("active") != "yes":
                bad("Products", f'{pid}: paper "{p}" is not active')
        for fid in fl:
            for s in sz:
                if (fid, s) not in folds:
                    bad("Products", f'{pid}: fold "{fid}" has no row for size {s} in Folds')
        try:
            qn = [int(q) for q in qs]
            if qn != sorted(qn) or any(q <= 0 for q in qn):
                raise ValueError
        except ValueError:
            bad("Products", f'{pid}: quantities must be whole numbers, smallest first (now: {rec.get("quantities (comma separated)")!r})')
            qn = []
        dp = rec.get("default paper")
        if dp not in pp:
            bad("Products", f'{pid}: default paper "{dp}" is not in its paper list')
        inks = split(rec.get("inks"))
        if active == "yes" and inks != ["cmyk"]:
            rep.err("Products", f'{pid}: only "cmyk" can be sold for now (white/black toner comes later)')
        sides = split(rec.get("sides offered")) or ["single", "double"]
        if any(x not in ("single", "double") for x in sides):
            bad("Products", f'{pid}: sides offered must be single and/or double')
        dq = rec.get("default quantity")
        if num(dq) and int(dq) not in qn:
            bad("Products", f'{pid}: default quantity {dq} is not in its quantity list')
        od = rec.get("online discount")
        if od is not None and (not num(od) or od < 0 or od >= 1):
            bad("Products", f'{pid}: online discount must be empty or a percentage between 0 % and 99 % (now: {od!r})')
            od = None
        # floor price (22 Oct 2026): for papers within a weight range the product is sold at cost + margin, without the online
        # discount and with its own minimum job price. All four columns empty = the product keeps the general rules.
        fp = [rec.get("floor price from paper weight (g/m²)"), rec.get("floor price to paper weight (g/m²)"),
              rec.get("floor price margin"), rec.get("floor price min job price (€)")]
        floor = None
        if any(v is not None for v in fp):
            ok_fp = all(num(v) for v in fp[:3]) and (fp[3] is None or num(fp[3]))
            if not ok_fp or not 0 < fp[0] <= fp[1] or not 0 <= fp[2] < 5 or (fp[3] is not None and fp[3] < 0):
                bad("Products", f'{pid}: floor price needs paper weight from and to (g/m², from <= to), a margin (0.25 = 25 %) and optionally a minimum job price (now: {fp!r})')
            else:
                floor = {"from": float(fp[0]), "to": float(fp[1]), "margin": float(fp[2]), "min_job": float(fp[3]) if fp[3] is not None else None}
        vat, pf, mq = rec.get("VAT rate"), rec.get("prepress fee (€)"), rec.get("min quantity")
        if vat is not None and (not num(vat) or not 0 <= vat < 1):
            bad("Products", f'{pid}: VAT rate must be empty or a fraction (0.06 = 6 %) (now: {vat!r})'); vat = None
        if pf is not None and (not num(pf) or pf < 0):
            bad("Products", f'{pid}: prepress fee must be empty or a number (now: {pf!r})'); pf = None
        if mq is not None and (not num(mq) or mq < 1):
            bad("Products", f'{pid}: min quantity must be empty or 1 or more (now: {mq!r})'); mq = None
        try:
            pg = [int(x) for x in split(rec.get("pages (comma separated)"))]
            if any(x < 8 or x % 4 for x in pg):
                raise ValueError
        except ValueError:
            bad("Products", f'{pid}: pages must be whole numbers, 8 or more, multiples of 4 (now: {rec.get("pages (comma separated)")!r})')
            pg = []
        cov = split(rec.get("cover papers (paper_id, comma separated)"))
        if pg:
            if not d.get("brochure"):
                bad("Products", f'{pid}: has pages (brochure) but the tab Brochures is missing')
            else:
                for s in sz:
                    if s not in d["brochure"]["sizes"]:
                        bad("Products", f'{pid}: size {s} is not in the size table of the tab Brochures')
            if not cov:
                bad("Products", f'{pid}: a brochure needs at least one cover paper')
            for p in cov:
                if p not in papers:
                    bad("Products", f'{pid}: cover paper "{p}" does not exist in Papers')
                elif papers[p].get("active") != "yes":
                    bad("Products", f'{pid}: cover paper "{p}" is not active')
        elif cov:
            bad("Products", f'{pid}: cover papers are only for brochures (fill in pages too)')
        dpg = rec.get("default pages")
        if dpg is not None and (not num(dpg) or int(dpg) not in pg):
            bad("Products", f'{pid}: default pages {dpg!r} is not in its pages list'); dpg = None
        prods[pid] = {"active": active, "sizes": sz, "papers": pp, "folds": fl, "qtys": qn, "default": dp, "inks": inks,
                      "sides": sides, "default_qty": int(dq) if num(dq) else None,
                      "online_discount": float(od) if num(od) else None, "floor": floor,
                      "vat": float(vat) if num(vat) else None, "prepress": float(pf) if num(pf) else None,
                      "min_qty": int(mq) if num(mq) else None, "pages": pg, "cover_papers": cov,
                      "default_pages": int(dpg) if num(dpg) else (pg[0] if pg else None)}
    d["products"] = prods

    # test jobs
    jobs = []
    for rec in table(wb["Test jobs"], 3):
        if not rec.get("product"):
            continue
        jobs.append(rec)
    if not jobs:
        rep.warn("Test jobs", "no test jobs")
    d["jobs"] = jobs
    return d

# --------------------------------------------------------------------------- the price formula (keep identical to the server)

class PriceError(Exception):
    pass


def round_step(price, step, down):
    lower = math.floor(price / step) * step
    above = price - lower
    if above == 0:
        return price
    return lower if above <= down + 1e-9 else lower + step


def price(d, product, size, paper_id, qty, sides="single", versions=1, express=False, fold=None, delivery=False, ink="cmyk",
          pages=None, cover=None):
    pr = d["products"].get(product) or {}
    if pr.get("pages"):   # brochure: own formula (cover + inside + crease + stitching)
        return price_brochure(d, pr, size, paper_id, qty, sides, versions, express, delivery, ink, pages, cover)
    m, ru, fm = d["machine"], d["rules"], d["fold_machine"]
    sz = d["sizes"].get(size)
    pa = d["papers"].get(paper_id)
    if not sz:
        raise PriceError(f"unknown size {size}")
    if not pa:
        raise PriceError(f"unknown paper {paper_id}")
    only = split(sz.get("only on papers (code, comma separated)"))
    if only and paper_id.split("-")[0] not in only:
        raise PriceError(f"{paper_id} cannot be printed in size {size}")
    if ink not in ("cmyk", "black", "white"):
        raise PriceError(f"unknown ink {ink}")
    if pa.get("group") != "cmyk" and ink == "cmyk":
        raise PriceError("coloured papers are printed with white or black toner, not CMYK")
    if ink == "white" and pa.get("group") == "cmyk":
        raise PriceError("white toner only on coloured paper")
    long_ = sz.get("banner feed (tall poster)") == "yes"
    xxl = sz.get("XXL (A3 click price)") == "yes"
    pps = int(sz["pieces per SRA3"])
    versions = max(1, int(versions))
    double = sides == "double"

    step = ru.get("time_round_step") or 0
    def rt(h):  # folding: round to the NEAREST time_round_step (0.01 h, like Multipress)
        return math.floor(h / step + 0.5 + 1e-9) * step if step else h
    cstep = d["cut"]["cut_time_round"]
    def rn(h):  # cutting: round to the NEAREST cut_time_round (0.01 h, like Multipress)
        return math.floor(h / cstep + 0.5 + 1e-9) * cstep if cstep else h
    # paper in whole big sheets (you cannot buy part of a sheet)
    sheets = (math.ceil(qty / pps) + m["setup_sheets"]) * versions
    paper_cost = sheets * pa["price per big sheet (€)"] if long_ else math.ceil(sheets / pa["SRA3 per big sheet"]) * pa["price per big sheet (€)"]
    paper_sell = paper_cost * (1 + ru["paper_markup"])

    tiers = d["tiers_xxl"] if xxl else d["tiers"]
    # cumulative, like Multipress: each sheet is charged at the price of the step it falls in
    click_sell, done, low = 0.0, 0, 0
    for t in tiers:
        n = max(0, min(sheets, t["max"]) - done)
        click_sell += n * t[ink]
        done += n
        if done >= sheets:
            break
    if done < sheets:
        click_sell += (sheets - done) * tiers[-1][ink]
    click_sell *= (2 if double else 1)
    click_cost = sheets * d["click_costs"][f"{ink}_cost"] * (2 if double else 1)

    print_h = sheets / (m["machine_speed_long"] if long_ else m["machine_speed"])
    hours = m["labor_minutes"] / 60 + print_h                  # setup + handling (10 min) + printing
    pstep = m["press_time_round"]
    if pstep:
        hours = math.floor(hours / pstep + 0.5) * pstep        # like Multipress: nearest 0.01 h
    machine_cost = hours * m["machine_cost_rate"]
    machine_sell = hours * m["machine_sell_rate"]

    fold_cost = fold_sell = 0.0
    if fold:
        ft = d["folds"].get((fold, size))
        if not ft:
            raise PriceError(f"fold {fold} not possible on {size}")
        op_id = ft["operation"]   # setup by number of folds/scores, any paper weight
        op = d["fold_ops"][op_id]
        pl = max(ft["flat width (mm)"], ft["flat height (mm)"])  # the long side goes through the folder
        tps = next((b[2] for b in op["bands"] if b[0] <= pl <= b[1]), None)
        if tps is None:
            raise PriceError(f"pass length {pl} mm is outside the table of {op_id}")
        extra_folds = int(ft["long side divided by"]) - 2
        fh = rt(fm["fold_fixed_setup"] + op["setup"] + fm["fold_time_per_version"] * (versions - 1)
              + qty * versions * tps / 3600 * (1 + fm["fold_extra_fold_time"] * extra_folds))
        fold_cost = fh * fm["fold_cost_rate"]
        fold_sell = fh * fm["fold_sell_rate"]

    # cutting (Polar 76 XT), Multipress method: setup + table time (cuts, format) + trims, per pile
    ct, tab = d["cut"], d["cut_table"]
    def table_time(n, fmt):
        if n <= 0:
            return 0.0
        rows_ = sorted({r[0] for r in tab})
        row_n = next((r for r in rows_ if r >= n), rows_[-1])   # next row up; above the table: highest row
        return next((t for c_, f, t in tab if c_ == row_n and fmt <= f), [t for c_, f, t in tab if c_ == row_n][-1])
    net_sheets = math.ceil(qty / pps) * versions
    ac, al = int(sz["pieces across"]), int(sz["pieces along"])
    n_cuts, n_trims = ac + al + 2, ac + al - 2      # counted like Multipress (Cps / Rog.)
    print_fmt = m["sra3_width"] + m["sra3_height"]
    piles = math.ceil(net_sheets / ct["sheets_per_pile"])
    cut_h = rn(ct["cut_setup"] + (table_time(n_cuts, print_fmt) + n_trims * ct["trim_time"]) * piles)
    per_big = int(pa["SRA3 per big sheet"])
    if per_big > 1 and not long_ and str(d["cut"].get("precut_on", "no")).lower() == "yes":
        pc_cuts = {2: ct["precut_cuts_if_2"], 4: ct["precut_cuts_if_4"], 8: ct["precut_cuts_if_8"]}.get(per_big)
        if pc_cuts is None:
            raise PriceError(f"no pre-cut setting for {per_big} print sheets per big sheet")
        bw, bh = pa.get("big sheet width (mm)"), pa.get("big sheet height (mm)")
        big_fmt = bw + bh if num(bw) and num(bh) else 10 ** 9
        big_sheets = math.ceil(sheets / per_big)
        cut_h += rn(ct["cut_setup"] + table_time(pc_cuts, big_fmt) * math.ceil(big_sheets / ct["sheets_per_pile"]))
    cut_cost = cut_h * ct["cut_cost_rate"]
    cut_sell = cut_h * ct["cut_sell_rate"]

    cost = paper_cost + click_cost + machine_cost + fold_cost + cut_cost
    prep = pr.get("prepress")
    sell = paper_sell + click_sell + machine_sell + (m["prepress_fee"] if prep is None else prep) + fold_sell + cut_sell
    incl, excl = finish(d, pr, cost, sell, long_, express, delivery, pa.get("gsm"))
    return {"incl": incl, "excl": excl, "cost": round(cost, 2), "sheets": sheets}


def finish(d, pr, cost, sell, long_, express, delivery, gsm=None):
    """Rules after the cost lines: minimum margin, online discount, uplifts, VAT, rounding, delivery."""
    ru = d["rules"]
    fl = pr.get("floor")
    floor_on = bool(fl and gsm is not None and fl["from"] <= gsm <= fl["to"])
    if floor_on:
        # floor price (flyers, 22 Oct 2026): cost + the product's own margin; no general minimum margin, no online discount
        sell = cost * (1 + fl["margin"])
    else:
        if cost > 0 and (sell - cost) / cost < ru["min_margin"]:
            sell = cost * (1 + ru["min_margin"])
        # online discount (e-shop: no e-mails, no offers), but never below the minimum margin
        # per product (Products tab, column online discount); empty = the general rule. Folders, brochures: 0 (Kris, 17 Oct)
        disc = pr.get("online_discount")
        if disc is None:
            disc = ru["online_discount"]
        sell = max(sell * (1 - disc), cost * (1 + ru["min_margin"]))
    if long_:
        sell *= 1 + ru["long_size_uplift"]
    if express:
        sell *= 1 + ru["express_markup"]
    vat = pr.get("vat")   # per product; empty (all products today, since 21 Oct 2026) = Pricing rules vat_rate (21 %)
    if vat is None:
        vat = ru["vat_rate"]
    incl = round_step(sell * (1 + vat), ru["round_step"], ru["round_down_threshold"])
    incl = max(incl, fl["min_job"] if floor_on and fl["min_job"] is not None else ru["min_job_price"])
    if delivery:
        incl += ru["delivery_dpd"]
    if vat == ru["vat_rate"]:
        excl = incl / (1 + vat)
    else:   # DPD delivery is always at the normal VAT rate
        dl = ru["delivery_dpd"] if delivery else 0
        excl = (incl - dl) / (1 + vat) + dl / (1 + ru["vat_rate"])
    return round(incl, 2), round(excl, 2)


def cut_table_time(d, n, fmt):
    tab = d["cut_table"]
    if n <= 0:
        return 0.0
    rows_ = sorted({r[0] for r in tab})
    row_n = next((r for r in rows_ if r >= n), rows_[-1])
    return next((t for c_, f, t in tab if c_ == row_n and fmt <= f), [t for c_, f, t in tab if c_ == row_n][-1])


def brochure_stitch(d, qty, pages):
    """Stitching hours and machine: by hand, or the tower when copies AND pages are above the limits (Kris, 17 Oct)."""
    b = d["brochure"]; bs = b["s"]
    stations = pages // 4
    if qty > bs["tower_when_copies_above"] and pages > bs["tower_when_pages_above"]:
        base = next((t for s_, t in b["stations"] if s_ >= stations), b["stations"][-1][1])
        run = 0.0
        for f_, t_, sp in b["speed"]:   # smoothed: every band at its own speed
            run += max(0, min(qty, t_) - (f_ - 1)) / sp
        h, how = base + bs["tower_setup_extra"] + run, "tower"
    else:
        h, how = bs["manual_setup"] + qty / bs["manual_speed"], "manual"
    st = bs["stitch_time_round"]
    if st:
        h = math.floor(h / st + 0.5 + 1e-9) * st
    return h, how


def price_brochure(d, pr, size, inside_id, qty, sides, versions, express, delivery, ink, pages, cover):
    """Saddle-stitched brochure: cover run + inside run (each: paper, clicks, press, pre-cut), crease the cover,
    stitch, prepress. Lines are summed in this order in all three engines."""
    b = d.get("brochure")
    if not b:
        raise PriceError("tab Brochures is missing")
    m, ru, fm, ct = d["machine"], d["rules"], d["fold_machine"], d["cut"]
    if int(versions) != 1:
        raise PriceError("brochures: one version per job")
    if sides != "double":
        raise PriceError("brochures are printed on both sides (4/4)")
    if ink not in ("cmyk", "black", "white"):
        raise PriceError(f"unknown ink {ink}")
    try:
        pages = int(pages)
    except (TypeError, ValueError):
        raise PriceError("number of pages missing")
    if pages < 8 or pages % 4:
        raise PriceError("pages: 8 or more, a multiple of 4")
    if not qty or qty <= 0:
        raise PriceError("quantity must be above 0")
    cover = cover or (pr["cover_papers"][0] if pr.get("cover_papers") else None)
    flat = b["sizes"].get(size)
    fz = d["sizes"].get(flat) if flat else None
    if not fz:
        raise PriceError(f"no brochure size {size}")
    pps = int(fz["pieces per SRA3"])
    tiers = d["tiers_xxl"] if fz.get("XXL (A3 click price)") == "yes" else d["tiers"]
    stations = pages // 4
    lines = []   # (cost, sell)
    sheets_total = 0
    for pid, n in ((cover, qty), (inside_id, qty * (stations - 1))):
        pa = d["papers"].get(pid)
        if not pa:
            raise PriceError(f"unknown paper {pid}")
        if pa.get("group") != "cmyk" and ink == "cmyk":
            raise PriceError("coloured papers are printed with white or black toner, not CMYK")
        if ink == "white" and pa.get("group") == "cmyk":
            raise PriceError("white toner only on coloured paper")
        sheets = math.ceil(n / pps) + m["setup_sheets"]
        sheets_total += sheets
        per = pa["SRA3 per big sheet"]
        big = math.ceil(sheets / per)
        pc = big * pa["price per big sheet (€)"]
        lines.append((pc, pc * (1 + ru["paper_markup"])))
        click_sell, done = 0.0, 0
        for t in tiers:
            k = max(0, min(sheets, t["max"]) - done)
            click_sell += k * t[ink]
            done += k
            if done >= sheets:
                break
        if done < sheets:
            click_sell += (sheets - done) * tiers[-1][ink]
        click_sell *= 2
        lines.append((sheets * d["click_costs"][f"{ink}_cost"] * 2, click_sell))
        hours = m["labor_minutes"] / 60 + sheets / m["machine_speed"]
        if m["press_time_round"]:
            hours = math.floor(hours / m["press_time_round"] + 0.5) * m["press_time_round"]
        lines.append((hours * m["machine_cost_rate"], hours * m["machine_sell_rate"]))
        if int(per) > 1 and str(ct.get("precut_on", "no")).lower() == "yes":
            pc_cuts = {2: ct["precut_cuts_if_2"], 4: ct["precut_cuts_if_4"], 8: ct["precut_cuts_if_8"]}.get(int(per))
            if pc_cuts is None:
                raise PriceError(f"no pre-cut setting for {per} print sheets per big sheet")
            bw, bh = pa.get("big sheet width (mm)"), pa.get("big sheet height (mm)")
            big_fmt = bw + bh if num(bw) and num(bh) else 10 ** 9
            step = ct["cut_time_round"]
            pch = ct["cut_setup"] + cut_table_time(d, pc_cuts, big_fmt) * math.ceil(big / ct["sheets_per_pile"])
            if step:
                pch = math.floor(pch / step + 0.5 + 1e-9) * step
            lines.append((pch * ct["cut_cost_rate"], pch * ct["cut_sell_rate"]))
    # crease the cover (same operation and speed as a folder with 1 fold)
    op = d["fold_ops"][b["s"]["cover_crease_operation"]]
    pl = max(fz["width (mm)"], fz["height (mm)"])
    tps = next((x[2] for x in op["bands"] if x[0] <= pl <= x[1]), None)
    if tps is None:
        raise PriceError(f"pass length {pl} mm is outside the table of {b['s']['cover_crease_operation']}")
    fh = fm["fold_fixed_setup"] + op["setup"] + qty * tps / 3600
    step = ru.get("time_round_step") or 0
    if step:
        fh = math.floor(fh / step + 0.5 + 1e-9) * step
    lines.append((fh * fm["fold_cost_rate"], fh * fm["fold_sell_rate"]))
    sh, _how = brochure_stitch(d, qty, pages)
    lines.append((sh * b["s"]["stitch_cost_rate"], sh * b["s"]["stitch_sell_rate"]))
    prep = pr.get("prepress")
    lines.append((0.0, m["prepress_fee"] if prep is None else prep))
    cost = sell = 0.0
    for c_, s_ in lines:
        cost += c_
        sell += s_
    incl, excl = finish(d, pr, cost, sell, False, express, delivery)
    return {"incl": incl, "excl": excl, "cost": round(cost, 2), "sheets": sheets_total}


def run_job(d, j):
    return price(d, j["product"], j["size"], j["paper_id"], int(j["quantity"]), j.get("sides") or "single",
                 int(j.get("versions") or 1), j.get("express") == "yes", j.get("fold"),
                 pages=j.get("pages"), cover=j.get("cover paper_id"))

# --------------------------------------------------------------------------- report

def page(new_name, old_name, rep, rows, ok):
    e = html.escape
    out = [f"""<!doctype html><meta charset="utf-8"><title>Price check {e(new_name)}</title>
<style>body{{font:15px/1.5 -apple-system,Arial,sans-serif;margin:32px;color:#1A0066;background:#FFF5EF}}
h1{{color:#4200FF;margin:0 0 4px}} table{{border-collapse:collapse;margin:12px 0 28px;background:#fff}}
td,th{{border:1px solid #e6dcd3;padding:5px 10px;text-align:left}} th{{background:#4200FF;color:#fff}}
.ok{{background:#2e7d32;color:#fff;padding:10px 16px;border-radius:8px;display:inline-block;font-weight:700}}
.bad{{background:#c00000;color:#fff;padding:10px 16px;border-radius:8px;display:inline-block;font-weight:700}}
.up{{color:#c00000}} .down{{color:#2e7d32}} td.n{{text-align:right}}</style>
<h1>Price check</h1><p>New file: <b>{e(new_name)}</b><br>Compared with: <b>{e(old_name or "— (no older version)")}</b><br>
Checked: {dt.datetime.now():%d/%m/%Y %H:%M}</p>"""]
    out.append('<p class="ok">OK — ready to upload: upload/price-data.xlsx</p>' if ok else
               '<p class="bad">NOT OK — fix the errors below. Nothing was prepared for upload.</p>')
    for title, items in (("Errors (must be fixed)", rep.errors), ("Warnings (check, but not blocking)", rep.warnings)):
        out.append(f"<h2>{title}: {len(items)}</h2>")
        if items:
            out.append("<table><tr><th>tab</th><th>what</th></tr>" + "".join(f"<tr><td>{e(t)}</td><td>{e(m)}</td></tr>" for t, m in items) + "</table>")
    if rows:
        out.append("<h2>Test jobs (incl. VAT)</h2><table><tr><th>#</th><th>job</th><th>old</th><th>new</th><th>change</th><th>you expected</th></tr>")
        for r in rows:
            ch = ""
            if isinstance(r["old"], float) and isinstance(r["new"], float):
                diff = r["new"] - r["old"]
                pct = diff / r["old"] * 100 if r["old"] else 0
                cls = "up" if diff > 0 else "down" if diff < 0 else ""
                ch = f'<span class="{cls}">{diff:+.2f} ({pct:+.1f} %)</span>' if diff else "—"
            fmt = lambda v: f"{v:.2f}" if isinstance(v, float) else e(str(v or ""))
            out.append(f'<tr><td>{r["n"]}</td><td>{e(r["label"])}</td><td class="n">{fmt(r["old"])}</td><td class="n">{fmt(r["new"])}</td><td class="n">{ch}</td><td class="n">{fmt(r["exp"])}</td></tr>')
        out.append("</table>")
    return "\n".join(out)


def make_selftest(d):
    """A grid of jobs priced by THIS script (the master). The shop backend (engine.php) and the
    internal calculator (engine.js) recalculate every job and refuse to work if one price differs."""
    jobs = []
    for pid, p in d["products"].items():
        inks = p["inks"] or ["cmyk"]
        modes = [(sd, ink) for ink in inks for sd in (("single", "double") if ink == "cmyk" else ("double",))]
        qs = sorted(set(p["qtys"] + [25, 37, 1234]))
        if p["pages"]:   # brochures: 4/4 only; a few page counts, every cover paper
            for size in p["sizes"]:
                for paper in p["papers"]:
                    for cov in p["cover_papers"]:
                        for pg in sorted({p["pages"][0], 20, 24, p["pages"][-1]}):
                            for q in qs:
                                for v, ex, dl in ((1, False, False), (3, False, False), (1, True, True)):
                                    o = {"product": pid, "size": size, "paperId": paper, "qty": q, "sides": "double", "versions": v,
                                         "express": ex, "fold": None, "delivery": dl, "ink": "cmyk", "pages": pg, "coverPaperId": cov}
                                    try:
                                        incl = price(d, pid, size, paper, q, "double", v, ex, None, dl, ink="cmyk", pages=pg, cover=cov)["incl"]
                                    except Exception:
                                        incl = None
                                    jobs.append({"o": o, "incl": incl})
            continue
        for size in p["sizes"]:
            for fold in (p["folds"] or [None]):
                for paper in p["papers"]:
                    for q in qs:
                        for sd, ink in modes:
                            for v, ex, dl in ((1, False, False), (3, False, False), (1, True, True)):
                                o = {"product": pid, "size": size, "paperId": paper, "qty": q, "sides": sd, "versions": v,
                                     "express": ex, "fold": fold, "delivery": dl, "ink": ink}
                                try:
                                    incl = price(d, pid, size, paper, q, sd, v, ex, fold, dl, ink=ink)["incl"]
                                except Exception:
                                    incl = None
                                jobs.append({"o": o, "incl": incl})
    return jobs


def build_upload(new_name, d):
    """One folder that mirrors the server: drag its content onto /sites/clickclick.be/ (overwrite)."""
    import json
    up = os.path.join(HERE, "upload")
    # remove files from older versions of this script (a loose price-data.xlsx here could
    # otherwise be dragged onto the server root by mistake)
    for old in ("price-data.xlsx", "VERSION.txt"):
        try:
            os.remove(os.path.join(up, old))
        except OSError:
            pass
    # calculator-v2 no longer has its own price file (25 Oct 2026): remove the old copy from upload/
    # so it cannot be dragged onto the server again.
    shutil.rmtree(os.path.join(up, "calculator-v2"), ignore_errors=True)
    st = {"version": new_name, "made": f"{dt.datetime.now():%Y-%m-%d %H:%M}", "jobs": make_selftest(d)}
    targets = {"shop/api/data": os.path.join(up, "shop", "api", "data")}   # on the server the shop folder is called shop since 22 Oct 2026
    for t in targets.values():
        os.makedirs(t, exist_ok=True)
        shutil.copy2(os.path.join(HERE, new_name), os.path.join(t, "price-data.xlsx"))
        with open(os.path.join(t, "selftest.json"), "w") as fh:
            json.dump(st, fh, separators=(",", ":"))
    with open(os.path.join(up, "UPLOAD.txt"), "w") as fh:
        fh.write(f"Prices: {new_name}, checked {st['made']}\n\n"
                 "In FileZilla: open /sites/clickclick.be/ on the server (right side),\n"
                 "then drag the folder  shop  from this folder onto it.\n"
                 "Choose Overwrite. Only the two price files are replaced; nothing else changes.\n\n"
                 "  shop/api/data/price-data.xlsx + selftest.json -> the shop, the chat AND calculator-v2\n"
                 "  (one price file on the server since 25 Oct 2026: calculator-v2 reads the shop's copy)\n\n"
                 f"selftest.json holds {len(st['jobs'])} prices calculated by this script. The shop and the\n"
                 "internal calculator recalculate them and stop (instead of showing a wrong price) if one differs.\n")
    # keep the local copy of the shop in sync too (the folder shop-v2 inside ClickClick-Web-tools;
    # calculator-v2 has no price file of its own any more)
    root = os.path.abspath(os.path.join(HERE, ".."))
    locals_ = []
    for dirpath, dirnames, _ in os.walk(root):
        dirnames[:] = [x for x in dirnames if x not in ("node_modules", ".git", "upload", "archive", "Archive", ".claude")]
        name = os.path.basename(dirpath)
        if name == "shop-v2" and os.path.isdir(os.path.join(dirpath, "api", "data")):
            locals_.append(os.path.join(dirpath, "api", "data"))
    for local in locals_:
        if os.path.isdir(local):
            for f in ("price-data.xlsx", "selftest.json"):
                src = os.path.join(targets["shop/api/data"], f)
                shutil.copy2(src, os.path.join(local, f))
    return len(st["jobs"])


def main():
    files = sorted((m.group(1), f) for f in os.listdir(HERE) if (m := PATTERN.match(f)))
    if not files:
        print("No file print-calculator-values_YYYY-MM-DD.xlsx found in", HERE)
        return 1
    new_date, new_name = files[-1]
    old_name = files[-2][1] if len(files) > 1 else None
    print(f"Checking {new_name}" + (f"  (compared with {old_name})" if old_name else ""))
    if os.path.exists(os.path.join(HERE, "~$" + new_name)):
        print("Note: the file seems to be open in Excel. Save it first — the check reads the saved file.")

    rep = Report()
    try:
        new = load(os.path.join(HERE, new_name), rep)
    except Exception as ex:
        new = None
        rep.err("file", f"the file could not be read ({ex}). Is it a normal .xlsx saved by Excel?")
    old = None
    if old_name:
        try:
            old = load(os.path.join(HERE, old_name), Report())
        except Exception as ex:  # an older layout cannot be compared
            print("  (older version could not be read for comparison:", ex, ")")

    rows = []
    if new:
        for i, j in enumerate(new["jobs"], 1):
            label = f'{j.get("product")} {j.get("size")}' + (f' {j["fold"]}' if j.get("fold") else "") + \
                    (f' {j["pages"]} p. cover {j.get("cover paper_id")}' if j.get("pages") else "") + \
                    f' · {j.get("paper_id")} · {j.get("quantity")} · {j.get("sides")}'
            try:
                nv = float(run_job(new, j)["incl"])
            except (PriceError, KeyError, TypeError, ValueError, ZeroDivisionError) as ex:
                nv = f"error: {ex}"
                rep.err("Test jobs", f"job {i} ({label}): {ex}")
            ov = ""
            if old:
                try:
                    ov = float(run_job(old, j)["incl"])
                except Exception as ex:
                    ov = "—"
            exp = j.get("your expected price incl. VAT (€)")
            rows.append({"n": i, "label": label, "old": ov, "new": nv, "exp": float(exp) if num(exp) else ""})
            if num(exp) and isinstance(nv, float) and abs(nv - exp) > max(2, exp * 0.05):
                rep.warn("Test jobs", f"job {i} ({label}): calculated {nv:.2f}, you expected {exp:.2f}")

    ok = new is not None and not rep.errors
    n_self = 0
    if ok:
        n_self = build_upload(new_name, new)

    os.makedirs(os.path.join(HERE, "reports"), exist_ok=True)
    rp = os.path.join(HERE, "reports", f"check_{dt.datetime.now():%Y-%m-%d_%H%M}.html")
    with open(rp, "w", encoding="utf-8") as fh:
        fh.write(page(new_name, old_name, rep, rows, ok))

    print(f"\n{len(rep.errors)} error(s), {len(rep.warnings)} warning(s)")
    for t, msg in rep.errors:
        print(f"  ERROR   [{t}] {msg}")
    for t, msg in rep.warnings:
        print(f"  warning [{t}] {msg}")
    print(f"\nOK — the folder 'upload' is ready ({n_self} self-test prices). See upload/UPLOAD.txt." if ok
          else "\nNOT OK — nothing prepared for upload.")
    print("Report:", rp)
    if "--no-open" not in sys.argv:
        try:
            webbrowser.open("file://" + rp)
        except Exception:
            pass
    return 0 if ok else 2


if __name__ == "__main__":
    sys.exit(main())
