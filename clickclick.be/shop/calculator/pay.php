<?php
// ── Configuration ─────────────────────────────────────────────────────────
$secrets = @include __DIR__ . '/secrets.php';
if (!is_array($secrets) || empty($secrets['mollie_key'])) {
    error_log('pay.php: secrets.php missing or incomplete');
    http_response_code(500);
    die('Service unavailable. Please try again later.');
}
define('MOLLIE_API_KEY',   $secrets['mollie_key']);
define('SITE_URL',         'https://clickclick.be');
define('COMPANY_NAME',     'Click Click Graphics SRL');
define('COMPANY_EMAIL',    'shop@clickclickgraphics.com');
define('COMPANY_IBAN',     'BE41 7360 6158 9610');
define('ORDERS_DIR',       __DIR__ . '/orders/');
// Derive the calculator base URL from the running script's directory so this file
// works whether deployed to /calculator/ (prod) or /calculator-v2/ (test site).
define('CALC_BASE_URL',    SITE_URL . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));

// Pull computePrice() and loadPricingData() from calc.php — the PAY_PHP_INCLUDE
// flag tells calc.php to skip its request-handling main block.
define('PAY_PHP_INCLUDE', true);
require_once __DIR__ . '/lib/xlsx_reader.php';
require_once __DIR__ . '/lib/items.php';
require_once __DIR__ . '/calc.php';

require_once __DIR__ . '/lib/audit.php';
require_once __DIR__ . '/lib/ratelimit.php';
require_once __DIR__ . '/lib/workdays.php';
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

function generate_invoice_number() {
    $year = date('Y');
    $file = ORDERS_DIR . 'invoice_counter_' . $year . '.txt';
    if (!file_exists($file)) file_put_contents($file, '0');
    $n = (int)file_get_contents($file) + 1;
    file_put_contents($file, $n);
    return $year . '-' . str_pad((string)$n, 3, '0', STR_PAD_LEFT);
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

    // Validate items
    $check = validateItems($src['items'] ?? []);
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

    if ($customer_name === '' || $customer_email === '' || $customer_address === '' || $customer_city === '') {
        json_fail(400, 'missing required customer fields');
    }
    if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        json_fail(400, 'invalid email');
    }

    // Server-side recompute — never trust client totals
    $pricing      = loadPricingData();
    $items_priced = [];
    $subtotalIncl = 0.0;
    $subtotalEx   = 0.0;
    foreach ($items_validated as $item) {
        $res = computePrice([
            'paperType' => $item['paperType'],
            'weight'    => $item['weight'],
            'size'      => $item['size'],
            'qty'       => $item['qty'],
            'versions'  => $item['versions'],
            'sides'     => $item['sides'],
            'express'   => $express,
            'delivery'  => 'pickup',   // delivery added once at order level below
        ], $pricing);
        if (!($res['ok'] ?? false)) {
            json_fail(400, $res['error'] ?? 'pricing failed');
        }
        $items_priced[] = [
            'id'                => $item['id'] !== '' ? $item['id'] : bin2hex(random_bytes(8)),
            'product'           => $item['product'],
            'paper_key'         => $item['paper_key'],
            'size'              => $item['size'],
            'qty'               => $item['qty'],
            'versions'          => $item['versions'],
            'sides'             => $item['sides'],
            'upload_id'         => $item['upload_id'],
            'artwork_filenames' => $artwork_by_item[$item['upload_id']] ?? [],
            'price_incl'        => $res['inclVat'],
            'price_ex'          => $res['exVat'],
            'specs_line'        => itemSpecsLine($item),
        ];
        $subtotalIncl += $res['inclVat'];
        $subtotalEx   += $res['exVat'];
    }

    $deliveryIncl = $delivery === 'dpd' ? 15.0 : 0.0;
    $deliveryEx   = round($deliveryIncl / 1.21, 2);
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

    $invoice_num = generate_invoice_number();
    $amount_eur  = number_format($amount_cents / 100, 2, '.', '');

    // Build multi-line specs string for email / human display
    $specs_lines  = array_map(fn($it) => $it['specs_line'], $items_priced);
    $specs_lines[] = $delivery === 'dpd' ? 'Delivery €15' : 'Pick-up';
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

// ──────────────────────────────────────────────────────────────────────────
// Legacy path: single-product form POST / GET — preserved for transition only
// ──────────────────────────────────────────────────────────────────────────

// CSRF check (legacy form-based path)
$csrf_token = (string)($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '');
if (!csrf_validate($csrf_token)) {
    audit_log('csrf.miss', ['where' => 'pay.php', 'mode' => 'legacy']);
    http_response_code(403);
    die('Session expired. Please reload the page and try again.');
}

