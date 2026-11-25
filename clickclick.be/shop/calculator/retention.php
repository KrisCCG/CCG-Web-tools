<?php
/**
 * Click Click — GDPR retention sweeps.
 *
 * Three passes over /orders/, each idempotent and safe to re-run:
 *
 *   1. ARTWORK_RETENTION_DAYS (default 180) after paid_at — delete files in
 *      /orders/{invoice}/artwork/ and set artwork_paths = [] in the JSON.
 *      The order metadata + invoice PDF stay (legal record).
 *
 *   2. PII_RETENTION_DAYS (default 730 = 2y) after paid_at — replace customer
 *      PII fields in the order JSON with "[redacted-YYYY-MM]". Invoice number,
 *      amount, specs, dates stay — these are the tax-record fields.
 *
 *   3. paid_orders.log PII rewrite — pipe-delimited log; redact name + email
 *      columns for rows older than PII_RETENTION_DAYS.
 *
 * Cron (weekly, Sunday 05:00):
 *   0 5 * * 0 php /home/clients/<id>/sites/clickclick.be/calculator/retention.php
 *
 * Run with --dry-run to log what would change without writing anything.
 */

declare(strict_types=1);

// Web access gated by HTTP basic-auth — see comment in backup.php for details.

const ORDERS_DIR_RETENTION  = __DIR__ . '/orders/';
const PAID_LOG_PATH         = __DIR__ . '/orders/paid_orders.log';
const RETENTION_LOG         = __DIR__ . '/orders/retention.log';
const ARTWORK_RETENTION_DAYS = 180;          // 6 months
const PII_RETENTION_DAYS     = 730;          // 2 years

$dry_run = in_array('--dry-run', $argv ?? [], true);
retention_log('start' . ($dry_run ? ' (dry-run)' : ''));

$now             = time();
$artwork_cutoff  = $now - (ARTWORK_RETENTION_DAYS * 86400);
$pii_cutoff      = $now - (PII_RETENTION_DAYS     * 86400);

$artwork_purged = 0;
$pii_redacted   = 0;
$skipped        = 0;

$entries = @scandir(ORDERS_DIR_RETENTION) ?: [];
foreach ($entries as $name) {
    if ($name === '.' || $name === '..' || $name === '.htaccess') continue;
    if (!preg_match('/^(\d{4})-(\d{3})\.json$/', $name, $m)) continue;

    $invoice = $m[1] . '-' . $m[2];
    $path    = ORDERS_DIR_RETENTION . $name;
    $raw     = @file_get_contents($path);
    if ($raw === false) { $skipped++; continue; }
    $order = json_decode($raw, true);
    if (!is_array($order)) { $skipped++; continue; }

    $paid_at = isset($order['paid_at']) ? strtotime((string)$order['paid_at']) : null;
    if (!$paid_at) continue; // only paid orders are subject to retention

    $changed = false;

    // ── 1. Artwork purge ──────────────────────────────────────────────────
    if ($paid_at < $artwork_cutoff) {
        $art_dir = ORDERS_DIR_RETENTION . $invoice . '/artwork/';
        if (is_dir($art_dir)) {
            $files = @scandir($art_dir) ?: [];
            foreach ($files as $f) {
                if ($f === '.' || $f === '..') continue;
                $fp = $art_dir . $f;
                if (is_file($fp)) {
                    if (!$dry_run) @unlink($fp);
                    $artwork_purged++;
                }
            }
            if (!$dry_run) {
                @rmdir($art_dir);
                @rmdir(ORDERS_DIR_RETENTION . $invoice);
            }
            if (!empty($order['artwork_paths']) || !empty($order['artwork_path'])) {
                $order['artwork_paths']    = [];
                $order['artwork_path']     = '';
                $order['artwork_purged_at'] = date('c');
                $changed = true;
            }
        }
    }

    // ── 2. PII redaction ──────────────────────────────────────────────────
    if ($paid_at < $pii_cutoff) {
        $stamp = '[redacted-' . date('Y-m') . ']';
        $pii_fields = [
            'customer_name', 'customer_email', 'customer_phone',
            'customer_company', 'customer_address', 'customer_zip', 'customer_city',
            'ship_address', 'ship_zip', 'ship_city',
            'vat_number', 'receipt_email',
        ];
        foreach ($pii_fields as $f) {
            if (!array_key_exists($f, $order)) continue;
            $v = $order[$f];
            if (is_string($v) && $v !== '' && strpos($v, '[redacted-') === false) {
                $order[$f] = $stamp;
                $changed = true;
            }
        }
        if ($changed && empty($order['pii_redacted_at'])) {
            $order['pii_redacted_at'] = date('c');
        }
    }

    if ($changed) {
        $pii_redacted++;
        if (!$dry_run) {
            @file_put_contents($path, json_encode($order, JSON_PRETTY_PRINT));
        }
    }
}

retention_log("orders: artwork_files_purged={$artwork_purged} json_redacted={$pii_redacted} skipped={$skipped}");

// ── 3. paid_orders.log PII rewrite ────────────────────────────────────────
// Format (from webhook.php:240): iso8601 | invoice | €amount | name | email
$log_redacted = 0;
if (is_file(PAID_LOG_PATH)) {
    $lines = @file(PAID_LOG_PATH, FILE_IGNORE_NEW_LINES) ?: [];
    $new_lines = [];
    foreach ($lines as $line) {
        $parts = explode(' | ', $line);
        if (count($parts) >= 5) {
            $ts = strtotime((string)$parts[0]);
            if ($ts !== false && $ts < $pii_cutoff
                && strpos($parts[3], '[redacted-') === false) {
                $stamp = '[redacted-' . date('Y-m') . ']';
                $parts[3] = $stamp;
                $parts[4] = $stamp;
                $line = implode(' | ', $parts);
                $log_redacted++;
            }
        }
        $new_lines[] = $line;
    }
    if ($log_redacted > 0 && !$dry_run) {
        // Atomic rewrite via tempfile to avoid a half-written log on crash.
        $tmp = PAID_LOG_PATH . '.tmp';
        @file_put_contents($tmp, implode("\n", $new_lines) . "\n");
        @rename($tmp, PAID_LOG_PATH);
    }
}
retention_log("paid_orders.log: rows_redacted={$log_redacted}");

retention_log('done');
exit(0);

function retention_log(string $msg): void {
    @file_put_contents(RETENTION_LOG, date('c') . ' ' . $msg . "\n", FILE_APPEND | LOCK_EX);
}
