<?php
/**
 * Shop v2 — GDPR right-to-erasure (ported from /calculator/admin/erase.php, 21 Oct 2026).
 *
 * Gated by HTTP basic-auth (admin/.htaccess, realm "Click Click — admin"). One ?email=… parameter: every order
 * of that customer is redacted, the artwork is deleted, and the customer's rows in paid_orders.log are redacted.
 *
 *   GET /shop-v2/api/admin/erase.php?email=customer@example.com&dry=1     dry run: shows what would change
 *   GET /shop-v2/api/admin/erase.php?email=customer@example.com           does it
 *   (after go-live the folder is /shop/api/admin/)
 *
 * Redacted (same list as retention.php): name, e-mail, phone, company, address, zip, city, ship address/zip/city,
 * VAT number, receipt e-mail, and the uploaded file names.
 * Kept (legal record): invoice number, amounts, specs, items, dates, Mollie id, status, and the receipt PDF
 * (orders/Receipt_*.pdf — the tax document; delete it by hand only if the accountant confirms it is not needed).
 *
 * New vs the old shop: cart orders keep artwork in per-item sub-folders (deleted recursively), TEST- order
 * numbers are recognised, and paid_orders.log rows of the customer are redacted too.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../lib/audit.php';

const ERASE_ORDERS_DIR = __DIR__ . '/../orders/';
const ERASE_PAID_LOG   = __DIR__ . '/../orders/paid_orders.log';

$email = trim((string)($_GET['email'] ?? ''));
$dry   = !empty($_GET['dry']);

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing or invalid email param']);
    exit;
}

$stamp = '[redacted-' . date('Y-m') . ']';
$pii_fields = ['customer_name', 'customer_email', 'customer_phone', 'customer_company', 'customer_address',
               'customer_zip', 'customer_city', 'ship_address', 'ship_zip', 'ship_city', 'vat_number', 'receipt_email'];

$matched = 0; $redacted = 0; $artwork_deleted = 0; $log_rows = 0;

foreach (@scandir(ERASE_ORDERS_DIR) ?: [] as $name) {
    if (!preg_match('/^((?:TEST-)?\d{4}-\d{3})\.json$/', $name, $m)) continue;
    $invoice = $m[1];
    $path    = ERASE_ORDERS_DIR . $name;
    $order   = json_decode((string)@file_get_contents($path), true);
    if (!is_array($order)) continue;
    if (strtolower((string)($order['customer_email'] ?? '')) !== strtolower($email)) continue;

    $matched++;
    $changed = false;
    foreach ($pii_fields as $f) {
        if (!array_key_exists($f, $order)) continue;
        $v = $order[$f];
        if (is_string($v) && $v !== '' && strpos($v, '[redacted-') === false) { $order[$f] = $stamp; $changed = true; }
    }
    // uploaded file names + stored artwork paths
    if (is_array($order['items'] ?? null)) {
        foreach ($order['items'] as $i => $it) {
            if (!empty($it['artwork_filenames']) && is_array($it['artwork_filenames'])) {
                $order['items'][$i]['artwork_file_count'] = count($it['artwork_filenames']);
                $order['items'][$i]['artwork_filenames']  = [];
                $changed = true;
            }
            if (!empty($it['artwork_paths'])) { $order['items'][$i]['artwork_paths'] = []; $changed = true; }
        }
    }
    foreach (['artwork_filename' => '', 'artwork_filenames' => [], 'artwork_paths' => [], 'artwork_path' => ''] as $f => $empty) {
        if (!empty($order[$f])) { $order[$f] = $empty; $changed = true; }
    }
    if ($changed) {
        $order['pii_redacted_at']     = date('c');
        $order['pii_redacted_reason'] = 'gdpr_request';
        $redacted++;
        if (!$dry) {
            $tmp = $path . '.tmp';
            if (@file_put_contents($tmp, json_encode($order, JSON_PRETTY_PRINT), LOCK_EX) !== false) @rename($tmp, $path);
        }
    }

    // Artwork goes too (erasure reaches further than the 6-month retention window).
    $art_dir = ERASE_ORDERS_DIR . $invoice . '/artwork';
    if (is_dir($art_dir)) {
        $artwork_deleted += erase_tree($art_dir, $dry);
        if (!$dry) @rmdir(ERASE_ORDERS_DIR . $invoice);
    }
}

// paid_orders.log rows of this customer: ts | [TEST/LIVE |] invoice | amount | name | email
if (is_file(ERASE_PAID_LOG)) {
    $lines = @file(ERASE_PAID_LOG, FILE_IGNORE_NEW_LINES) ?: [];
    foreach ($lines as $k => $line) {
        $p   = explode(' | ', $line);
        $off = (isset($p[1]) && in_array($p[1], ['TEST', 'LIVE'], true)) ? 1 : 0;
        if (count($p) >= 5 + $off && strtolower(trim($p[4 + $off])) === strtolower($email)) {
            $p[3 + $off] = $stamp; $p[4 + $off] = $stamp;
            $lines[$k] = implode(' | ', $p);
            $log_rows++;
        }
    }
    if ($log_rows > 0 && !$dry) {
        $tmp = ERASE_PAID_LOG . '.tmp';
        if (@file_put_contents($tmp, implode("\n", $lines) . "\n", LOCK_EX) !== false) @rename($tmp, ERASE_PAID_LOG);
    }
}

audit_log('admin.erase', [
    'email'           => $email,
    'matched'         => $matched,
    'redacted'        => $redacted,
    'artwork_deleted' => $artwork_deleted,
    'log_rows'        => $log_rows,
    'dry'             => $dry,
    'admin_user'      => $_SERVER['PHP_AUTH_USER'] ?? 'unknown',
]);

$at = strrpos($email, '@');
echo json_encode([
    'ok'              => true,
    'email'           => $at >= 1 ? substr($email, 0, min(3, $at)) . '***@' . substr($email, $at + 1) : '[redacted]',
    'matched'         => $matched,
    'redacted'        => $redacted,
    'artwork_deleted' => $artwork_deleted,
    'log_rows'        => $log_rows,
    'dry'             => $dry,
], JSON_PRETTY_PRINT);

function erase_tree(string $dir, bool $dry): int {
    $n = 0;
    foreach (@scandir($dir) ?: [] as $f) {
        if ($f === '.' || $f === '..') continue;
        $p = $dir . '/' . $f;
        if (is_link($p)) { if (!$dry) @unlink($p); continue; }
        if (is_dir($p)) { $n += erase_tree($p, $dry); }
        elseif (is_file($p)) { $n++; if (!$dry) @unlink($p); }
    }
    if (!$dry) @rmdir($dir);
    return $n;
}
