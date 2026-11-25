<?php
/**
 * Click Click Graphics — price engine v2 (PHP).
 *
 * PHP copy of the price formula in:
 *   pricing/check_prices.py                    (the master, run by "Update prices")
 *   build/internal-calculator-v2/engine.js     (internal calculator)
 * All three must give the same price. selftest.json (written by the update
 * script) holds the master's prices for a grid of jobs; ccp_selftest() checks
 * this file against it every time the price file changes.
 *
 * Reads data/price-data.xlsx (cached values). Never send its contents to the browser:
 * it holds costs and margins. ccp_catalogue() returns only the public part.
 */

declare(strict_types=1);

final class CcpError extends RuntimeException {}

// ------------------------------------------------------------------ reading the workbook
function ccp_read_xlsx(string $path): array
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) throw new CcpError('cannot open price file');
    $shared = [];
    $ss = $zip->getFromName('xl/sharedStrings.xml');
    if ($ss !== false) {
        $x = simplexml_load_string($ss);
        foreach ($x->si as $si) {
            $t = '';
            foreach ($si->xpath('.//*[local-name()="t"]') as $n) $t .= (string)$n;
            $shared[] = $t;
        }
    }
    $wb = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
    $rels = simplexml_load_string($zip->getFromName('xl/_rels/workbook.xml.rels'));
    $target = [];
    foreach ($rels->Relationship as $r) $target[(string)$r['Id']] = (string)$r['Target'];
    $out = [];
    foreach ($wb->sheets->sheet as $s) {
        $rid = (string)$s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
        $t = ltrim($target[$rid] ?? '', '/');
        $p = str_starts_with($t, 'xl/') ? $t : 'xl/' . $t;
        $xml = $zip->getFromName($p);
        if ($xml === false) continue;
        $sx = simplexml_load_string($xml);
        $cells = [];
        foreach ($sx->sheetData->row as $row) {
            foreach ($row->c as $c) {
                $ref = (string)$c['r']; $typ = (string)$c['t'];
                if ($typ === 'inlineStr') {
                    $val = '';
                    foreach ($c->xpath('.//*[local-name()="t"]') as $n) $val .= (string)$n;
                } else {
                    if (!isset($c->v)) continue;
                    $v = (string)$c->v;
                    if ($typ === 's') $val = $shared[(int)$v] ?? '';
                    elseif ($typ === 'str') $val = $v;
                    elseif ($typ === 'b' || $typ === 'e') continue;
                    else { $f = (float)$v; $val = (floor($f) == $f && abs($f) < 1e15) ? (int)$f : $f; }
                }
                if (is_string($val)) { $val = trim($val); if ($val === '') continue; }
                preg_match('/^([A-Z]+)(\d+)$/', $ref, $m);
                $col = 0; foreach (str_split($m[1]) as $ch) $col = $col * 26 + ord($ch) - 64;
                $cells[(int)$m[2] . ':' . $col] = $val;
            }
        }
        $out[(string)$s['name']] = $cells;
    }
    $zip->close();
    return $out;
}

function ccp_get(array $cells, int $r, int $c) { return $cells["$r:$c"] ?? null; }

function ccp_table(array $cells, int $headerRow): array
{
    $heads = [];
    for ($c = 1; ccp_get($cells, $headerRow, $c) !== null; $c++) $heads[$c] = (string)ccp_get($cells, $headerRow, $c);
    $rows = [];
    for ($r = $headerRow + 1; $r <= $headerRow + 2000; $r++) {
        $rec = []; $empty = true;
        foreach ($heads as $c => $h) { $v = ccp_get($cells, $r, $c); $rec[$h] = $v; if ($v !== null) $empty = false; }
        if ($empty) break;
        $rec['_row'] = $r; $rows[] = $rec;
    }
    return $rows;
}

function ccp_find_row(array $cells, int $col, string $text): ?int
{
    for ($r = 1; $r < 400; $r++) if (ccp_get($cells, $r, $col) === $text) return $r;
    return null;
}

function ccp_num($v): bool { return (is_int($v) || is_float($v)) && is_finite((float)$v); }
function ccp_split($v): array
{
    if ($v === null) return [];
    return array_values(array_filter(array_map('trim', explode(',', (string)$v)), fn($x) => $x !== ''));
}

