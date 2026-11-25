<?php
/**
 * Shop v2 — create the payment for a cart.
 * Prices are recalculated here with engine.php (never trusted from the browser).
 * While config.php has 'payments_enabled' => false, everything is checked and priced,
 * but no payment is created and no order is saved: the shop shows a test message.
 */
$config = @include __DIR__ . '/config.php';
define('PAYMENTS_ENABLED', is_array($config) && !empty($config['payments_enabled']));
define('SITE_URL',         'https://clickclick.be');
define('COMPANY_NAME',     'Click Click Graphics SRL');
define('COMPANY_EMAIL',    'shop@clickclickgraphics.com');
define('COMPANY_IBAN',     'BE41 7360 6158 9610');
define('ORDERS_DIR',       __DIR__ . '/orders/');
define('CALC_BASE_URL',    SITE_URL . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));

define('PAY_PHP_INCLUDE', true);
require_once __DIR__ . '/calc.php';          // engine.php, items.php, workdays.php

require_once __DIR__ . '/lib/audit.php';
require_once __DIR__ . '/lib/ratelimit.php';
require_once __DIR__ . '/csrf.php';

// Rate-limit: 10 payment creations / IP / hour. Cheap to bypass with rotating
// IPs but stops the cheap "fill the disk with pending JSONs" abuse.
rate_limit_or_die('pay:' . rate_limit_client_ip(), 10, 3600, 'text');

