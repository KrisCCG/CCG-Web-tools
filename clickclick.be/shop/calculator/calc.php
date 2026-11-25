<?php
/**
 * Click Click — server-side pricing endpoint.
 *
 * Accepts POST JSON with an items array:
 *   {
 *     "items": [
 *       { "id":"uuid", "product":"flyer", "paper_key":"ms130", "size":"A6",
 *         "qty":250, "versions":1, "sides":"single" }, ...
 *     ],
 *     "express": false,
 *     "delivery": "pickup"|"dpd"
 *   }
 *
 * Also accepts legacy GET / form POST with the flat single-item fields
 * (product, paper_key, size, qty, versions, sides, express, delivery) —
 * wrapped internally as a one-item request and returned in the legacy flat shape.
 *
 * Multi-item response:
 *   { ok, items:[{id, inclVat, exVat, perPieceIncl, perPieceEx, qty, versions, totalPieces}],
 *     subtotalInclVat, subtotalExVat,
 *     deliveryInclVat, deliveryExVat,
 *     totalInclVat,    totalExVat }
 *
 * Legacy single-item response (when wrapped):
 *   { ok, inclVat, exVat, perPieceIncl, perPieceEx, qty, versions, totalPieces }
 *
 * The raw Excel costs/margins never leave this endpoint.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
// Public endpoint: returns only computed prices (no internal costs/margins).
// CORS open so the quote page works from any origin (incl. local dev).
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/lib/xlsx_reader.php';
require_once __DIR__ . '/lib/items.php';
require_once __DIR__ . '/lib/workdays.php';

const XLSX_PATH = __DIR__ . '/data/print-calculator-values.xlsx';

// ──────────────────── Load & cache pricing data ────────────────────
function loadPricingData(): array
{
    $cachePath = __DIR__ . '/data/.pricing_cache.json';

    // Use cache if fresher than the xlsx
    if (is_file($cachePath) && is_file(XLSX_PATH)
        && filemtime($cachePath) >= filemtime(XLSX_PATH)) {
        $json = @file_get_contents($cachePath);
        if ($json !== false) {
            $data = json_decode($json, true);
            if (is_array($data)) return $data;
        }
    }

    $xlsx = new PricingXlsx(XLSX_PATH);

    // CMYK Papers → paperData[paperType][weight] = {s:cuts, cost:raw}
    $paperData = [];
    foreach ($xlsx->getSheetAssoc('CMYK Papers') as $row) {
        $pt   = $row['paper_type']       ?? null;
        $gsm  = $row['gsm']               ?? null;
        $cuts = $row['cuts_per_mother']   ?? null;
        $raw  = $row['raw_cost']          ?? null;
        if (!$pt || !$gsm || !$cuts || $raw === null) continue;
        $pt  = (string)$pt;
        $gsm = (string)$gsm;
        if (!isset($paperData[$pt])) $paperData[$pt] = [];
        $paperData[$pt][$gsm] = ['s' => (int)$cuts, 'cost' => (float)$raw];
    }

    // Settings → key/value
    $settings = [];
    foreach ($xlsx->getSheetAssoc('Settings') as $row) {
        $k = $row['setting'] ?? null;
        $v = $row['value']    ?? null;
        if (!$k || $v === null) continue;
        $settings[(string)$k] = is_numeric($v) ? (float)$v : $v;
    }

    // Click Tiers (regular + XXL) → list of {max, black, white, cmyk} rates per sheet
    $clickTiers    = readClickTiers($xlsx, 'Click Tiers');
    $clickTiersXxl = readClickTiers($xlsx, 'Click Tiers XXL');

    $data = [
        'paperData'     => $paperData,
        'settings'      => $settings,
        'clickTiers'    => $clickTiers,
        'clickTiersXxl' => $clickTiersXxl,
    ];

    @file_put_contents($cachePath, json_encode($data));
    return $data;
}

const PPS = ['BC' => 20, 'A7' => 16, 'A6' => 8, 'A5' => 4, 'A4' => 2, 'A3' => 1, 'LP' => 1];

// Tall poster, 300 x 600 mm. It does NOT fit an SRA3 (320 x 450): it is fed as a
// ~320 x 620 strip on the banner tray (press maximum 330 x 900). That breaks
// three assumptions the rest of this file makes, so it is special-cased:
//   · paper   — 1 strip per 460 x 640 mother sheet, so a poster costs the WHOLE
//               mother, not mother/cuts_per_mother as every other size does.
//   · clicks  — billed at the XXL tier, like A3.
//   · speed   — banner feed runs at machine_speed_long, not machine_speed.
// Plus a percentage uplift (long_size_uplift) for the unusual format.
const LONG_SIZES = ['LP'];

// Sizes that can only be printed on certain stocks. The shop greys the rest out
// in its dropdown, but that is a UI convenience the customer can bypass — this
// is the authority, because pay.php prices from here.
const SIZE_PAPERS = ['LP' => ['multiart']];

// Read a click-tier sheet → list of ['max'=>int, 'black'=>float, 'white'=>float, 'cmyk'=>float]
// sorted ascending by max_sheets. Tolerates legacy column names (blanc/quadri/noir).
function readClickTiers(PricingXlsx $xlsx, string $sheet): array
{
    $rows = $xlsx->getSheetAssoc($sheet);
    $tiers = [];
    foreach ($rows as $r) {
        $max = $r['max_sheets'] ?? null;
        if ($max === null) continue;
        $tiers[] = [
            'max'   => (int)$max,
            'black' => (float)($r['black'] ?? $r['noir']   ?? 0),
            'white' => (float)($r['white'] ?? $r['blanc']  ?? 0),
            'cmyk'  => (float)($r['cmyk']  ?? $r['quadri'] ?? 0),
        ];
    }
    usort($tiers, fn($a, $b) => $a['max'] <=> $b['max']);
    return $tiers;
}

function clickSellRate(string $ink, int $sheets, array $tiers): float
{
    if (!$tiers) return 0.0;
    foreach ($tiers as $tier) {
        if ($sheets <= $tier['max']) {
            return $tier[$ink] ?? $tier['cmyk'];
        }
    }
    $t = end($tiers);
    return $t[$ink] ?? $t['cmyk'];
}

function roundToStep(float $price, float $step, float $downThreshold): float
{
    $lower = floor($price / $step) * $step;
    $above = $price - $lower;
    if ($above == 0) return $price;
    if ($above <= $downThreshold) return $lower;
    return $lower + $step;
}

function parsePaperKey(string $key): ?array
{
    static $codeMap = ['ms' => 'multiart', 'bt' => 'biotop', 'rs' => 'recystar'];
    if (strlen($key) < 3) return null;
    $code = substr($key, 0, 2);
    $weight = (int)substr($key, 2);
    if (!isset($codeMap[$code]) || $weight < 1) return null;
    return ['paperType' => $codeMap[$code], 'weight' => $weight];
}

function computePrice(array $input, array $pricing): array
{
    $paperData = $pricing['paperData'];
    $s = $pricing['settings'];

    $SETUP_SHEETS = (int)($s['setup_sheets']        ?? 6);
    $PREPRESS     = (float)($s['prepress_fee']       ?? 9.60);
    $MACHINE_COST = (float)($s['machine_cost_rate']  ?? 85);
    $MACHINE_SELL = (float)($s['machine_sell_rate']  ?? 120);
    $LABOR_MIN    = (float)($s['labor_minutes']      ?? 15);
    $MARKUP       = (float)($s['paper_markup']       ?? 0.5) * 100;   // %
    $MIN_MARGIN   = (float)($s['min_margin']         ?? 0.3) * 100;   // %
    $VAT          = (float)($s['vat_rate']           ?? 0.21) * 100;  // %
    $EXPRESS      = (float)($s['express_markup']     ?? 0.35) * 100;  // %
    $ROUND_STEP   = (float)($s['round_step']         ?? 1);
    $ROUND_THRESH = (float)($s['round_down_threshold'] ?? 0.5);
    $MACHINE_SPEED = (float)($s['machine_speed']     ?? 4140);
    $MACHINE_SPEED_LONG = (float)($s['machine_speed_long'] ?? 1242);
    $LONG_UPLIFT   = (float)($s['long_size_uplift']  ?? 0.25) * 100;  // %
    // Accepts either click_cmyk_cost (current) or click_cmyk_cost_single (legacy).
    $CLICK_COST   = (float)($s['click_cmyk_cost'] ?? $s['click_cmyk_cost_single'] ?? 0.035);

    $pd = $paperData[$input['paperType']][(string)$input['weight']] ?? null;
    if (!$pd) return ['ok' => false, 'error' => 'paper not in catalog'];

    $pps      = PPS[$input['size']] ?? null;
    if ($pps === null) return ['ok' => false, 'error' => 'unknown size'];

    $allowedPapers = SIZE_PAPERS[$input['size']] ?? null;
    if ($allowedPapers && !in_array($input['paperType'], $allowedPapers, true)) {
        return ['ok' => false, 'error' => 'paper not available in this size'];
    }
    $isLong   = in_array($input['size'], LONG_SIZES, true);
    $isXXL    = $input['size'] === 'A3' || $isLong;
    $qty      = $input['qty'];
    $versions = max(1, (int)($input['versions'] ?? 1));
    $sides    = $input['sides'];

    // Each version is its own print run with its own setup overhead.
    // total SRA3 = (print sheets per version + setup per version) × versions
    // $pd['cost'] is the mother-sheet cost and $pd['s'] how many SRA3 come off it.
    // A long poster takes one strip per mother, so there is nothing to divide by.
    $paperCostPerSheet  = $isLong ? $pd['cost'] : $pd['cost'] / $pd['s'];
    $sra3v              = (int)ceil($qty / $pps);
    $totalSra3          = ($sra3v + $SETUP_SHEETS) * $versions;
    $paperCostTotal     = $totalSra3 * $paperCostPerSheet;
    $paperSellTotal     = $paperCostTotal * (1 + $MARKUP / 100);

    $tiers              = $isXXL ? ($pricing['clickTiersXxl'] ?? []) : ($pricing['clickTiers'] ?? []);
    $sellRate           = clickSellRate('cmyk', $totalSra3, $tiers);
    $sellPerSheet       = $sides === 'double' ? $sellRate * 2 : $sellRate;
    $clickSellTotal     = $totalSra3 * $sellPerSheet;
    $costPerSheet       = $sides === 'double' ? $CLICK_COST * 2 : $CLICK_COST;
    $clickCostTotal     = $totalSra3 * $costPerSheet;

    $machineHours       = $totalSra3 / ($isLong ? $MACHINE_SPEED_LONG : $MACHINE_SPEED);
    $laborHours         = $LABOR_MIN / 60;
    $totalHours         = $machineHours + $laborHours;
    $machineCost        = $totalHours * $MACHINE_COST;
    $machineSell        = $totalHours * $MACHINE_SELL;

    $totalCost          = $paperCostTotal + $clickCostTotal + $machineCost;
    $totalSell          = $paperSellTotal + $clickSellTotal + $machineSell + $PREPRESS;
    $margin             = $totalCost > 0 ? (($totalSell - $totalCost) / $totalCost) * 100 : 0;

    $marginSell = $margin < $MIN_MARGIN ? $totalCost * (1 + $MIN_MARGIN / 100) : $totalSell;

    if ($isLong) $marginSell *= (1 + $LONG_UPLIFT / 100);

    $expressFee  = $input['express'] ? $marginSell * $EXPRESS / 100 : 0;
    $expressSell = $marginSell + $expressFee;

    $rawInclVat     = $expressSell * (1 + $VAT / 100);
    $roundedInclVat = roundToStep($rawInclVat, $ROUND_STEP, $ROUND_THRESH);
    $finalExVat     = $roundedInclVat / (1 + $VAT / 100);

    // Add delivery flat (incl VAT) — outside the rounded calc
    $deliveryInclVat = $input['delivery'] === 'dpd' ? 15.0 : 0.0;
    $deliveryExVat   = $deliveryInclVat / (1 + $VAT / 100);
    $finalInclVat    = $roundedInclVat + $deliveryInclVat;
    $finalExVat     += $deliveryExVat;

    // "Per piece" is per actual delivered piece (qty × versions), not per print run.
    $totalPieces = max(1, $qty * $versions);

    return [
        'ok'             => true,
        'inclVat'        => round($finalInclVat, 2),
        'exVat'          => round($finalExVat, 2),
        'perPieceIncl'   => round($finalInclVat / $totalPieces, 4),
        'perPieceEx'     => round($finalExVat / $totalPieces, 4),
        'qty'            => $qty,
        'versions'       => $versions,
        'totalPieces'    => $totalPieces,
    ];
}

// ──────────────────── Input parsing ────────────────────
/**
 * Returns ['items'=>[raw items], 'envelope'=>['express','delivery'], 'is_legacy'=>bool].
 * Legacy: a flat single-item request (GET or form POST) is wrapped as one item
 * and flagged so the response can be flattened to match the old contract.
 */