// ── Read & validate input ─────────────────────────────────────────────────
$amount_cents     = (int)($_GET['simpay_amount']   ?? $_POST['simpay_amount']   ?? 0);
$specs            = sanitize($_GET['specs']          ?? $_POST['specs']          ?? '');
$payment_type     = sanitize($_GET['payment']        ?? $_POST['payment']        ?? 'bancontact');
$vat_number       = sanitize($_GET['vat']            ?? $_POST['vat']            ?? '');
$customer_name    = sanitize($_GET['customer_name']    ?? $_POST['customer_name']    ?? '');
$customer_email   = sanitize($_GET['customer_email']   ?? $_POST['customer_email']   ?? '');
$customer_phone   = sanitize($_GET['customer_phone']   ?? $_POST['customer_phone']   ?? '');
$customer_company = sanitize($_GET['customer_company'] ?? $_POST['customer_company'] ?? '');
$customer_address = sanitize($_GET['customer_address'] ?? $_POST['customer_address'] ?? '');
$customer_zip     = sanitize($_GET['customer_zip']     ?? $_POST['customer_zip']     ?? '');
$customer_city    = sanitize($_GET['customer_city']    ?? $_POST['customer_city']    ?? '');
$delivery         = sanitize($_GET['delivery']         ?? $_POST['delivery']         ?? 'no');
$doc_type         = sanitize($_GET['doc_type']         ?? $_POST['doc_type']         ?? 'receipt');
$receipt_email    = sanitize($_GET['receipt_email']     ?? $_POST['receipt_email']     ?? 'shop@clickclickgraphics.com');
$ship_address     = sanitize($_GET['ship_address']     ?? $_POST['ship_address']     ?? '');
$ship_zip         = sanitize($_GET['ship_zip']         ?? $_POST['ship_zip']         ?? '');
$ship_city        = sanitize($_GET['ship_city']        ?? $_POST['ship_city']        ?? '');
$upload_id        = sanitize($_GET['upload_id']        ?? $_POST['upload_id']        ?? '');
$versions         = max(1, (int)($_GET['versions']     ?? $_POST['versions']         ?? 1));

if ($amount_cents < 100) {
    die('Invalid amount.');
}

$artwork_filenames = [];
if ($upload_id !== '') {
    if (!preg_match(ITEM_UUID_REGEX, $upload_id)) {
        die('Invalid upload reference.');
    }
    $temp_path = ORDERS_DIR . 'temp/' . $upload_id;
    $entries = is_dir($temp_path) ? @scandir($temp_path) : [];
    $artwork_filenames = is_array($entries)
        ? array_values(array_filter(
            $entries,
            fn($e) => $e !== '.' && $e !== '..' && is_file($temp_path . '/' . $e)
        ))
        : [];
    if (count($artwork_filenames) < 1) {
        die('Uploaded artwork not found. Please re-upload your file.');
    }
}
$artwork_filename = implode(', ', $artwork_filenames);

$amount_eur  = number_format($amount_cents / 100, 2, '.', '');
$invoice_num = generate_invoice_number();

$description = 'Print order — ' . $specs;
if (strlen($description) > 255) {
    $description = substr($description, 0, 252) . '...';
}

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
        'mode'         => 'legacy',
    ]);
    die('Payment could not be created. Please try again or contact us at ' . COMPANY_EMAIL);
}

audit_log('payment.create', [
    'invoice'        => $invoice_num,
    'mollie_id'      => $payment['id'],
    'amount_eur'     => $amount_eur,
    'customer_email' => $customer_email,
    'customer_name'  => $customer_name,
    'delivery'       => $delivery,
    'mode'           => 'legacy',
]);

$order = [
    'schema_version'    => 1,
    'invoice_num'       => $invoice_num,
    'mollie_id'         => $payment['id'],
    'amount_eur'        => $amount_eur,
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
    'delivery'          => $delivery,
    'doc_type'          => $doc_type,
    'receipt_email'     => $receipt_email,
    'ship_address'      => $ship_address,
    'ship_zip'          => $ship_zip,
    'ship_city'         => $ship_city,
    'upload_id'         => $upload_id,
    'artwork_filename'  => $artwork_filename,
    'artwork_filenames' => $artwork_filenames,
    'versions'          => $versions,
];
file_put_contents(
    ORDERS_DIR . $invoice_num . '.json',
    json_encode($order, JSON_PRETTY_PRINT)
);

header('Location: ' . $payment['_links']['checkout']['href']);
exit;