// ── Helpers ───────────────────────────────────────────────────────────────
function mollie_post($endpoint, $data) {
    $ch = curl_init('https://api.mollie.com/v2/' . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . MOLLIE_API_KEY,
            'Content-Type: application/json',
        ],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function sanitize($str) {
    return htmlspecialchars(strip_tags(trim((string)$str)), ENT_QUOTES, 'UTF-8');
}

function generate_invoice_number(bool $test = false) {
    $year = date('Y');
    // Test payments get their own counter and a TEST- prefix, so they can never clash with the
    // live shop's numbers (2026-0xx in /calculator/orders/).
    $file = ORDERS_DIR . ($test ? 'invoice_counter_test_' : 'invoice_counter_') . $year . '.txt';
    if (!file_exists($file)) file_put_contents($file, '0');
    $n = (int)file_get_contents($file) + 1;
    file_put_contents($file, $n);
    return ($test ? 'TEST-' : '') . $year . '-' . str_pad((string)$n, 3, '0', STR_PAD_LEFT);
}

/**
 * Detect whether the request is JSON (new multi-item cart) or legacy form/GET.
 * Returns ['mode'=>'json'|'legacy', 'src'=>array].
 */
function read_request_source(): array {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $ctype  = $_SERVER['CONTENT_TYPE'] ?? '';
    if ($method === 'POST' && stripos($ctype, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (is_array($data)) {
            return ['mode' => 'json', 'src' => $data];
        }
    }
    return ['mode' => 'legacy', 'src' => array_merge($_GET ?? [], $_POST ?? [])];
}

/**
 * Emit a JSON error and exit. Used in the JSON path; legacy path uses die().
 */
function json_fail(int $http, string $msg, ?int $itemIndex = null): void {
    http_response_code($http);
    header('Content-Type: application/json; charset=utf-8');
    $payload = ['ok' => false, 'error' => $msg];
    if ($itemIndex !== null) $payload['item_index'] = $itemIndex;
    echo json_encode($payload);
    exit;
}

/**
 * For each cart item, verify upload_id is a UUID-v4 referencing a non-empty
 * temp folder, and grab the filenames for the order JSON.
 * Returns an associative array: [upload_id => string[] filenames].
 */
function collect_artwork_for_items(array $items): array {
    $out = [];
    foreach ($items as $i => $item) {
        $uid = (string)($item['upload_id'] ?? '');
        if ($uid === '') {
            json_fail(400, 'missing upload_id for item', $i);
        }
        if (!preg_match(ITEM_UUID_REGEX, $uid)) {
            json_fail(400, 'invalid upload reference', $i);
        }
        if (isset($out[$uid])) {
            json_fail(400, 'duplicate upload_id across items', $i);
        }
        $temp_path = ORDERS_DIR . 'temp/' . $uid;
        $entries = is_dir($temp_path) ? @scandir($temp_path) : [];
        $files = is_array($entries)
            ? array_values(array_filter(
                $entries,
                fn($e) => $e !== '.' && $e !== '..' && is_file($temp_path . '/' . $e)
            ))
            : [];
        if (count($files) < 1) {
            json_fail(400, 'no artwork found for item', $i);
        }
        $out[$uid] = $files;
    }
    return $out;
}

// ── Ensure orders directory exists, and is HTTP-protected ─────────────────
// PHP can still read it server-side; only direct HTTP access (URL guessing for
// e.g. `/calculator/orders/2026-042.json`) is blocked.
if (!is_dir(ORDERS_DIR)) {
    mkdir(ORDERS_DIR, 0755, true);
}
$orders_htaccess = ORDERS_DIR . '.htaccess';
if (!is_file($orders_htaccess)) {
    @file_put_contents(
        $orders_htaccess,
        "# Block direct HTTP access to order JSONs and uploaded artwork.\n" .
        "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n" .
        "<IfModule !mod_authz_core.c>\n  Order deny,allow\n  Deny from all\n</IfModule>\n" .
        "Options -Indexes\n"
    );
}

// ──────────────────────────────────────────────────────────────────────────
// JSON path: new multi-item cart submit
// ──────────────────────────────────────────────────────────────────────────
$req = read_request_source();

if ($req['mode'] === 'json') {
    $src = $req['src'];

    // CSRF check — token issued by /calculator/csrf.php on page load,
    // one-shot consumed here.
    $csrf_token = (string)($src['csrf_token'] ?? '');
    if (!csrf_validate($csrf_token)) {
        audit_log('csrf.miss', ['where' => 'pay.php', 'mode' => 'json']);
        json_fail(403, 'Session expired. Please reload the page and try again.');
    }

    // Price data (refuses to work if the self-test against the update script fails)
    try { $d = ccp_data(DATA_DIR); } catch (CcpError $e) { json_fail(503, 'prices temporarily unavailable'); }

    // Validate items
    $check = validateItems($src['items'] ?? [], $d);
    if (!$check['ok']) {
        json_fail(400, $check['error'], $check['item_index'] ?? null);
    }
    $items_validated = $check['items'];

    // Attach upload_id (validateItem strips unknown fields, so carry it ourselves)
    foreach ($items_validated as $i => &$it) {
        $it['upload_id'] = (string)($src['items'][$i]['upload_id'] ?? '');
    }
    unset($it);

    // Confirm each item's artwork is on disk
    $artwork_by_item = collect_artwork_for_items($items_validated);

    // Envelope
    $env       = readOrderEnvelope($src);
    $delivery  = $env['delivery'];      // 'pickup' | 'dpd'
    $express   = $env['express'];       // bool
    require_once __DIR__ . '/lib/express.php';
    if ($express && !express_available()) json_fail(409, 'express_unavailable');

    // Customer fields (mirror legacy field names so $order JSON shape is stable)
    $vat_number       = sanitize($src['vat_number']        ?? '');
    $payment_type     = sanitize($src['payment_type']      ?? 'bancontact');
    $customer_name    = sanitize($src['customer_name']     ?? '');
    $customer_email   = sanitize($src['customer_email']    ?? '');
    $customer_phone   = sanitize($src['customer_phone']    ?? '');
    $customer_company = sanitize($src['customer_company']  ?? '');
    $customer_address = sanitize($src['customer_address']  ?? '');
    $customer_zip     = sanitize($src['customer_zip']      ?? '');
    $customer_city    = sanitize($src['customer_city']     ?? '');
    $doc_type         = sanitize($src['doc_type']          ?? 'receipt');
    $receipt_email    = sanitize($src['receipt_email']     ?? 'shop@clickclickgraphics.com');
    $ship_address     = sanitize($src['ship_address']      ?? '');
    $ship_zip         = sanitize($src['ship_zip']          ?? '');
    $ship_city        = sanitize($src['ship_city']         ?? '');
    $lang             = in_array(($src['lang'] ?? ''), ['nl', 'fr', 'en'], true) ? $src['lang'] : 'en';

    if ($customer_name === '' || $customer_email === '' || $customer_address === '' || $customer_city === '') {
        json_fail(400, 'missing required customer fields');
    }
    if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        json_fail(400, 'invalid email');
    }

    // Server-side recompute — never trust client totals
    $items_priced = [];
    $subtotalIncl = 0.0;
    $subtotalEx   = 0.0;
    foreach ($items_validated as $i => $item) {
        try {
            $res = priceItem($item, $express, $d);
        } catch (CcpError $e) {
            json_fail(400, $e->getMessage(), $i);
        }
        $items_priced[] = [
            'id'                => $item['id'] !== '' ? $item['id'] : bin2hex(random_bytes(8)),
            'product'           => $item['product'],
            'size'              => $item['size'],
            'paper'             => $item['paper'],
            'fold'              => $item['fold'],
            'pages'             => $item['pages'],
            'cover'             => $item['cover'],
            'qty'               => $item['qty'],
            'versions'          => $item['versions'],
            'sides'             => $item['sides'],
            'upload_id'         => $item['upload_id'],
            'artwork_filenames' => $artwork_by_item[$item['upload_id']] ?? [],
            'price_incl'        => $res['inclVat'],
            'price_ex'          => $res['exVat'],
            'vat_rate'          => $res['vatRate'],   // 21 % for all products today; kept per item so the invoice can show one line per rate if that ever changes
            'specs_line'        => itemSpecsLine($item, $d),
        ];
        $subtotalIncl += $res['inclVat'];
        $subtotalEx   += $res['exVat'];
    }

    $deliveryIncl = $delivery === 'dpd' ? (float)$d['rules']['delivery_dpd'] : 0.0;
    $deliveryEx   = round($deliveryIncl / (1 + (float)$d['rules']['vat_rate']), 2);
    $totalIncl    = round($subtotalIncl + $deliveryIncl, 2);
    $totalEx      = round($subtotalEx   + $deliveryEx,   2);
    $amount_cents = (int) round($totalIncl * 100);

    if ($amount_cents < 100) {
        json_fail(400, 'amount too small');
    }

    // Optional client-side total cross-check (advisory; server is authoritative)
    if (isset($src['claimed_total_cents'])) {
        $claimed = (int)$src['claimed_total_cents'];
        if (abs($claimed - $amount_cents) > 2) {
            error_log("pay.php: client total {$claimed} mismatched server total {$amount_cents}");
        }
    }

    // ── Test shop: stop here. Everything above (validation, artwork, prices) has run. ──
    if (!PAYMENTS_ENABLED) {
        audit_log('payment.disabled', ['amount_cents' => $amount_cents, 'item_count' => count($items_priced)]);
        json_fail(503, 'TEST SHOP: online payment is switched off. Your order was checked (total EUR '
            . number_format($amount_cents / 100, 2, '.', '') . ') but NOT placed.');
    }
    $secrets = @include __DIR__ . '/secrets.php';
    if (!is_array($secrets) || empty($secrets['mollie_key'])) {
        error_log('pay.php: secrets.php missing or incomplete');
        json_fail(500, 'Service unavailable. Please try again later.');
    }
    define('MOLLIE_API_KEY', $secrets['mollie_key']);

    // This preview site is behind a login and has its own order folder: it only takes Mollie TEST
    // payments. A live key is refused until config.php says 'allow_live' => true (go-live step).
    $is_test_key = strpos((string)$secrets['mollie_key'], 'test_') === 0;
    if (!$is_test_key && !(is_array($config) && !empty($config['allow_live']))) {
        error_log('pay.php (shop-v2): live Mollie key refused, config allow_live is false');
        json_fail(500, 'Service unavailable. Please try again later.');
    }

    $invoice_num = generate_invoice_number($is_test_key);
    $amount_eur  = number_format($amount_cents / 100, 2, '.', '');

    // Build multi-line specs string for email / human display
    $specs_lines  = array_map(fn($it) => $it['specs_line'], $items_priced);
    $specs_lines[] = $delivery === 'dpd' ? 'Delivery DPD' : 'Pick-up';
    $specs        = implode("\n", $specs_lines);
    $item_count   = count($items_priced);
    $description  = "Click Click order #{$invoice_num} ({$item_count} " . ($item_count === 1 ? 'item' : 'items') . ")";
    if (strlen($description) > 255) {
        $description = substr($description, 0, 252) . '...';
    }

    // Mollie payload — keep metadata small (1KB limit); rich order lives in JSON on disk.
    $payment_data = [
        'amount'      => ['currency' => 'EUR', 'value' => $amount_eur],
        'description' => $description,
        'redirectUrl' => CALC_BASE_URL . '/thankyou.php?invoice=' . urlencode($invoice_num),
        'webhookUrl'  => CALC_BASE_URL . '/webhook.php',
        'method'      => ($payment_type === 'bancontact') ? 'bancontact' : null,
        'metadata'    => [
            'invoice_num' => $invoice_num,
        ],
    ];
    if ($payment_data['method'] === null) unset($payment_data['method']);

    $payment = mollie_post('payments', $payment_data);
    if (!isset($payment['id'])) {
        error_log('Mollie error: ' . json_encode($payment));
        audit_log('payment.failed', [
            'invoice'      => $invoice_num,
            'mollie_error' => isset($payment['detail']) ? (string)$payment['detail'] : 'unknown',
            'mode'         => 'json',
        ]);
        json_fail(502, 'payment provider error');
    }

    audit_log('payment.create', [
        'invoice'        => $invoice_num,
        'mollie_id'      => $payment['id'],
        'amount_eur'     => $amount_eur,
        'customer_email' => $customer_email,
        'customer_name'  => $customer_name,
        'delivery'       => $delivery,
        'item_count'     => $item_count,
        'mode'           => 'json',
    ]);

    $deadline = wd_production_deadline((bool)$express);

    $order = [
        'schema_version'    => 2,
        'test_mode'         => $is_test_key,
        'lang'              => $lang,
        'invoice_num'       => $invoice_num,
        'mollie_id'         => $payment['id'],
        'amount_eur'        => $amount_eur,
        'amount_inc'        => $totalIncl,
        'amount_ex'         => $totalEx,
        'subtotal_inc'      => round($subtotalIncl, 2),
        'subtotal_ex'       => round($subtotalEx,   2),
        'delivery_inc'      => $deliveryIncl,
        'delivery_ex'       => $deliveryEx,
        'items'             => $items_priced,
        'specs'             => $specs,
        'vat_number'        => $vat_number,
        'payment_type'      => $payment_type,
        'status'            => 'pending',
        'created_at'        => date('c'),
        'customer_name'     => $customer_name,
        'customer_email'    => $customer_email,
        'customer_phone'    => $customer_phone,
        'customer_company'  => $customer_company,
        'customer_address'  => $customer_address,
        'customer_zip'      => $customer_zip,
        'customer_city'     => $customer_city,
        'delivery'          => $delivery === 'dpd' ? 'yes' : 'no',
        'express'           => $express,
        // Last possible production deadline, fixed at order creation. Computed
        // server-side in Europe/Brussels and excluding Belgian public holidays,
        // so it is authoritative — the browser's own estimate is only a display.
        'production_deadline' => $deadline['date'],
        'production_days'     => $deadline['days'],
        'doc_type'          => $doc_type,
        'receipt_email'     => $receipt_email,
        'ship_address'      => $ship_address,
        'ship_zip'          => $ship_zip,
        'ship_city'         => $ship_city,
    ];
    file_put_contents(
        ORDERS_DIR . $invoice_num . '.json',
        json_encode($order, JSON_PRETTY_PRINT)
    );

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok'          => true,
        'invoice_num' => $invoice_num,
        'checkoutUrl' => $payment['_links']['checkout']['href'],
    ]);
    exit;
}

// Only the JSON cart submit is supported in shop v2.
json_fail(400, 'JSON request required');