function readInput(): array
{
    $src = [];
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'POST') {
        $ctype = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($ctype, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $src = json_decode($raw, true) ?? [];
        } else {
            $src = $_POST;
        }
    } else {
        $src = $_GET;
    }

    // New shape: items array + order envelope
    if (isset($src['items']) && is_array($src['items'])) {
        return [
            'items'    => $src['items'],
            'envelope' => readOrderEnvelope($src),
            'is_legacy' => false,
        ];
    }

    // Legacy shape: a single flat item — wrap it
    $oneItem = [
        'product'   => $src['product']   ?? '',
        'paper_key' => $src['paper_key'] ?? '',
        'size'      => $src['size']      ?? '',
        'qty'       => $src['qty']       ?? 0,
        'versions'  => $src['versions']  ?? 1,
        'sides'     => $src['sides']     ?? 'single',
    ];
    return [
        'items'    => [$oneItem],
        'envelope' => readOrderEnvelope($src),
        'is_legacy' => true,
    ];
}

// ──────────────────── Main ────────────────────
// Guard: when pay.php requires this file purely for the pricing helpers,
// it sets PAY_PHP_INCLUDE so the request-handling block below doesn't run.
if (defined('PAY_PHP_INCLUDE')) return;

try {
    $in = readInput();

    $check = validateItems($in['items']);
    if (!$check['ok']) {
        http_response_code(400);
        $resp = ['ok' => false, 'error' => $check['error']];
        if (isset($check['item_index'])) $resp['item_index'] = $check['item_index'];
        echo json_encode($resp); exit;
    }
    $items = $check['items'];

    $pricing = loadPricingData();

    // Price each item individually with delivery='pickup' so computePrice never
    // adds the €15 fee. Delivery is an order-level flat that we add once at the end.
    $perItem        = [];
    $subtotalIncl   = 0.0;
    $subtotalEx     = 0.0;
    $deadline = wd_production_deadline((bool)($in['envelope']['express'] ?? false));

    foreach ($items as $item) {
        $res = computePrice([
            'paperType' => $item['paperType'],
            'weight'    => $item['weight'],
            'size'      => $item['size'],
            'qty'       => $item['qty'],
            'versions'  => $item['versions'],
            'sides'     => $item['sides'],
            'express'   => $in['envelope']['express'],
            'delivery'  => 'pickup',
        ], $pricing);

        if (!($res['ok'] ?? false)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => $res['error'] ?? 'pricing failed']);
            exit;
        }

        $perItem[] = [
            'id'           => $item['id'],
            'inclVat'      => $res['inclVat'],
            'exVat'        => $res['exVat'],
            'perPieceIncl' => $res['perPieceIncl'],
            'perPieceEx'   => $res['perPieceEx'],
            'qty'          => $res['qty'],
            'versions'     => $res['versions'],
            'totalPieces'  => $res['totalPieces'],
        ];
        $subtotalIncl += $res['inclVat'];
        $subtotalEx   += $res['exVat'];
    }

    $deliveryIncl = $in['envelope']['delivery'] === 'dpd' ? 15.0 : 0.0;
    $deliveryEx   = round($deliveryIncl / 1.21, 2);
    $totalIncl    = round($subtotalIncl + $deliveryIncl, 2);
    $totalEx      = round($subtotalEx   + $deliveryEx,   2);

    if ($in['is_legacy']) {
        // Backward-compatible flat response for the old single-item GET/form interface.
        // Per-piece amounts include the delivery share — matches the old contract.
        $only = $perItem[0];
        $finalIncl = round($only['inclVat'] + $deliveryIncl, 2);
        $finalEx   = round($only['exVat']   + $deliveryEx,   2);
        echo json_encode([
            'ok'           => true,
            'inclVat'      => $finalIncl,
            'exVat'        => $finalEx,
            'perPieceIncl' => round($finalIncl / max(1, $only['totalPieces']), 4),
            'perPieceEx'   => round($finalEx   / max(1, $only['totalPieces']), 4),
            'qty'          => $only['qty'],
            'versions'     => $only['versions'],
            'totalPieces'  => $only['totalPieces'],
            'productionDeadline' => $deadline['date'],
            'productionDays'     => $deadline['days'],
        ]);
        exit;
    }

    echo json_encode([
        'ok'              => true,
        'items'           => $perItem,
        'subtotalInclVat' => round($subtotalIncl, 2),
        'subtotalExVat'   => round($subtotalEx,   2),
        'deliveryInclVat' => $deliveryIncl,
        'deliveryExVat'   => $deliveryEx,
        'totalInclVat'    => $totalIncl,
        'totalExVat'      => $totalEx,
        // Authoritative deadline: Europe/Brussels, Belgian holidays excluded.
        // The UI shows this instead of its own estimate so the customer sees
        // exactly the date pay.php will record on the order.
        'productionDeadline' => $deadline['date'],
        'productionDays'     => $deadline['days'],
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'server error']);
    // For debugging only — strip this line in production if desired:
    error_log('calc.php error: ' . $e->getMessage());
}