function ccp_load(string $path): array
{
    $wb = ccp_read_xlsx($path);
    foreach (['Papers', 'Clicks', 'Machine', 'Pricing rules', 'Sizes', 'Cutting', 'Folds', 'Products'] as $t) {
        if (!isset($wb[$t])) throw new CcpError("tab $t missing in price file");
    }
    $kv = function (string $tab) use ($wb): array {
        $o = [];
        foreach (ccp_table($wb[$tab], 3) as $rec) if (!empty($rec['setting'])) $o[(string)$rec['setting']] = $rec['value'] ?? null;
        return $o;
    };
    $d = ['machine' => $kv('Machine'), 'rules' => $kv('Pricing rules'), 'cut' => $kv('Cutting')];

    $cw = $wb['Cutting']; $hr = ccp_find_row($cw, 1, 'cuts'); $tab = [];
    if ($hr) foreach (ccp_table($cw, $hr) as $rec) {
        if (ccp_num($rec['cuts'] ?? null) && ccp_num($rec['format up to (mm)'] ?? null) && ccp_num($rec['time (h)'] ?? null))
            $tab[] = [$rec['cuts'], $rec['format up to (mm)'], $rec['time (h)']];
    }
    usort($tab, fn($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);
    if (!$tab) throw new CcpError('cut table missing');
    $d['cutTable'] = $tab;

    $d['papers'] = [];
    foreach (ccp_table($wb['Papers'], 3) as $rec) if (!empty($rec['paper_id'])) $d['papers'][$rec['paper_id']] = $rec;

    $c = $wb['Clicks'];
    $tiers = function (int $col0) use ($c): array {
        $o = [];
        for ($r = 4; ccp_num(ccp_get($c, $r, $col0)); $r++)
            $o[] = ['max' => ccp_get($c, $r, $col0), 'cmyk' => ccp_get($c, $r, $col0 + 1), 'white' => ccp_get($c, $r, $col0 + 2), 'black' => ccp_get($c, $r, $col0 + 3)];
        return $o;
    };
    $d['tiers'] = $tiers(1); $d['tiersXXL'] = $tiers(6);
    $d['clickCosts'] = [];
    $rc = ccp_find_row($c, 1, 'cmyk_cost');
    if ($rc) for ($i = 0; $i < 4; $i++) if (ccp_get($c, $rc + $i, 1)) $d['clickCosts'][ccp_get($c, $rc + $i, 1)] = ccp_get($c, $rc + $i, 2);

    $d['sizes'] = [];
    foreach (ccp_table($wb['Sizes'], 3) as $rec) if (!empty($rec['size'])) $d['sizes'][(string)$rec['size']] = $rec;

    $f = $wb['Folds']; $d['foldMachine'] = [];
    for ($r = 4; ccp_get($f, $r, 1) !== null; $r++) $d['foldMachine'][(string)ccp_get($f, $r, 1)] = ccp_get($f, $r, 2);
    $d['foldOps'] = [];
    $ro = ccp_find_row($f, 1, 'operation');
    if ($ro) foreach (ccp_table($f, $ro) as $rec) {
        if (empty($rec['operation']) || !ccp_num($rec['pass length from (mm)'] ?? null)) continue;
        $id = $rec['operation'];
        $d['foldOps'][$id] ??= ['setup' => null, 'name' => null, 'bands' => []];
        if (ccp_num($rec['setup time (h)'] ?? null)) $d['foldOps'][$id]['setup'] = $rec['setup time (h)'];
        if (!empty($rec['name in Multipress'])) $d['foldOps'][$id]['name'] = $rec['name in Multipress'];
        $d['foldOps'][$id]['bands'][] = [$rec['pass length from (mm)'], $rec['pass length to (mm)'], $rec['time per sheet (s)']];
    }
    $d['folds'] = [];
    $rt = ccp_find_row($f, 1, 'fold_id');
    if ($rt) foreach (ccp_table($f, $rt) as $rec) if (!empty($rec['fold_id']) && !empty($rec['flat size'])) $d['folds'][$rec['fold_id'] . '|' . $rec['flat size']] = $rec;

    // brochures (optional tab): stitching by hand or with the tower, closed -> flat size
    $d['brochure'] = null;
    if (isset($wb['Brochures'])) {
        $bw = $wb['Brochures'];
        $bs = [];
        foreach (ccp_table($bw, 3) as $rec) if (!empty($rec['setting'])) $bs[(string)$rec['setting']] = $rec['value'] ?? null;
        foreach (['stitch_cost_rate', 'stitch_sell_rate', 'stitch_time_round', 'tower_when_copies_above', 'tower_when_pages_above', 'manual_setup', 'manual_speed', 'tower_setup_extra'] as $k)
            if (!ccp_num($bs[$k] ?? null)) throw new CcpError("Brochures: setting $k missing");
        $b = ['s' => $bs, 'sizes' => [], 'stations' => [], 'speed' => []];
        $r = ccp_find_row($bw, 1, 'closed size');
        if ($r) foreach (ccp_table($bw, $r) as $rec) if (!empty($rec['closed size'])) $b['sizes'][(string)$rec['closed size']] = (string)($rec['flat size'] ?? '');
        $r = ccp_find_row($bw, 1, 'stations');
        if ($r) foreach (ccp_table($bw, $r) as $rec) if (ccp_num($rec['stations'] ?? null) && ccp_num($rec['setup time (h)'] ?? null)) $b['stations'][] = [$rec['stations'], $rec['setup time (h)']];
        $r = ccp_find_row($bw, 1, 'copies from');
        if ($r) foreach (ccp_table($bw, $r) as $rec)
            if (ccp_num($rec['copies from'] ?? null) && ccp_num($rec['copies to'] ?? null) && ccp_num($rec['copies per hour'] ?? null) && $rec['copies per hour'] > 0)
                $b['speed'][] = [$rec['copies from'], $rec['copies to'], $rec['copies per hour']];
        usort($b['stations'], fn($x, $y) => [$x[0], $x[1]] <=> [$y[0], $y[1]]);
        usort($b['speed'], fn($x, $y) => [$x[0], $x[1], $x[2]] <=> [$y[0], $y[1], $y[2]]);
        $d['brochure'] = $b;
    }

    $d['products'] = [];
    foreach (ccp_table($wb['Products'], 3) as $rec) {
        if (empty($rec['product'])) continue;
        $sides = ccp_split($rec['sides offered'] ?? null);
        $d['products'][$rec['product']] = [
            'id' => $rec['product'], 'name' => $rec['name'] ?? $rec['product'], 'active' => ($rec['active'] ?? '') === 'yes',
            'sizes' => ccp_split($rec['sizes (comma separated)'] ?? null), 'folds' => ccp_split($rec['folds (fold_id, comma separated)'] ?? null),
            'papers' => ccp_split($rec['papers (paper_id, comma separated)'] ?? null),
            'qtys' => array_map('intval', ccp_split($rec['quantities (comma separated)'] ?? null)),
            'defaultPaper' => $rec['default paper'] ?? null, 'days' => $rec['production days'] ?? null,
            'inks' => ccp_split($rec['inks'] ?? null), 'sides' => $sides ?: ['single', 'double'],
            'defaultQty' => ccp_num($rec['default quantity'] ?? null) ? (int)$rec['default quantity'] : null,
            'hint' => $rec['hint shown in the shop'] ?? null,
            'maxQty' => ccp_num($rec['max quantity'] ?? null) ? (int)$rec['max quantity'] : 10000,
            'onlineDiscount' => ccp_num($rec['online discount'] ?? null) ? (float)$rec['online discount'] : null,
            // floor price (22 Oct 2026): papers within the weight range are sold at cost + margin (no online discount, own minimum job price)
            'floor' => (ccp_num($rec['floor price from paper weight (g/m²)'] ?? null) && ccp_num($rec['floor price to paper weight (g/m²)'] ?? null) && ccp_num($rec['floor price margin'] ?? null))
                ? ['from' => (float)$rec['floor price from paper weight (g/m²)'], 'to' => (float)$rec['floor price to paper weight (g/m²)'],
                   'margin' => (float)$rec['floor price margin'],
                   'minJob' => ccp_num($rec['floor price min job price (€)'] ?? null) ? (float)$rec['floor price min job price (€)'] : null] : null,
            'vat' => ccp_num($rec['VAT rate'] ?? null) ? (float)$rec['VAT rate'] : null,
            'prepress' => ccp_num($rec['prepress fee (€)'] ?? null) ? (float)$rec['prepress fee (€)'] : null,
            'minQty' => ccp_num($rec['min quantity'] ?? null) ? (int)$rec['min quantity'] : null,
            'pages' => array_values(array_filter(array_map('intval', ccp_split($rec['pages (comma separated)'] ?? null)), fn($x) => $x >= 8 && $x % 4 === 0)),
            'coverPapers' => ccp_split($rec['cover papers (paper_id, comma separated)'] ?? null),
            'defaultPages' => ccp_num($rec['default pages'] ?? null) ? (int)$rec['default pages'] : null,
        ];
    }
    return $d;
}

// ------------------------------------------------------------------ the price formula (identical to check_prices.py / engine.js)
function ccp_round_step(float $price, float $step, float $down): float
{
    $lower = floor($price / $step) * $step;
    $above = $price - $lower;
    if ($above == 0) return $price;
    return $above <= $down + 1e-9 ? $lower : $lower + $step;
}

/** $o: product,size,paperId,qty,sides,versions,express,fold,delivery,ink. Returns incl, excl, cost, sheets. */
function ccp_price(array $d, array $o): array
{
    $pr = $d['products'][(string)($o['product'] ?? '')] ?? [];
    if (!empty($pr['pages'])) return ccp_price_brochure($d, $pr, $o);
    $size = (string)($o['size'] ?? ''); $paperId = (string)($o['paperId'] ?? ''); $qty = (int)($o['qty'] ?? 0);
    $sides = $o['sides'] ?? 'single'; $versions = max(1, (int)($o['versions'] ?? 1));
    $express = !empty($o['express']); $fold = $o['fold'] ?? null; $delivery = !empty($o['delivery']); $ink = $o['ink'] ?? 'cmyk';
    $m = $d['machine']; $ru = $d['rules']; $fm = $d['foldMachine']; $ct = $d['cut'];
    $sz = $d['sizes'][$size] ?? null; $pa = $d['papers'][$paperId] ?? null;
    if (!$sz) throw new CcpError("Unknown size $size");
    if (!$pa) throw new CcpError("Unknown paper $paperId");
    if ($qty <= 0) throw new CcpError('Quantity must be above 0');
    $only = ccp_split($sz['only on papers (code, comma separated)'] ?? null);
    if ($only && !in_array(explode('-', $paperId)[0], $only, true)) throw new CcpError("$paperId cannot be printed in size $size");
    if (!in_array($ink, ['cmyk', 'black', 'white'], true)) throw new CcpError("Unknown ink $ink");
    if (($pa['group'] ?? '') !== 'cmyk' && $ink === 'cmyk') throw new CcpError('Coloured papers are printed with white or black toner, not CMYK');
    if ($ink === 'white' && ($pa['group'] ?? '') === 'cmyk') throw new CcpError('White toner only on coloured paper');
    $long = ($sz['banner feed (tall poster)'] ?? '') === 'yes';
    $xxl = ($sz['XXL (A3 click price)'] ?? '') === 'yes';
    $pps = (int)$sz['pieces per SRA3'];
    $double = $sides === 'double';

    $step = (float)($ru['time_round_step'] ?: 0);
    $rt = fn(float $h): float => $step ? floor($h / $step + 0.5 + 1e-9) * $step : $h;
    $cstep = (float)($ct['cut_time_round'] ?: 0);
    $rn = fn(float $h): float => $cstep ? floor($h / $cstep + 0.5 + 1e-9) * $cstep : $h;

    $sheets = ((int)ceil($qty / $pps) + (int)$m['setup_sheets']) * $versions;
    $perBig = $pa['SRA3 per big sheet'];
    $bigSheets = $long ? $sheets : (int)ceil($sheets / $perBig);
    $paperCost = ($long ? $sheets : $bigSheets) * $pa['price per big sheet (€)'];
    $paperSell = $paperCost * (1 + $ru['paper_markup']);

    $tiers = $xxl ? $d['tiersXXL'] : $d['tiers'];
    $clickSell = 0.0; $done = 0;
    foreach ($tiers as $t) {
        $n = max(0, min($sheets, $t['max']) - $done);
        $clickSell += $n * $t[$ink]; $done += $n;
        if ($done >= $sheets) break;
    }
    if ($done < $sheets) $clickSell += ($sheets - $done) * $tiers[count($tiers) - 1][$ink];
    $clickSell *= $double ? 2 : 1;
    $clickCost = $sheets * $d['clickCosts'][$ink . '_cost'] * ($double ? 2 : 1);

    $printH = $sheets / ($long ? $m['machine_speed_long'] : $m['machine_speed']);
    $hours = $m['labor_minutes'] / 60 + $printH;
    if ($m['press_time_round']) $hours = floor($hours / $m['press_time_round'] + 0.5) * $m['press_time_round'];
    $machineCost = $hours * $m['machine_cost_rate'];
    $machineSell = $hours * $m['machine_sell_rate'];

    $foldCost = $foldSell = 0.0;
    if ($fold) {
        $ft = $d['folds'][$fold . '|' . $size] ?? null;
        if (!$ft) throw new CcpError("Fold $fold not possible on $size");
        $op = $d['foldOps'][$ft['operation']] ?? null;
        if (!$op) throw new CcpError("Fold operation {$ft['operation']} missing");
        $pl = max($ft['flat width (mm)'], $ft['flat height (mm)']);
        $tps = null;
        foreach ($op['bands'] as $b) if ($b[0] <= $pl && $pl <= $b[1]) { $tps = $b[2]; break; }
        if ($tps === null) throw new CcpError("Pass length $pl mm is outside the table");
        $extra = (int)$ft['long side divided by'] - 2;
        $fh = $rt($fm['fold_fixed_setup'] + $op['setup'] + $fm['fold_time_per_version'] * ($versions - 1)
                  + $qty * $versions * $tps / 3600 * (1 + $fm['fold_extra_fold_time'] * $extra));
        $foldCost = $fh * $fm['fold_cost_rate'];
        $foldSell = $fh * $fm['fold_sell_rate'];
    }

    $tab = $d['cutTable'];
    $tableTime = function (int $n, float $fmt) use ($tab): float {
        if ($n <= 0) return 0.0;
        $rows = array_values(array_unique(array_map(fn($r) => $r[0], $tab))); sort($rows);
        $rowN = $rows[count($rows) - 1];
        foreach ($rows as $r) if ($r >= $n) { $rowN = $r; break; }
        $in = array_values(array_filter($tab, fn($r) => $r[0] == $rowN));
        foreach ($in as $r) if ($fmt <= $r[1]) return (float)$r[2];
        return (float)$in[count($in) - 1][2];
    };
    $netSheets = (int)ceil($qty / $pps) * $versions;
    $ac = (int)$sz['pieces across']; $al = (int)$sz['pieces along'];
    $nCuts = $ac + $al + 2; $nTrims = $ac + $al - 2;
    $printFmt = $m['sra3_width'] + $m['sra3_height'];
    $piles = (int)ceil($netSheets / $ct['sheets_per_pile']);
    $cutH = $rn($ct['cut_setup'] + ($tableTime($nCuts, $printFmt) + $nTrims * $ct['trim_time']) * $piles);
    $perBigI = (int)$perBig;
    if ($perBigI > 1 && !$long && strtolower((string)($ct['precut_on'] ?? 'no')) === 'yes') {
        $pc = [2 => $ct['precut_cuts_if_2'], 4 => $ct['precut_cuts_if_4'], 8 => $ct['precut_cuts_if_8']][$perBigI] ?? null;
        if ($pc === null) throw new CcpError("No pre-cut setting for $perBigI print sheets per big sheet");
        $bw = $pa['big sheet width (mm)'] ?? null; $bh = $pa['big sheet height (mm)'] ?? null;
        $bigFmt = (ccp_num($bw) && ccp_num($bh)) ? $bw + $bh : 1e9;
        $cutH += $rn($ct['cut_setup'] + $tableTime((int)$pc, (float)$bigFmt) * (int)ceil($bigSheets / $ct['sheets_per_pile']));
    }
    $cutCost = $cutH * $ct['cut_cost_rate'];
    $cutSell = $cutH * $ct['cut_sell_rate'];

    $cost = $paperCost + $clickCost + $machineCost + $foldCost + $cutCost;
    $sell = $paperSell + $clickSell + $machineSell + (($pr['prepress'] ?? null) === null ? $m['prepress_fee'] : $pr['prepress']) + $foldSell + $cutSell;
    [$incl, $excl] = ccp_finish($d, $pr, $cost, $sell, $long, $express, $delivery, $pa['gsm'] ?? null);
    return ['incl' => $incl, 'excl' => $excl, 'cost' => round($cost, 2), 'sheets' => $sheets];
}

/** Rules after the cost lines (= finish() in check_prices.py). Returns [incl, excl]. */
function ccp_finish(array $d, array $pr, float $cost, float $sell, bool $long, bool $express, bool $delivery, $gsm = null): array
{
    $ru = $d['rules'];
    $fl = $pr['floor'] ?? null;
    $floorOn = $fl !== null && $gsm !== null && $fl['from'] <= (float)$gsm && (float)$gsm <= $fl['to'];
    if ($floorOn) {
        // floor price (flyers, 22 Oct 2026): cost + the product's own margin; no general minimum margin, no online discount
        $sell = $cost * (1 + $fl['margin']);
    } else {
        if ($cost > 0 && ($sell - $cost) / $cost < $ru['min_margin']) $sell = $cost * (1 + $ru['min_margin']);
        // per product (Products tab, column online discount); empty = the general rule. Folders, brochures: 0 (Kris, 17 Oct)
        $disc = $pr['onlineDiscount'] ?? null;
        if ($disc === null) $disc = (float)$ru['online_discount'];
        $sell = max($sell * (1 - $disc), $cost * (1 + $ru['min_margin']));
    }
    if ($long) $sell *= 1 + $ru['long_size_uplift'];
    if ($express) $sell *= 1 + $ru['express_markup'];
    $vat = $pr['vat'] ?? null;   // per product; empty (all products today) = Pricing rules vat_rate (21 %)
    if ($vat === null) $vat = $ru['vat_rate'];
    $incl = ccp_round_step($sell * (1 + $vat), (float)$ru['round_step'], (float)$ru['round_down_threshold']);
    $incl = max($incl, $floorOn && $fl['minJob'] !== null ? (float)$fl['minJob'] : (float)$ru['min_job_price']);
    if ($delivery) $incl += $ru['delivery_dpd'];
    if ((float)$vat == (float)$ru['vat_rate']) {
        $excl = $incl / (1 + $vat);
    } else {   // DPD delivery is always at the normal VAT rate
        $dl = $delivery ? $ru['delivery_dpd'] : 0;
        $excl = ($incl - $dl) / (1 + $vat) + $dl / (1 + $ru['vat_rate']);
    }
    return [round($incl, 2), round($excl, 2)];
}

function ccp_cut_table_time(array $d, int $n, float $fmt): float
{
    if ($n <= 0) return 0.0;
    $tab = $d['cutTable'];
    $rows = array_values(array_unique(array_map(fn($r) => $r[0], $tab))); sort($rows);
    $rowN = $rows[count($rows) - 1];
    foreach ($rows as $r) if ($r >= $n) { $rowN = $r; break; }
    $in = array_values(array_filter($tab, fn($r) => $r[0] == $rowN));
    foreach ($in as $r) if ($fmt <= $r[1]) return (float)$r[2];
    return (float)$in[count($in) - 1][2];
}

/** Stitching hours: by hand, or the tower when copies AND pages are above the limits (Kris, 17 Oct). */
function ccp_brochure_stitch(array $d, int $qty, int $pages): array
{
    $b = $d['brochure']; $bs = $b['s']; $stations = intdiv($pages, 4);
    if ($qty > $bs['tower_when_copies_above'] && $pages > $bs['tower_when_pages_above']) {
        $base = $b['stations'][count($b['stations']) - 1][1];
        foreach ($b['stations'] as $r) if ($r[0] >= $stations) { $base = $r[1]; break; }
        $run = 0.0;
        foreach ($b['speed'] as [$f, $t, $sp]) $run += max(0, min($qty, $t) - ($f - 1)) / $sp;   // smoothed
        $h = $base + $bs['tower_setup_extra'] + $run; $how = 'tower';
    } else {
        $h = $bs['manual_setup'] + $qty / $bs['manual_speed']; $how = 'manual';
    }
    $st = (float)$bs['stitch_time_round'];
    if ($st) $h = floor($h / $st + 0.5 + 1e-9) * $st;
    return [$h, $how];
}

/** Saddle-stitched brochure (= price_brochure() in check_prices.py; lines summed in the same order). */
function ccp_price_brochure(array $d, array $pr, array $o): array
{
    $b = $d['brochure'] ?? null;
    if (!$b) throw new CcpError('tab Brochures missing');
    $m = $d['machine']; $ru = $d['rules']; $fm = $d['foldMachine']; $ct = $d['cut'];
    $size = (string)($o['size'] ?? ''); $qty = (int)($o['qty'] ?? 0); $ink = $o['ink'] ?? 'cmyk'; $sides = $o['sides'] ?? 'single';
    if ((int)($o['versions'] ?? 1) !== 1) throw new CcpError('brochures: one version per job');
    if ($sides !== 'double') throw new CcpError('brochures are printed on both sides (4/4)');
    if (!in_array($ink, ['cmyk', 'black', 'white'], true)) throw new CcpError("Unknown ink $ink");
    $pages = (int)($o['pages'] ?? 0);
    if ($pages < 8 || $pages % 4) throw new CcpError('pages: 8 or more, a multiple of 4');
    if ($qty <= 0) throw new CcpError('Quantity must be above 0');
    $cover = $o['coverPaperId'] ?? null;
    if (!$cover) $cover = $pr['coverPapers'][0] ?? null;
    $flat = $b['sizes'][$size] ?? null; $fz = $flat ? ($d['sizes'][$flat] ?? null) : null;
    if (!$fz) throw new CcpError("No brochure size $size");
    $pps = (int)$fz['pieces per SRA3'];
    $tiers = ($fz['XXL (A3 click price)'] ?? '') === 'yes' ? $d['tiersXXL'] : $d['tiers'];
    $stations = intdiv($pages, 4);
    $lines = []; $sheetsTotal = 0;
    foreach ([[$cover, $qty], [(string)($o['paperId'] ?? ''), $qty * ($stations - 1)]] as [$pid, $n]) {
        $pa = $d['papers'][$pid] ?? null;
        if (!$pa) throw new CcpError("Unknown paper $pid");
        if (($pa['group'] ?? '') !== 'cmyk' && $ink === 'cmyk') throw new CcpError('Coloured papers are printed with white or black toner, not CMYK');
        if ($ink === 'white' && ($pa['group'] ?? '') === 'cmyk') throw new CcpError('White toner only on coloured paper');
        $sheets = (int)ceil($n / $pps) + (int)$m['setup_sheets'];
        $sheetsTotal += $sheets;
        $per = $pa['SRA3 per big sheet']; $big = (int)ceil($sheets / $per);
        $pc = $big * $pa['price per big sheet (€)'];
        $lines[] = [$pc, $pc * (1 + $ru['paper_markup'])];
        $clickSell = 0.0; $done = 0;
        foreach ($tiers as $t) {
            $k = max(0, min($sheets, $t['max']) - $done);
            $clickSell += $k * $t[$ink]; $done += $k;
            if ($done >= $sheets) break;
        }
        if ($done < $sheets) $clickSell += ($sheets - $done) * $tiers[count($tiers) - 1][$ink];
        $clickSell *= 2;
        $lines[] = [$sheets * $d['clickCosts'][$ink . '_cost'] * 2, $clickSell];
        $hours = $m['labor_minutes'] / 60 + $sheets / $m['machine_speed'];
        if ($m['press_time_round']) $hours = floor($hours / $m['press_time_round'] + 0.5) * $m['press_time_round'];
        $lines[] = [$hours * $m['machine_cost_rate'], $hours * $m['machine_sell_rate']];
        $perI = (int)$per;
        if ($perI > 1 && strtolower((string)($ct['precut_on'] ?? 'no')) === 'yes') {
            $pcc = [2 => $ct['precut_cuts_if_2'], 4 => $ct['precut_cuts_if_4'], 8 => $ct['precut_cuts_if_8']][$perI] ?? null;
            if ($pcc === null) throw new CcpError("No pre-cut setting for $perI print sheets per big sheet");
            $bw = $pa['big sheet width (mm)'] ?? null; $bh = $pa['big sheet height (mm)'] ?? null;
            $bigFmt = (ccp_num($bw) && ccp_num($bh)) ? $bw + $bh : 1e9;
            $pch = $ct['cut_setup'] + ccp_cut_table_time($d, (int)$pcc, (float)$bigFmt) * (int)ceil($big / $ct['sheets_per_pile']);
            if ($ct['cut_time_round']) $pch = floor($pch / $ct['cut_time_round'] + 0.5 + 1e-9) * $ct['cut_time_round'];
            $lines[] = [$pch * $ct['cut_cost_rate'], $pch * $ct['cut_sell_rate']];
        }
    }
    $opId = $b['s']['cover_crease_operation'] ?? ''; $op = $d['foldOps'][$opId] ?? null;
    if (!$op) throw new CcpError("Crease operation $opId missing");
    $pl = max($fz['width (mm)'], $fz['height (mm)']);
    $tps = null;
    foreach ($op['bands'] as $x) if ($x[0] <= $pl && $pl <= $x[1]) { $tps = $x[2]; break; }
    if ($tps === null) throw new CcpError("Pass length $pl mm is outside the table");
    $fh = $fm['fold_fixed_setup'] + $op['setup'] + $qty * $tps / 3600;
    $step = (float)($ru['time_round_step'] ?: 0);
    if ($step) $fh = floor($fh / $step + 0.5 + 1e-9) * $step;
    $lines[] = [$fh * $fm['fold_cost_rate'], $fh * $fm['fold_sell_rate']];
    [$sh] = ccp_brochure_stitch($d, $qty, $pages);
    $lines[] = [$sh * $b['s']['stitch_cost_rate'], $sh * $b['s']['stitch_sell_rate']];
    $lines[] = [0.0, ($pr['prepress'] ?? null) === null ? $m['prepress_fee'] : $pr['prepress']];
    $cost = 0.0; $sell = 0.0;
    foreach ($lines as [$c, $s]) { $cost += $c; $sell += $s; }
    [$incl, $excl] = ccp_finish($d, $pr, $cost, $sell, false, !empty($o['express']), !empty($o['delivery']));
    return ['incl' => $incl, 'excl' => $excl, 'cost' => round($cost, 2), 'sheets' => $sheetsTotal];
}

// ------------------------------------------------------------------ public catalogue (no costs, no margins)
function ccp_catalogue(array $d): array
{
    $out = ['products' => [], 'sizes' => [], 'papers' => [], 'folds' => [], 'vatRate' => $d['rules']['vat_rate'],
            'minJobPrice' => $d['rules']['min_job_price'], 'deliveryDpd' => $d['rules']['delivery_dpd'],
            'expressMarkup' => $d['rules']['express_markup']];
    $usedPapers = []; $usedSizes = [];
    foreach ($d['products'] as $p) {
        if (!$p['active']) continue;
        $out['products'][$p['id']] = ['name' => $p['name'], 'sizes' => $p['sizes'], 'folds' => $p['folds'], 'papers' => $p['papers'],
            'qtys' => $p['qtys'], 'defaultPaper' => $p['defaultPaper'], 'defaultQty' => $p['defaultQty'], 'days' => $p['days'],
            'inks' => $p['inks'], 'sides' => $p['sides'], 'hint' => $p['hint'], 'maxQty' => $p['maxQty'],
            'minQty' => $p['minQty'] ?? null, 'vat' => $p['vat'] ?? null, 'pages' => $p['pages'] ?? [], 'coverPapers' => $p['coverPapers'] ?? [],
            'defaultPages' => $p['defaultPages'] ?? null];
        foreach (($p['coverPapers'] ?? []) as $x) $usedPapers[$x] = true;
        foreach ($p['papers'] as $x) $usedPapers[$x] = true;
        foreach ($p['sizes'] as $x) $usedSizes[$x] = true;
        foreach ($p['folds'] as $fid) foreach ($p['sizes'] as $s) {
            $ft = $d['folds']["$fid|$s"] ?? null;
            if ($ft) $out['folds']["$fid|$s"] = ['id' => $fid, 'size' => $s, 'pages' => $ft['pages'] ?? null,
                'closed' => $ft['closed size (mm)'] ?? null,
                'en' => $ft['name in the shop (EN)'] ?? null, 'nl' => $ft['name in the shop (NL)'] ?? null, 'fr' => $ft['name in the shop (FR)'] ?? null];
        }
    }
    foreach (array_keys($usedSizes) as $s) if (isset($d['sizes'][$s])) {
        $z = $d['sizes'][$s];
        $out['sizes'][$s] = ['name' => $z['name'] ?? $s, 'w' => $z['width (mm)'] ?? null, 'h' => $z['height (mm)'] ?? null,
            'onlyOn' => ccp_split($z['only on papers (code, comma separated)'] ?? null)];
    }
    foreach (array_keys($usedPapers) as $k) if (isset($d['papers'][$k])) {
        $p = $d['papers'][$k];
        if (($p['active'] ?? '') !== 'yes') continue;
        $out['papers'][$k] = ['name' => $p['name (shown to customers)'] ?? $k, 'gsm' => $p['gsm'] ?? null, 'colour' => $p['colour (hex)'] ?? null, 'group' => $p['group'] ?? null];
    }
    return $out;
}

// ------------------------------------------------------------------ self-test against the master (check_prices.py)
/** Returns ['ok'=>bool,'checked'=>n,'failed'=>[...]]. selftest.json: {"jobs":[{o:{...}, incl:float|null}]} (null = master refused). */
function ccp_selftest(array $d, string $selftestPath): array
{
    if (!is_file($selftestPath)) return ['ok' => false, 'checked' => 0, 'failed' => ['selftest.json missing']];
    $st = json_decode((string)file_get_contents($selftestPath), true);
    if (!is_array($st) || !isset($st['jobs'])) return ['ok' => false, 'checked' => 0, 'failed' => ['selftest.json unreadable']];
    $failed = []; $n = 0;
    foreach ($st['jobs'] as $j) {
        $n++;
        try { $got = ccp_price($d, $j['o'])['incl']; } catch (Throwable $e) { $got = null; }
        $exp = $j['incl'];
        if (($exp === null) !== ($got === null) || ($exp !== null && abs($exp - $got) > 0.005)) {
            if (count($failed) < 10) $failed[] = ['job' => $j['o'], 'expected' => $exp, 'got' => $got];
        }
    }
    return ['ok' => !$failed && $n > 0, 'checked' => $n, 'failed' => $failed, 'version' => $st['version'] ?? null];
}

/**
 * Load price data with a cache. The cache is rebuilt when price-data.xlsx or selftest.json
 * changes, and only if the self-test passes; otherwise CcpError (the shop shows "prices
 * temporarily unavailable" instead of a wrong price).
 */
function ccp_data(string $dataDir): array
{
    $xlsx = $dataDir . '/price-data.xlsx'; $st = $dataDir . '/selftest.json'; $cache = $dataDir . '/.cache.json';
    if (!is_file($xlsx)) throw new CcpError('price file missing');
    $stamp = filemtime($xlsx) . '-' . filesize($xlsx) . '-' . (is_file($st) ? filemtime($st) . '-' . filesize($st) : 'none');
    if (is_file($cache)) {
        $c = json_decode((string)file_get_contents($cache), true);
        if (is_array($c) && ($c['stamp'] ?? '') === $stamp && isset($c['data'])) return $c['data'];
    }
    $d = ccp_load($xlsx);
    $t = ccp_selftest($d, $st);
    if (!$t['ok']) {
        error_log('price engine self-test FAILED: ' . json_encode($t));
        throw new CcpError('self-test failed');
    }
    @file_put_contents($cache, json_encode(['stamp' => $stamp, 'data' => $d, 'selftest' => ['checked' => $t['checked'], 'version' => $t['version']]]));
    return $d;
}
