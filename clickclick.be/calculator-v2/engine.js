/*
 * Click Click Graphics — price engine (v2), read from print-calculator-values / price-data.xlsx.
 * JavaScript copy of the price formula in pricing/check_prices.py. Keep the two identical:
 * the check script compares them on every test job.
 * Works in the browser (window.CCEngine, needs SheetJS as window.XLSX) and in Node (module.exports).
 */
(function (root) {
  "use strict";

  // ------------------------------------------------------------------ reading the workbook
  function sheetCells(ws, XLSX) {
    const cells = new Map();
    for (const ref of Object.keys(ws)) {
      if (ref[0] === "!") continue;
      const c = ws[ref];
      let v = c.v;
      if (v === undefined || v === null) continue;
      if (c.t === "b" || c.t === "e") continue;
      if (typeof v === "string") { v = v.trim(); if (v === "") continue; }
      const a = XLSX.utils.decode_cell(ref);
      cells.set((a.r + 1) + ":" + (a.c + 1), v);
    }
    return { get: (r, c) => cells.get(r + ":" + c), has: (r, c) => cells.has(r + ":" + c) };
  }

  function table(cells, headerRow, firstCol = 1) {
    const heads = [];
    for (let c = firstCol; cells.has(headerRow, c); c++) heads.push([c, String(cells.get(headerRow, c))]);
    const rows = [];
    for (let r = headerRow + 1; r <= headerRow + 2000; r++) {
      const rec = {}; let empty = true;
      for (const [c, h] of heads) { const v = cells.get(r, c); rec[h] = v === undefined ? null : v; if (v !== undefined) empty = false; }
      if (empty) break;
      rec._row = r; rows.push(rec);
    }
    return rows;
  }

  function findRow(cells, col, text) {
    for (let r = 1; r < 400; r++) if (cells.get(r, col) === text) return r;
    return null;
  }

  const num = (v) => typeof v === "number" && isFinite(v);
  const split = (v) => (v === null || v === undefined) ? [] : String(v).split(",").map((x) => x.trim()).filter(Boolean);

  const REQUIRED = ["Papers", "Clicks", "Machine", "Pricing rules", "Sizes", "Cutting", "Folds", "Products", "Test jobs"];

  function load(wb, XLSX) {
    const errors = [];
    const sh = {};
    for (const t of REQUIRED) {
      if (!wb.Sheets[t]) errors.push(`Tab "${t}" is missing`); else sh[t] = sheetCells(wb.Sheets[t], XLSX);
    }
    if (errors.length) return { errors };
    const d = { errors };

    const kv2 = (cells) => { const o = {}; for (const rec of table(cells, 3)) if (rec.setting) o[String(rec.setting)] = rec.value; return o; };
    const kv = (tab) => kv2(sh[tab]);
    d.machine = kv("Machine"); d.rules = kv("Pricing rules"); d.cut = kv("Cutting");
    const need = { machine: ["machine_speed", "machine_speed_long", "machine_cost_rate", "machine_sell_rate", "labor_minutes", "press_time_round", "setup_sheets", "prepress_fee", "sra3_width", "sra3_height"],
      rules: ["time_round_step", "online_discount", "paper_markup", "min_margin", "vat_rate", "express_markup", "round_step", "round_down_threshold", "long_size_uplift", "min_job_price", "delivery_dpd"],
      cut: ["cut_sell_rate", "cut_cost_rate", "cut_setup", "trim_time", "cut_time_round", "sheets_per_pile", "precut_cuts_if_2", "precut_cuts_if_4", "precut_cuts_if_8"] };
    for (const [grp, keys] of Object.entries(need)) for (const k of keys) if (!num(d[grp][k])) errors.push(`Setting "${k}" is missing or not a number`);

    const cw = sh["Cutting"]; const hr = findRow(cw, 1, "cuts"); d.cutTable = [];
    if (hr) for (const rec of table(cw, hr)) if (num(rec.cuts) && num(rec["format up to (mm)"]) && num(rec["time (h)"])) d.cutTable.push([rec.cuts, rec["format up to (mm)"], rec["time (h)"]]);
    d.cutTable.sort((a, b) => a[0] - b[0] || a[1] - b[1]);
    if (!d.cutTable.length) errors.push("Cut table not found");

    d.papers = {};
    for (const rec of table(sh["Papers"], 3)) if (rec.paper_id) d.papers[rec.paper_id] = rec;

    const c = sh["Clicks"];
    const tiers = (col0) => { const out = []; for (let r = 4; num(c.get(r, col0)); r++) out.push({ max: c.get(r, col0), cmyk: c.get(r, col0 + 1), white: c.get(r, col0 + 2), black: c.get(r, col0 + 3) }); return out; };
    d.tiers = tiers(1); d.tiersXXL = tiers(6);
    d.clickCosts = {}; const rc = findRow(c, 1, "cmyk_cost");
    if (rc) for (let i = 0; i < 4; i++) if (c.get(rc + i, 1)) d.clickCosts[c.get(rc + i, 1)] = c.get(rc + i, 2);

    d.sizes = {};
    for (const rec of table(sh["Sizes"], 3)) if (rec.size) d.sizes[rec.size] = rec;

    const f = sh["Folds"]; d.foldMachine = {};
    for (let r = 4; f.get(r, 1); r++) d.foldMachine[String(f.get(r, 1))] = f.get(r, 2);
    d.foldOps = {}; const ro = findRow(f, 1, "operation");
    if (ro) for (const rec of table(f, ro)) {
      if (!rec.operation || !num(rec["pass length from (mm)"])) continue;
      const op = d.foldOps[rec.operation] || (d.foldOps[rec.operation] = { setup: null, name: null, bands: [] });
      if (num(rec["setup time (h)"])) op.setup = rec["setup time (h)"];
      if (rec["name in Multipress"]) op.name = rec["name in Multipress"];
      op.bands.push([rec["pass length from (mm)"], rec["pass length to (mm)"], rec["time per sheet (s)"]]);
    }
    d.folds = {}; const rt = findRow(f, 1, "fold_id");
    if (rt) for (const rec of table(f, rt)) if (rec.fold_id && rec["flat size"]) d.folds[rec.fold_id + "|" + rec["flat size"]] = rec;

    // brochures (optional tab): stitching by hand or with the tower, closed -> flat size
    d.brochure = null;
    if (wb.Sheets["Brochures"]) {
      const bw = sheetCells(wb.Sheets["Brochures"], XLSX);
      const s = kv2(bw);
      for (const k of ["stitch_cost_rate", "stitch_sell_rate", "stitch_time_round", "tower_when_copies_above", "tower_when_pages_above", "manual_setup", "manual_speed", "tower_setup_extra"])
        if (!num(s[k])) errors.push(`Brochures: setting "${k}" is missing or not a number`);
      const b = { s, sizes: {}, stations: [], speed: [] };
      const r1 = findRow(bw, 1, "closed size");
      if (r1) for (const rec of table(bw, r1)) if (rec["closed size"]) b.sizes[String(rec["closed size"])] = String(rec["flat size"]);
      const r2 = findRow(bw, 1, "stations");
      if (r2) for (const rec of table(bw, r2)) if (num(rec.stations) && num(rec["setup time (h)"])) b.stations.push([rec.stations, rec["setup time (h)"]]);
      const r3 = findRow(bw, 1, "copies from");
      if (r3) for (const rec of table(bw, r3)) if (num(rec["copies from"]) && num(rec["copies to"]) && num(rec["copies per hour"]) && rec["copies per hour"] > 0) b.speed.push([rec["copies from"], rec["copies to"], rec["copies per hour"]]);
      b.stations.sort((a, c) => a[0] - c[0] || a[1] - c[1]); b.speed.sort((a, c) => a[0] - c[0] || a[1] - c[1] || a[2] - c[2]);
      d.brochure = b;
    }

    d.products = {};
    for (const rec of table(sh["Products"], 3)) {
      if (!rec.product) continue;
      d.products[rec.product] = {
        id: rec.product, name: rec.name || rec.product, active: rec.active === "yes",
        sizes: split(rec["sizes (comma separated)"]), folds: split(rec["folds (fold_id, comma separated)"]),
        papers: split(rec["papers (paper_id, comma separated)"]), qtys: split(rec["quantities (comma separated)"]).map(Number),
        defaultPaper: rec["default paper"], days: rec["production days"], inks: split(rec.inks),
        sides: split(rec["sides offered"]).length ? split(rec["sides offered"]) : ["single", "double"],
        defaultQty: num(rec["default quantity"]) ? rec["default quantity"] : null,
        onlineDiscount: num(rec["online discount"]) ? rec["online discount"] : null,
        // floor price (22 Oct 2026): papers within the weight range are sold at cost + margin (no online discount, own minimum job price)
        floor: num(rec["floor price from paper weight (g/m²)"]) && num(rec["floor price to paper weight (g/m²)"]) && num(rec["floor price margin"])
          ? { from: rec["floor price from paper weight (g/m²)"], to: rec["floor price to paper weight (g/m²)"], margin: rec["floor price margin"],
              minJob: num(rec["floor price min job price (€)"]) ? rec["floor price min job price (€)"] : null } : null,
        vat: num(rec["VAT rate"]) ? rec["VAT rate"] : null,
        prepress: num(rec["prepress fee (€)"]) ? rec["prepress fee (€)"] : null,
        minQty: num(rec["min quantity"]) ? rec["min quantity"] : null,
        pages: split(rec["pages (comma separated)"]).map((x) => parseInt(x, 10)).filter((x) => x >= 8 && x % 4 === 0),
        coverPapers: split(rec["cover papers (paper_id, comma separated)"]),
        defaultPages: num(rec["default pages"]) ? rec["default pages"] : null,
      };
    }
    d.jobs = table(sh["Test jobs"], 3).filter((r) => r.product);
    return d;
  }

  // ------------------------------------------------------------------ the price formula (identical to check_prices.py)
  function roundStep(price, step, down) {
    const lower = Math.floor(price / step) * step;
    const above = price - lower;
    if (above === 0) return price;
    return above <= down + 1e-9 ? lower : lower + step;
  }

  function price(d, o) {
    const pr = d.products[o.product] || {};
    if (pr.pages && pr.pages.length) return priceBrochure(d, pr, o);
    const { product, size, paperId, qty } = o;
    const sides = o.sides || "single", versions = Math.max(1, parseInt(o.versions || 1, 10));
    const express = !!o.express, fold = o.fold || null, delivery = !!o.delivery, ink = o.ink || "cmyk";
    const m = d.machine, ru = d.rules, fm = d.foldMachine, ct = d.cut;
    const sz = d.sizes[size], pa = d.papers[paperId];
    if (!sz) throw new Error(`Unknown size ${size}`);
    if (!pa) throw new Error(`Unknown paper ${paperId}`);
    if (!(qty > 0)) throw new Error("Quantity must be above 0");
    const only = split(sz["only on papers (code, comma separated)"]);
    if (only.length && !only.includes(paperId.split("-")[0])) throw new Error(`${paperId} cannot be printed in size ${size}`);
    if (!["cmyk", "black", "white"].includes(ink)) throw new Error(`Unknown ink ${ink}`);
    if (pa.group !== "cmyk" && ink === "cmyk") throw new Error("Coloured papers are printed with white or black toner, not CMYK");
    if (ink === "white" && pa.group === "cmyk") throw new Error("White toner only on coloured paper");
    const long_ = sz["banner feed (tall poster)"] === "yes";
    const xxl = sz["XXL (A3 click price)"] === "yes";
    const pps = parseInt(sz["pieces per SRA3"], 10);
    const double = sides === "double";
    const lines = [];

    const step = ru.time_round_step || 0;
    const rt = (h) => step ? Math.floor(h / step + 0.5 + 1e-9) * step : h;
    const cstep = ct.cut_time_round;
    const rn = (h) => cstep ? Math.floor(h / cstep + 0.5 + 1e-9) * cstep : h;

    // paper
    const sheets = (Math.ceil(qty / pps) + m.setup_sheets) * versions;
    const perBig = pa["SRA3 per big sheet"];
    const bigSheets = long_ ? sheets : Math.ceil(sheets / perBig);
    const paperCost = long_ ? sheets * pa["price per big sheet (€)"] : bigSheets * pa["price per big sheet (€)"];
    const paperSell = paperCost * (1 + ru.paper_markup);
    lines.push({ key: "paper", label: "Paper", detail: `${sheets} print sheets (${Math.ceil(qty / pps) * versions} + ${m.setup_sheets * versions} setup) · ${long_ ? sheets + " sheets" : bigSheets + " big sheets (" + perBig + " per big)"} × €${pa["price per big sheet (€)"]} + ${Math.round(ru.paper_markup * 100)}%`, cost: paperCost, sell: paperSell });

    // clicks (cumulative tiers)
    const tiers = xxl ? d.tiersXXL : d.tiers;
    let clickSell = 0, done = 0; const parts = [];
    for (const t of tiers) {
      const n = Math.max(0, Math.min(sheets, t.max) - done);
      if (n > 0) parts.push(`${n} × ${t[ink]}`);
      clickSell += n * t[ink]; done += n;
      if (done >= sheets) break;
    }
    if (done < sheets) { const t = tiers[tiers.length - 1]; parts.push(`${sheets - done} × ${t[ink]}`); clickSell += (sheets - done) * t[ink]; }
    clickSell *= double ? 2 : 1;
    const clickCost = sheets * d.clickCosts[ink + "_cost"] * (double ? 2 : 1);
    lines.push({ key: "clicks", label: "Clicks", detail: `${ink === "cmyk" ? "" : ink + " toner · "}${xxl ? "XXL " : ""}${parts.join(" + ")}${double ? " · × 2 sides" : ""}`, cost: clickCost, sell: clickSell });

    // press
    const printH = sheets / (long_ ? m.machine_speed_long : m.machine_speed);
    let hours = m.labor_minutes / 60 + printH;
    const pstep = m.press_time_round;
    if (pstep) hours = Math.floor(hours / pstep + 0.5) * pstep;
    lines.push({ key: "press", label: "Press", detail: `${m.labor_minutes} min + ${sheets}/${long_ ? m.machine_speed_long : m.machine_speed} h → ${hours.toFixed(2)} h × €${m.machine_sell_rate}`, cost: hours * m.machine_cost_rate, sell: hours * m.machine_sell_rate, hours });

    // folding
    if (fold) {
      const ft = d.folds[fold + "|" + size];
      if (!ft) throw new Error(`Fold ${fold} not possible on ${size}`);
      const op = d.foldOps[ft.operation];
      if (!op) throw new Error(`Fold operation ${ft.operation} missing`);
      const pl = Math.max(ft["flat width (mm)"], ft["flat height (mm)"]);
      const band = op.bands.find((b) => b[0] <= pl && pl <= b[1]);
      if (!band) throw new Error(`Pass length ${pl} mm is outside the table of ${ft.operation}`);
      const tps = band[2];
      const extra = parseInt(ft["long side divided by"], 10) - 2;
      const fh = rt(fm.fold_fixed_setup + op.setup + fm.fold_time_per_version * (versions - 1) + qty * versions * tps / 3600 * (1 + fm.fold_extra_fold_time * extra));
      lines.push({ key: "fold", label: "Folding", detail: `${op.name || ft.operation}: ${op.setup} h setup + ${qty * versions} × ${tps} s${extra ? " + " + Math.round(fm.fold_extra_fold_time * 100 * extra) + "%" : ""} → ${fh.toFixed(2)} h × €${fm.fold_sell_rate}`, cost: fh * fm.fold_cost_rate, sell: fh * fm.fold_sell_rate, hours: fh });
    }

    // cutting
    const tab = d.cutTable;
    const tableTime = (n, fmt) => {
      if (n <= 0) return 0;
      const rows = [...new Set(tab.map((r) => r[0]))].sort((a, b) => a - b);
      const rowN = rows.find((r) => r >= n) ?? rows[rows.length - 1];
      const inRow = tab.filter((r) => r[0] === rowN);
      const hit = inRow.find((r) => fmt <= r[1]);
      return hit ? hit[2] : inRow[inRow.length - 1][2];
    };
    const netSheets = Math.ceil(qty / pps) * versions;
    const ac = parseInt(sz["pieces across"], 10), al = parseInt(sz["pieces along"], 10);
    const nCuts = ac + al + 2, nTrims = ac + al - 2;
    const printFmt = m.sra3_width + m.sra3_height;
    const piles = Math.ceil(netSheets / ct.sheets_per_pile);
    const finalH = rn(ct.cut_setup + (tableTime(nCuts, printFmt) + nTrims * ct.trim_time) * piles);
    lines.push({ key: "cut", label: "Final cut", detail: `${nCuts} cuts + ${nTrims} trims, ${piles} pile${piles > 1 ? "s" : ""} → ${finalH.toFixed(2)} h × €${ct.cut_sell_rate}`, cost: finalH * ct.cut_cost_rate, sell: finalH * ct.cut_sell_rate, hours: finalH });
    const perBigI = parseInt(perBig, 10);
    if (perBigI > 1 && !long_ && String(ct.precut_on || "no").toLowerCase() === "yes") {
      const pcCuts = { 2: ct.precut_cuts_if_2, 4: ct.precut_cuts_if_4, 8: ct.precut_cuts_if_8 }[perBigI];
      if (pcCuts === undefined) throw new Error(`No pre-cut setting for ${perBigI} print sheets per big sheet`);
      const bw = pa["big sheet width (mm)"], bh = pa["big sheet height (mm)"];
      const bigFmt = num(bw) && num(bh) ? bw + bh : 1e9;
      const pcH = rn(ct.cut_setup + tableTime(pcCuts, bigFmt) * Math.ceil(bigSheets / ct.sheets_per_pile));
      lines.push({ key: "precut", label: "Pre-cut", detail: `${pcCuts} cuts on ${num(bw) ? bw + " × " + bh : "unknown size"} → ${pcH.toFixed(2)} h × €${ct.cut_sell_rate}`, cost: pcH * ct.cut_cost_rate, sell: pcH * ct.cut_sell_rate, hours: pcH });
    }

    lines.push({ key: "prepress", label: "File check", detail: "Prepress fee", cost: 0, sell: pr.prepress != null ? pr.prepress : m.prepress_fee });

    return Object.assign(finish(d, pr, lines, long_, express, delivery, pa.gsm), { sheets, bigSheets, netSheets, lines });
  }

  // rules after the cost lines: minimum margin, online discount, uplifts, VAT, rounding, delivery (= finish() in check_prices.py)
  function finish(d, pr, lines, long_, express, delivery, gsm) {
    const ru = d.rules;
    const cost = lines.reduce((s, l) => s + l.cost, 0);
    const listSell = lines.reduce((s, l) => s + l.sell, 0);   // ≈ Multipress price, before any rule
    let sell = listSell; const notes = [];
    const fl = pr.floor;
    const floorOn = !!(fl && gsm != null && fl.from <= gsm && gsm <= fl.to);
    let beforeDiscount, discount;
    if (floorOn) {
      // floor price (flyers, 22 Oct 2026): cost + the product's own margin; no general minimum margin, no online discount
      beforeDiscount = sell; discount = 0;
      sell = cost * (1 + fl.margin); notes.push(`floor price: cost + ${Math.round(fl.margin * 100)}%`);
    } else {
    if (cost > 0 && (sell - cost) / cost < ru.min_margin) { sell = cost * (1 + ru.min_margin); notes.push(`raised to the minimum margin (${Math.round(ru.min_margin * 100)}%)`); }
    beforeDiscount = sell;
    // per product (Products tab, column online discount); empty = the general rule. Folders, brochures: 0 (Kris, 17 Oct)
    discount = pr.onlineDiscount != null ? pr.onlineDiscount : ru.online_discount;
    const disc = sell * (1 - discount), floor = cost * (1 + ru.min_margin);
    sell = Math.max(disc, floor);
    if (discount > 0 && floor > disc) notes.push("online discount limited by the minimum margin");
    }
    if (long_) { sell *= 1 + ru.long_size_uplift; notes.push(`tall poster +${Math.round(ru.long_size_uplift * 100)}%`); }
    if (express) { sell *= 1 + ru.express_markup; notes.push(`express +${Math.round(ru.express_markup * 100)}%`); }
    const vat = pr.vat != null ? pr.vat : ru.vat_rate;   // per product; empty (all products today) = Pricing rules vat_rate (21 %)
    const rawIncl = sell * (1 + vat);
    let incl = roundStep(rawIncl, ru.round_step, ru.round_down_threshold);
    const minJob = floorOn && fl.minJob != null ? fl.minJob : ru.min_job_price;
    if (incl < minJob) { incl = minJob; notes.push(`minimum job price €${minJob}`); }
    if (delivery) { incl += ru.delivery_dpd; notes.push(`DPD delivery +€${ru.delivery_dpd}`); }
    const dl = delivery ? ru.delivery_dpd : 0;   // DPD delivery is always at the normal VAT rate
    const excl = vat === ru.vat_rate ? incl / (1 + vat) : (incl - dl) / (1 + vat) + dl / (1 + ru.vat_rate);
    return {
      incl: Math.round(incl * 100) / 100, excl: Math.round(excl * 100) / 100, cost, listSell, beforeDiscount, discount, sellAfterRules: sell, rawIncl, vat,
      notes, margin: cost > 0 ? (excl - dl / (1 + ru.vat_rate) - cost) / cost : null,
    };
  }

  function cutTableTime(d, n, fmt) {
    if (n <= 0) return 0;
    const tab = d.cutTable;
    const rows = [...new Set(tab.map((r) => r[0]))].sort((a, b) => a - b);
    const rowN = rows.find((r) => r >= n) ?? rows[rows.length - 1];
    const inRow = tab.filter((r) => r[0] === rowN);
    const hit = inRow.find((r) => fmt <= r[1]);
    return hit ? hit[2] : inRow[inRow.length - 1][2];
  }

  // stitching: by hand, or the tower when copies AND pages are above the limits (Kris, 17 Oct)
  function brochureStitch(d, qty, pages) {
    const b = d.brochure, bs = b.s, stations = Math.floor(pages / 4);
    let h, how, detail;
    if (qty > bs.tower_when_copies_above && pages > bs.tower_when_pages_above) {
      const hit = b.stations.find((r) => r[0] >= stations);
      const base = hit ? hit[1] : b.stations[b.stations.length - 1][1];
      let run = 0; const parts = [];
      for (const [f, t, sp] of b.speed) {   // smoothed: every band at its own speed
        const n = Math.max(0, Math.min(qty, t) - (f - 1));
        run += n / sp;
        if (n > 0) parts.push(`${n}/${sp}`);
      }
      h = base + bs.tower_setup_extra + run; how = "tower";
      detail = `tower (${qty} > ${bs.tower_when_copies_above} copies and ${pages} > ${bs.tower_when_pages_above} pages): ${base} + ${bs.tower_setup_extra} h setup (${stations} stations) + ${parts.join(" + ")} h`;
    } else {
      h = bs.manual_setup + qty / bs.manual_speed; how = "manual";
      detail = `by hand: ${bs.manual_setup} h setup + ${qty}/${bs.manual_speed} h`;
    }
    const st = bs.stitch_time_round;
    if (st) h = Math.floor(h / st + 0.5 + 1e-9) * st;
    return { h, how, detail: `${detail} → ${h.toFixed(2)} h × €${bs.stitch_sell_rate}` };
  }

  // saddle-stitched brochure (= price_brochure() in check_prices.py; lines are summed in the same order)
  function priceBrochure(d, pr, o) {
    const b = d.brochure;
    if (!b) throw new Error("Tab Brochures is missing");
    const m = d.machine, ru = d.rules, fm = d.foldMachine, ct = d.cut;
    const qty = o.qty, size = o.size, ink = o.ink || "cmyk", sides = o.sides || "single";
    const versions = parseInt(o.versions || 1, 10);
    if (versions !== 1) throw new Error("Brochures: one version per job");
    if (sides !== "double") throw new Error("Brochures are printed on both sides (4/4)");
    if (!["cmyk", "black", "white"].includes(ink)) throw new Error(`Unknown ink ${ink}`);
    const pages = parseInt(o.pages, 10);
    if (!(pages > 0)) throw new Error("Number of pages missing");
    if (pages < 8 || pages % 4) throw new Error("Pages: 8 or more, a multiple of 4");
    if (!(qty > 0)) throw new Error("Quantity must be above 0");
    const cover = o.coverPaperId || (pr.coverPapers && pr.coverPapers[0]) || null;
    const flat = b.sizes[size], fz = flat ? d.sizes[flat] : null;
    if (!fz) throw new Error(`No brochure size ${size}`);
    const pps = parseInt(fz["pieces per SRA3"], 10);
    const tiers = fz["XXL (A3 click price)"] === "yes" ? d.tiersXXL : d.tiers;
    const stations = Math.floor(pages / 4);
    const lines = []; let sheetsTotal = 0, bigTotal = 0;
    for (const [part, pid, n] of [["Cover", cover, qty], ["Inside", o.paperId, qty * (stations - 1)]]) {
      const pa = d.papers[pid];
      if (!pa) throw new Error(`Unknown paper ${pid}`);
      if (pa.group !== "cmyk" && ink === "cmyk") throw new Error("Coloured papers are printed with white or black toner, not CMYK");
      if (ink === "white" && pa.group === "cmyk") throw new Error("White toner only on coloured paper");
      const net = Math.ceil(n / pps), sheets = net + m.setup_sheets;
      sheetsTotal += sheets;
      const per = pa["SRA3 per big sheet"], big = Math.ceil(sheets / per);
      bigTotal += big;
      const pc = big * pa["price per big sheet (€)"];
      const what = part === "Cover" ? `cover ${qty} × 1 sheet ${flat}` : `inside ${qty} × ${stations - 1} sheet${stations > 2 ? "s" : ""} ${flat}`;
      lines.push({ key: "paper", label: `${part} · paper`, detail: `${pa["name (shown to customers)"] || pid} ${pa.gsm} gsm · ${what}, ${pps} per SRA3 → ${net} + ${m.setup_sheets} setup = ${sheets} print sheets · ${big} big sheets (${per} per big) × €${pa["price per big sheet (€)"]} + ${Math.round(ru.paper_markup * 100)}%`, cost: pc, sell: pc * (1 + ru.paper_markup) });
      let clickSell = 0, done = 0; const parts = [];
      for (const t of tiers) {
        const k = Math.max(0, Math.min(sheets, t.max) - done);
        if (k > 0) parts.push(`${k} × ${t[ink]}`);
        clickSell += k * t[ink]; done += k;
        if (done >= sheets) break;
      }
      if (done < sheets) { const t = tiers[tiers.length - 1]; parts.push(`${sheets - done} × ${t[ink]}`); clickSell += (sheets - done) * t[ink]; }
      clickSell *= 2;
      lines.push({ key: "clicks", label: `${part} · clicks`, detail: `${ink === "cmyk" ? "" : ink + " toner · "}${parts.join(" + ")} · × 2 sides`, cost: sheets * d.clickCosts[ink + "_cost"] * 2, sell: clickSell });
      let hours = m.labor_minutes / 60 + sheets / m.machine_speed;
      if (m.press_time_round) hours = Math.floor(hours / m.press_time_round + 0.5) * m.press_time_round;
      lines.push({ key: "press", label: `${part} · press`, detail: `${m.labor_minutes} min + ${sheets}/${m.machine_speed} h → ${hours.toFixed(2)} h × €${m.machine_sell_rate}`, cost: hours * m.machine_cost_rate, sell: hours * m.machine_sell_rate, hours });
      const perI = parseInt(per, 10);
      if (perI > 1 && String(ct.precut_on || "no").toLowerCase() === "yes") {
        const pcCuts = { 2: ct.precut_cuts_if_2, 4: ct.precut_cuts_if_4, 8: ct.precut_cuts_if_8 }[perI];
        if (pcCuts === undefined) throw new Error(`No pre-cut setting for ${perI} print sheets per big sheet`);
        const bw = pa["big sheet width (mm)"], bh = pa["big sheet height (mm)"];
        const bigFmt = num(bw) && num(bh) ? bw + bh : 1e9;
        let pch = ct.cut_setup + cutTableTime(d, pcCuts, bigFmt) * Math.ceil(big / ct.sheets_per_pile);
        if (ct.cut_time_round) pch = Math.floor(pch / ct.cut_time_round + 0.5 + 1e-9) * ct.cut_time_round;
        lines.push({ key: "precut", label: `${part} · pre-cut`, detail: `${pcCuts} cuts on ${num(bw) ? bw + " × " + bh : "unknown size"} → ${pch.toFixed(2)} h × €${ct.cut_sell_rate}`, cost: pch * ct.cut_cost_rate, sell: pch * ct.cut_sell_rate, hours: pch });
      }
    }
    // crease the cover (same operation and speed as a folder with 1 fold)
    const opId = b.s.cover_crease_operation, op = d.foldOps[opId];
    if (!op) throw new Error(`Crease operation ${opId} missing`);
    const pl = Math.max(fz["width (mm)"], fz["height (mm)"]);
    const band = op.bands.find((x) => x[0] <= pl && pl <= x[1]);
    if (!band) throw new Error(`Pass length ${pl} mm is outside the table of ${opId}`);
    let fh = fm.fold_fixed_setup + op.setup + qty * band[2] / 3600;
    const step = ru.time_round_step || 0;
    if (step) fh = Math.floor(fh / step + 0.5 + 1e-9) * step;
    lines.push({ key: "fold", label: "Crease cover", detail: `${op.name || opId}: ${op.setup} h setup + ${qty} × ${band[2]} s → ${fh.toFixed(2)} h × €${fm.fold_sell_rate}`, cost: fh * fm.fold_cost_rate, sell: fh * fm.fold_sell_rate, hours: fh });
    const st = brochureStitch(d, qty, pages);
    lines.push({ key: "stitch", label: "Stitching", detail: st.detail, cost: st.h * b.s.stitch_cost_rate, sell: st.h * b.s.stitch_sell_rate, hours: st.h });
    lines.push({ key: "prepress", label: "File check", detail: "Prepress fee (brochure)", cost: 0, sell: pr.prepress != null ? pr.prepress : m.prepress_fee });
    return Object.assign(finish(d, pr, lines, false, !!o.express, !!o.delivery), { sheets: sheetsTotal, bigSheets: bigTotal, netSheets: null, lines, stitch: st.how, flat });
  }

  const api = { load, price, split };
  if (typeof module !== "undefined" && module.exports) module.exports = api; else root.CCEngine = api;
})(typeof window !== "undefined" ? window : globalThis);
