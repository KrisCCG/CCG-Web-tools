<?php
/**
 * Shop v2 — GDPR retention sweeps (ported from /calculator/retention.php, 21 Oct 2026).
 *
 * Three passes over api/orders/, each idempotent and safe to re-run:
 *
 *   1. ARTWORK_RETENTION_DAYS (180) after paid_at — delete everything under orders/{invoice}/artwork/
 *      (cart orders keep one sub-folder per item: artwork/items/{id}/…, so the delete is recursive),
 *      clear the artwork paths in the JSON. The order data and the receipt PDF stay (legal record).
 *
 *   2. PII_RETENTION_DAYS (730 = 2 years) after paid_at — replace customer PII in the order JSON with
 *      "[redacted-YYYY-MM]" and drop the uploaded file NAMES (they can contain a person's name).
 *      Invoice number, amounts, specs, items and dates stay: those are the tax-record fields.
 *
 *   3. paid_orders.log — redact the name + e-mail columns of rows older than PII_RETENTION_DAYS.
 *      Row format: iso8601 | [TEST|LIVE] | invoice | €amount | name | e-mail  (the TEST/LIVE column is
 *      new in shop v2; rows without it, from the old shop, are handled too).
 *
 * TEST-… orders (Mollie test mode) are swept like any other order.
 * A fourth pass deletes rotated security logs (orders/audit-YYYY-WW.log) after 180 days, as the privacy page promises.
 *
 * Schedule (Infomaniak Task Scheduler, HTTPS, "password protected" with the cron login), weekly Sunday 05:00:
 *   https://clickclick.be/shop-v2/api/retention.php         (after go-live: /shop/api/retention.php)
 * Dry run (changes nothing, writes the report only):  …/retention.php?dry=1   or   php retention.php --dry-run
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); }

const ORDERS_DIR_RETENTION   = __DIR__ . '/orders/';
const PAID_LOG_PATH          = __DIR__ . '/orders/paid_orders.log';
const RETENTION_LOG          = __DIR__ . '/orders/retention.log';
const ARTWORK_RETENTION_DAYS = 180;          // 6 months
const PII_RETENTION_DAYS     = 730;          // 2 years
const AUDIT_RETENTION_DAYS   = 180;          // security logs: 6 months (as promised on the privacy page)
const INVOICE_FILE_RE        = '/^((?:TEST-)?\d{4}-\d{3})\.json$/';

$dry_run = in_array('--dry-run', $argv ?? [], true) || !empty($_GET['dry']);
retention_log('start' . ($dry_run ? ' (dry-run)' : ''));

$now            = time();
$artwork_cutoff = $now - (ARTWORK_RETENTION_DAYS * 86400);
$pii_cutoff     = $now - (PII_RETENTION_DAYS     * 86400);

$artwork_purged = 0;   // files deleted
$json_changed   = 0;
$skipped        = 0;

$entries = @scandir(ORDERS_DIR_RETENTION) ?: [];
foreach ($entries as $name) {
    if (!preg_match(INVOICE_FILE_RE, $name, $m)) continue;
    $invoice = $m[1];
    $path    = ORDERS_DIR_RETENTION . $name;
    $raw     = @file_get_contents($path);
    if ($raw === false) { $skipped++; continue; }
    $order = json_decode($raw, true);
    if (!is_array($order)) { $skipped++; continue; }

    $paid_at = isset($order['paid_at']) ? strtotime((string)$order['paid_at']) : false;
    if (!$paid_at) continue;   // only paid orders are subject to retention

    $changed = false;

    // ── 1. Artwork purge ──────────────────────────────────────────────────
    if ($paid_at < $artwork_cutoff) {
        $inv_dir = ORDERS_DIR_RETENTION . $invoice . '/';
        if (is_dir($inv_dir . 'artwork')) {
            $artwork_purged += rrmdir_count($inv_dir . 'artwork', $dry_run);
            if (!$dry_run) @rmdir($inv_dir);   // only succeeds when empty
        }
        $had_paths = !empty($order['artwork_paths']) || !empty($order['artwork_path']);
        if (is_array($order['items'] ?? null)) {
            foreach ($order['items'] as $i => $it) {
                if (!empty($it['artwork_paths'])) { $order['items'][$i]['artwork_paths'] = []; $had_paths = true; }
            }
        }
        if ($had_paths) {
            $order['artwork_paths']     = [];
            $order['artwork_path']      = '';
            $order['artwork_purged_at'] = date('c');
            $changed = true;
        }
    }

    // ── 2. PII redaction ──────────────────────────────────────────────────
    if ($paid_at < $pii_cutoff) {
        $stamp = '[redacted-' . date('Y-m') . ']';
        foreach (pii_fields() as $f) {
            if (!array_key_exists($f, $order)) continue;
            $v = $order[$f];
            if (is_string($v) && $v !== '' && strpos($v, '[redacted-') === false) { $order[$f] = $stamp; $changed = true; }
        }
        // uploaded file names can carry a person's name — keep only how many files there were
        if (is_array($order['items'] ?? null)) {
            foreach ($order['items'] as $i => $it) {
                if (!empty($it['artwork_filenames']) && is_array($it['artwork_filenames'])) {
                    $order['items'][$i]['artwork_filenames'] = [];
                    $order['items'][$i]['artwork_file_count'] = count($it['artwork_filenames']);
                    $changed = true;
                }
            }
        }
        foreach (['artwork_filename', 'artwork_filenames'] as $f) {
            if (!empty($order[$f])) { $order[$f] = is_array($order[$f]) ? [] : ''; $changed = true; }
        }
        if ($changed && empty($order['pii_redacted_at'])) $order['pii_redacted_at'] = date('c');
    }

    if ($changed) {
        $json_changed++;
        if (!$dry_run) atomic_write($path, json_encode($order, JSON_PRETTY_PRINT));
    }
}
retention_log("orders: artwork_files_purged={$artwork_purged} json_changed={$json_changed} skipped={$skipped}");

// ── 3. paid_orders.log PII rewrite ────────────────────────────────────────
$log_redacted = 0;
if (is_file(PAID_LOG_PATH)) {
    $lines = @file(PAID_LOG_PATH, FILE_IGNORE_NEW_LINES) ?: [];
    $new_lines = [];
    foreach ($lines as $line) {
        $parts = explode(' | ', $line);
        // v2 rows: ts | TEST/LIVE | invoice | amount | name | email   (old rows: ts | invoice | amount | name | email)
        $off = (isset($parts[1]) && in_array($parts[1], ['TEST', 'LIVE'], true)) ? 1 : 0;
        if (count($parts) >= 5 + $off) {
            $ts = strtotime((string)$parts[0]);
            if ($ts !== false && $ts < $pii_cutoff && strpos($parts[3 + $off], '[redacted-') === false) {
                $stamp = '[redacted-' . date('Y-m') . ']';
                $parts[3 + $off] = $stamp;
                $parts[4 + $off] = $stamp;
                $line = implode(' | ', $parts);
                $log_redacted++;
            }
        }
        $new_lines[] = $line;
    }
    if ($log_redacted > 0 && !$dry_run) atomic_write(PAID_LOG_PATH, implode("\n", $new_lines) . "\n");
}
retention_log("paid_orders.log: rows_redacted={$log_redacted}");

// ── 4. Security logs: rotated audit logs (audit-YYYY-WW.log) older than AUDIT_RETENTION_DAYS ──
// The privacy page promises "security logs: 6 months at most, deleted automatically".
$audit_deleted = 0;
$audit_cutoff  = $now - (AUDIT_RETENTION_DAYS * 86400);
foreach (@scandir(ORDERS_DIR_RETENTION) ?: [] as $name) {
    if (!preg_match('/^audit-\d{4}-W\d{2}\.log$/', $name)) continue;
    $p = ORDERS_DIR_RETENTION . $name;
    if (@filemtime($p) !== false && filemtime($p) < $audit_cutoff) { if (!$dry_run) @unlink($p); $audit_deleted++; }
}
retention_log("audit logs: files_deleted={$audit_deleted}");
retention_log('done');
exit(0);

// ── Helpers ───────────────────────────────────────────────────────────────
function pii_fields(): array {
    return ['customer_name', 'customer_email', 'customer_phone', 'customer_company', 'customer_address',
            'customer_zip', 'customer_city', 'ship_address', 'ship_zip', 'ship_city', 'vat_number', 'receipt_email'];
}
function atomic_write(string $path, string $data): void {
    $tmp = $path . '.tmp';
    if (@file_put_contents($tmp, $data, LOCK_EX) !== false) @rename($tmp, $path);
}
/** Delete a folder tree (or only count, in a dry run). Returns the number of files. */
function rrmdir_count(string $dir, bool $dry): int {
    $n = 0;
    foreach (@scandir($dir) ?: [] as $f) {
        if ($f === '.' || $f === '..') continue;
        $p = $dir . '/' . $f;
        if (is_link($p)) { if (!$dry) @unlink($p); continue; }
        if (is_dir($p)) { $n += rrmdir_count($p, $dry); }
        elseif (is_file($p)) { $n++; if (!$dry) @unlink($p); }
    }
    if (!$dry) @rmdir($dir);
    return $n;
}
function retention_log(string $msg): void {
    @file_put_contents(RETENTION_LOG, date('c') . ' ' . $msg . "\n", FILE_APPEND | LOCK_EX);
    echo $msg . "\n";
}
