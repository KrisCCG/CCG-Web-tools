<?php
/**
 * Shop v2 — pricing endpoint. Same calculation as the internal calculator (engine.php =
 * engine.js = check_prices.py, verified with data/selftest.json).
 *
 * GET  calc.php?catalogue=1   → public product list (names, sizes, papers, folds, quantities).
 *                               No costs, no margins.
 * POST calc.php  (JSON)       → { items:[{id,product,size,paper,fold,sides,qty,versions}], express, delivery }
 *   ← { ok, items:[{id,inclVat,exVat,perPieceIncl,perPieceEx,qty,versions,totalPieces}],
 *       subtotalInclVat, subtotalExVat, deliveryInclVat, deliveryExVat, totalInclVat, totalExVat,
 *       productionDeadline, productionDays }
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/lib/engine.php';
require_once __DIR__ . '/lib/items.php';
require_once __DIR__ . '/lib/workdays.php';
require_once __DIR__ . '/lib/express.php';

const DATA_DIR = __DIR__ . '/data';

if (defined('PAY_PHP_INCLUDE')) return;

function calc_fail(int $code, string $msg, ?int $idx = null): void
{
    http_response_code($code);
    $r = ['ok' => false, 'error' => $msg];
    if ($idx !== null) $r['item_index'] = $idx;
    echo json_encode($r);
    exit;
}

try {
    try {
        $d = ccp_data(DATA_DIR);
    } catch (CcpError $e) {
        error_log('calc.php: ' . $e->getMessage());
        calc_fail(503, 'prices temporarily unavailable');
    }

    if (isset($_GET['catalogue'])) {
        // holiday: the closure dates while the holiday mode runs (banner in the shop), else null (holiday.php, 26 Oct 2026)
        $hs = function_exists('holiday_status') ? holiday_status() : ['active' => false];
        $hol = !empty($hs['active']) ? ['from' => $hs['from'], 'lastClosed' => $hs['last_closed'], 'back' => $hs['back'],
            'firstReady' => function_exists('wd_holiday_first_ready') ? wd_holiday_first_ready() : null] : null;
        echo json_encode(['ok' => true, 'expressAvailable' => express_available(), 'holiday' => $hol] + ccp_catalogue($d));
        exit;
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') calc_fail(405, 'POST required');
    require_once __DIR__ . '/lib/humancheck.php';
    if (isset($_GET['human'])) hc_verify_request();
    // 5 free calculations per hour, then the visitor must tick "I'm a client" (see lib/humancheck.php).
    if (!isset($_GET['log'])) hc_gate();
    $src = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($src)) calc_fail(400, 'invalid JSON');

    $check = validateItems($src['items'] ?? [], $d);
    if (!$check['ok']) calc_fail(400, $check['error'], $check['item_index'] ?? null);
    $env = readOrderEnvelope($src);
    // Express switched off by the team (Dashboard): refuse, the shop page then hides the option.
    if ($env['express'] && !express_available()) calc_fail(409, 'express_unavailable');

    $out = []; $subIncl = 0.0; $subEx = 0.0;
    foreach ($check['items'] as $i => $item) {
        try {
            $r = priceItem($item, $env['express'], $d);
        } catch (CcpError $e) {
            calc_fail(400, $e->getMessage(), $i);
        }
        $out[] = ['id' => $item['id']] + $r;
        $subIncl += $r['inclVat']; $subEx += $r['exVat'];
    }
    // Browse statistics (?log=1): the shop page sends the configuration the visitor is looking at, 3 s after
    // the last change. One anonymous line per quote in orders/calc_log.jsonl — no name, no IP, no e-mail.
    // Max 1 line per IP per 10 s; the file stops growing at 5 MB. The Dashboard (api-dashboard.php?action=logs) reads it.
    if (isset($_GET['log'])) {
        require_once __DIR__ . '/lib/ratelimit.php';
        $logFile = __DIR__ . '/orders/calc_log.jsonl';
        if (rate_limit_check('calclog:' . rate_limit_client_ip(), 1, 10)
            && (!is_file($logFile) || filesize($logFile) < 5000000)) {
            $it = end($check['items']); $r = end($out);
            if (!is_dir(dirname($logFile))) @mkdir(dirname($logFile), 0755, true);
            @file_put_contents($logFile, json_encode([
                'timestamp' => date('c'),
                'src'       => 'v2',
                'product'   => $it['product'],
                'tab'       => ucfirst((string)$it['product']),
                'paper'     => $it['paper'],
                'size'      => $it['size'],
                'fold'      => $it['fold'],
                'pages'     => $it['pages'],
                'quantity'  => $it['qty'],
                'sides'     => $it['sides'],
                'versions'  => $it['versions'],
                'express'   => (bool)$env['express'],
                'price'     => $r['inclVat'],
                'priceExVat'=> $r['exVat'],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
        }
        echo json_encode(['ok' => true]);
        exit;
    }

    $vat = 1 + (float)$d['rules']['vat_rate'];
    $delIncl = $env['delivery'] === 'dpd' ? (float)$d['rules']['delivery_dpd'] : 0.0;
    $delEx = round($delIncl / $vat, 2);
    $deadline = wd_production_deadline($env['express']);
    echo json_encode([
        'ok' => true, 'items' => $out,
        'subtotalInclVat' => round($subIncl, 2), 'subtotalExVat' => round($subEx, 2),
        'deliveryInclVat' => $delIncl, 'deliveryExVat' => $delEx,
        'totalInclVat' => round($subIncl + $delIncl, 2), 'totalExVat' => round($subEx + $delEx, 2),
        'productionDeadline' => $deadline['date'], 'productionDays' => $deadline['days'],
    ]);
} catch (Throwable $e) {
    error_log('calc.php error: ' . $e->getMessage());
    calc_fail(500, 'server error');
}
