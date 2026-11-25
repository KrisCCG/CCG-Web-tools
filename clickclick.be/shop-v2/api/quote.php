<?php
/**
 * Out-of-Belgium quote request.
 *
 * The shop's DPD delivery is a flat EUR 15 and is Belgium-only. When a customer
 * selects any other country, the frontend swaps the Pay button for "Request a
 * quote" and POSTs here instead of pay.php. Nothing is charged: we save the
 * request and email the team, who reply with a final delivered price.
 *
 * POST JSON:
 *   { items:[{product,paper_key,size,qty,versions,sides,label?}],
 *     express, country, customer_name, customer_email, customer_phone,
 *     customer_company, customer_address, customer_zip, customer_city,
 *     ship_address?, ship_zip?, ship_city?, vat_number?, lang, csrf_token }
 *
 * Returns { ok:true, quote_id } or { ok:false, error }.
 *
 * NB: no price is calculated or quoted here on purpose. Shipping outside
 * Belgium is exactly the thing we can't price automatically — that is the whole
 * reason this endpoint exists. The team quotes it by hand.
 */

$secrets = @include __DIR__ . '/secrets.php';
if (!is_array($secrets)) {
    error_log('quote.php: secrets.php missing');
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Service unavailable']);
    exit;
}

define('COMPANY_EMAIL', 'shop@clickclickgraphics.com');
define('QUOTES_DIR',    __DIR__ . '/orders/quotes/');

require_once __DIR__ . '/lib/audit.php';
require_once __DIR__ . '/lib/ratelimit.php';
require_once __DIR__ . '/csrf.php';

header('Content-Type: application/json; charset=utf-8');

// Same budget as pay.php — this writes a file and sends mail, so it needs a cap.
rate_limit_or_die('quote:' . rate_limit_client_ip(), 10, 3600, 'json');

