<?php
/**
 * Click Click — GDPR right-to-erasure endpoint.
 *
 * Gated by HTTP basic-auth (see admin/.htaccess). Takes a single ?email=…
 * query param and redacts PII in every matching order JSON. Returns a small
 * JSON summary and logs the action.
 *
 * Usage:
 *   GET /calculator/admin/erase.php?email=customer@example.com
 *   GET /calculator/admin/erase.php?email=customer@example.com&dry=1
 *
 * Response:
 *   { ok: true, email: "abc***@example.com", matched: 3, redacted: 3, dry: false }
 *
 * What it redacts (matches retention.php fields exactly):
 *   customer_name, customer_email, customer_phone, customer_company,
 *   customer_address, customer_zip, customer_city,
 *   ship_address, ship_zip, ship_city, vat_number, receipt_email
 *
 * What it preserves (legal record):
 *   invoice_num, amount_eur, amount_inc, amount_ex, specs, paid_at, mollie_id,
 *   payment_type, status, dates. The PDF invoice in /orders/{invoice}/ is the
 *   authoritative tax record.
 *
 * Artwork is also deleted on erasure (right-to-erasure goes further than
 * retention.php's 6-month window).
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../lib/audit.php';

const ERASE_ORDERS_DIR = __DIR__ . '/../orders/';

$email = trim((string)($_GET['email'] ?? ''));
$dry   = !empty($_GET['dry']);

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing or invalid email param']);
    exit;
}

$entries = @scandir(ERASE_ORDERS_DIR) ?: [];
$stamp   = '[redacted-' . date('Y-m') . ']';

$matched = 0;
$redacted = 0;
$artwork_deleted = 0;

$pii_fields = [
    'customer_name', 'customer_email', 'customer_phone',
    'customer_company', 'customer_address', 'customer_zip', 'customer_city',
    'ship_address', 'ship_zip', 'ship_city',
    'vat_number', 'receipt_email',
];

foreach ($entries as $name) {
    if ($name === '.' || $name === '..' || $name === '.htaccess') continue;
    if (!preg_match('/^(\d{4})-(\d{3})\.json$/', $name, $m)) continue;

    $invoice = $m[1] . '-' . $m[2];
    $path    = ERASE_ORDERS_DIR . $name;
    $raw     = @file_get_contents($path);
    if ($raw === false) continue;
    $order = json_decode($raw, true);
    if (!is_array($order)) continue;

    $order_email = strtolower((string)($order['customer_email'] ?? ''));
    if ($order_email !== strtolower($email)) continue;

    $matched++;
    $changed = false;
    foreach ($pii_fields as $f) {
        if (!array_key_exists($f, $order)) continue;
        $v = $order[$f];
        if (is_string($v) && $v !== '' && strpos($v, '[redacted-') === false) {
            $order[$f] = $stamp;
            $changed = true;
        }
    }
    if ($changed) {
        $order['pii_redacted_at'] = date('c');
        $order['pii_redacted_reason'] = 'gdpr_request';
        $redacted++;
        if (!$dry) {
            @file_put_contents($path, json_encode($order, JSON_PRETTY_PRINT));
        }
    }

    // Delete artwork too — erasure goes further than retention.
    $art_dir = ERASE_ORDERS_DIR . $invoice . '/artwork/';
    if (is_dir($art_dir)) {
        $files = @scandir($art_dir) ?: [];
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            $fp = $art_dir . $f;
            if (is_file($fp)) {
                if (!$dry) @unlink($fp);
                $artwork_deleted++;
            }
        }
        if (!$dry) {
            @rmdir($art_dir);
            @rmdir(ERASE_ORDERS_DIR . $invoice);
        }
    }
}

audit_log('admin.erase', [
    'email'           => $email,
    'matched'         => $matched,
    'redacted'        => $redacted,
    'artwork_deleted' => $artwork_deleted,
    'dry'             => $dry,
    'admin_user'      => $_SERVER['PHP_AUTH_USER'] ?? 'unknown',
]);

echo json_encode([
    'ok'              => true,
    'email'           => (function($e) {
        $at = strrpos($e, '@');
        return $at >= 1
            ? substr($e, 0, min(3, $at)) . '***@' . substr($e, $at + 1)
            : '[redacted]';
    })($email),
    'matched'         => $matched,
    'redacted'        => $redacted,
    'artwork_deleted' => $artwork_deleted,
    'dry'             => $dry,
], JSON_PRETTY_PRINT);