function q_fail(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

function q_clean($v, int $max = 200): string {
    return mb_substr(htmlspecialchars(strip_tags(trim((string)$v)), ENT_QUOTES, 'UTF-8'), 0, $max);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    q_fail('POST required', 405);
}

$raw = file_get_contents('php://input');
$in  = json_decode($raw, true);
if (!is_array($in)) q_fail('Malformed request');

if (!csrf_validate((string)($in['csrf_token'] ?? ''))) {
    audit_log('csrf.miss', ['where' => 'quote.php']);
    q_fail('Session expired — please reload the page and try again.', 403);
}

// ── Validate ──────────────────────────────────────────────────────────────

$country = q_clean($in['country'] ?? '', 60);
if ($country === '') q_fail('Country is required');
if (strcasecmp($country, 'BE') === 0 || strcasecmp($country, 'Belgium') === 0) {
    // Belgium goes through the normal paid checkout — never through here.
    q_fail('Belgian orders are placed directly in the shop.');
}

$email = trim((string)($in['customer_email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) q_fail('A valid email address is required');

$name = q_clean($in['customer_name'] ?? '');
if ($name === '') q_fail('Name is required');

$items_in = $in['items'] ?? [];
if (!is_array($items_in) || count($items_in) === 0) q_fail('No items in the request');
if (count($items_in) > 50) q_fail('Too many items');

$items = [];
foreach ($items_in as $it) {
    if (!is_array($it)) continue;
    $qty = (int)($it['qty'] ?? 0);
    if ($qty < 1 || $qty > 10000) q_fail('Invalid quantity');
    $items[] = [
        'product'   => q_clean($it['product']   ?? '', 40),
        'paper_key' => q_clean($it['paper'] ?? $it['paper_key'] ?? '', 30),
        'fold'      => q_clean($it['fold'] ?? '', 20),
        'pages'     => max(0, min(200, (int)($it['pages'] ?? 0))),
        'cover'     => q_clean($it['cover'] ?? '', 30),
        'size'      => q_clean($it['size']      ?? '', 10),
        'sides'     => q_clean($it['sides']     ?? '', 10),
        'versions'  => max(1, (int)($it['versions'] ?? 1)),
        'qty'       => $qty,
    ];
}
if (!$items) q_fail('No valid items in the request');

// ── Persist first, mail second — the file is the recoverable record ───────

if (!is_dir(QUOTES_DIR) && !@mkdir(QUOTES_DIR, 0770, true) && !is_dir(QUOTES_DIR)) {
    error_log('quote.php: cannot create ' . QUOTES_DIR);
    q_fail('Could not save your request — please email ' . COMPANY_EMAIL, 500);
}

$quote_id = date('Y-m-d') . '-' . bin2hex(random_bytes(4));

$record = [
    'quote_id'     => $quote_id,
    'submitted_at' => date('c'),
    'ip_hash'      => substr(hash('sha256', audit_client_ip()), 0, 16),
    'country'      => $country,
    'lang'         => q_clean($in['lang'] ?? '', 5),
    'express'      => !empty($in['express']),
    'items'        => $items,
    'customer'     => [
        'name'    => $name,
        'email'   => $email,
        'phone'   => q_clean($in['customer_phone']   ?? '', 40),
        'company' => q_clean($in['customer_company'] ?? ''),
        'vat'     => q_clean($in['vat_number']       ?? '', 30),
        'address' => q_clean($in['customer_address'] ?? ''),
        'zip'     => q_clean($in['customer_zip']     ?? '', 20),
        'city'    => q_clean($in['customer_city']    ?? ''),
    ],
    'ship' => [
        'address' => q_clean($in['ship_address'] ?? ''),
        'zip'     => q_clean($in['ship_zip']     ?? '', 20),
        'city'    => q_clean($in['ship_city']    ?? ''),
    ],
];

if (@file_put_contents(QUOTES_DIR . $quote_id . '.json',
        json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
    error_log('quote.php: could not write record ' . $quote_id);
    q_fail('Could not save your request — please email ' . COMPANY_EMAIL, 500);
}

audit_log('quote_requested', ['quote_id' => $quote_id, 'country' => $country]);

// ── Email the team ────────────────────────────────────────────────────────

$rows = '';
foreach ($items as $i => $it) {
    $rows .= sprintf(
        '<tr><td style="padding:4px 10px 4px 0;">%d</td><td style="padding:4px 10px 4px 0;">%s %s, %s, %s</td>'
        . '<td style="padding:4px 0;"><strong>%d</strong>%s</td></tr>',
        $i + 1,
        $it['product'], $it['size'] . ($it['fold'] !== '' ? ' ' . $it['fold'] : '') . ($it['pages'] ? ' ' . $it['pages'] . ' pages' : ''),
        ($it['cover'] !== '' ? 'cover ' . $it['cover'] . ' + inside ' : '') . $it['paper_key'], $it['sides'],
        $it['qty'],
        $it['versions'] > 1 ? ' × ' . $it['versions'] . ' versions' : ''
    );
}

$c = $record['customer'];
$ship = array_filter($record['ship']);
$ship_line = $ship
    ? implode(', ', $ship)
    : $c['address'] . ', ' . $c['zip'] . ' ' . $c['city'];

$html = '<!doctype html><html><body style="font-family:-apple-system,Arial,sans-serif;color:#222;font-size:14px;line-height:1.6;">'
    . '<h2 style="margin:0 0 4px;">Quote request — delivery to ' . $country . '</h2>'
    . '<p style="margin:0 0 16px;color:#666;">Outside Belgium, so the shop could not price shipping. '
    . 'Reply to this email to reach the customer directly.</p>'
    . '<p style="margin:0 0 4px;"><strong>Reference:</strong> ' . $quote_id . '</p>'
    . '<p style="margin:0 0 16px;"><strong>Production:</strong> '
    . ($record['express'] ? 'EXPRESS requested (48 h)' : 'standard, 5 working days')
    . '</p>'
    . '<table style="border-collapse:collapse;margin-bottom:16px;">' . $rows . '</table>'
    . '<p style="margin:0 0 4px;"><strong>' . $c['name'] . '</strong>'
    . ($c['company'] !== '' ? ' — ' . $c['company'] : '') . '</p>'
    . '<p style="margin:0 0 4px;">' . $c['email']
    . ($c['phone'] !== '' ? ' · ' . $c['phone'] : '') . '</p>'
    . ($c['vat'] !== '' ? '<p style="margin:0 0 4px;">VAT: ' . $c['vat'] . '</p>' : '')
    . '<p style="margin:0 0 4px;"><strong>Deliver to:</strong> ' . $ship_line . ', <strong>' . $country . '</strong></p>'
    . '</body></html>';

$headers  = "From: Click Click Graphics SRL <shop@clickclickgraphics.com>\r\n";
$headers .= "Reply-To: " . $email . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

$sent = @mail(
    COMPANY_EMAIL,
    'Quote request — ' . $country . ' — ' . $name,
    $html,
    $headers,
    '-fshop@clickclickgraphics.com'
);

if (!$sent) {
    // Non-fatal: the JSON on disk is the record of truth, same contract as the
    // chatbot's submit.php. The customer still gets a success response.
    error_log('quote.php: mail() failed for ' . $quote_id);
    audit_log('quote_mail_failed', ['quote_id' => $quote_id]);
}

echo json_encode(['ok' => true, 'quote_id' => $quote_id]);
