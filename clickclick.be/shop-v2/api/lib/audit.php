<?php
/**
 * Click Click — append-only audit log.
 *
 * One JSON line per event in /calculator/orders/audit.log. PII is redacted at
 * log time — never log a full email, phone, name, or address.
 *
 * Usage:
 *   require_once __DIR__ . '/lib/audit.php';
 *   audit_log('payment.create', ['invoice' => $invoice_num, 'email' => $email]);
 *
 * Sensitive keys recognised by audit_redact():
 *   email, customer_email, receipt_email   → "abc***@domain.tld"
 *   name,  customer_name                   → "K***"
 *   phone, customer_phone                  → "+32***"
 *   address, customer_address, ship_address, zip, city, *_zip, *_city
 *                                          → "[redacted]"
 *   vat, vat_number                        → "[redacted]"
 */

declare(strict_types=1);

const AUDIT_LOG_FILE = __DIR__ . '/../orders/audit.log';

function audit_redact(array $context): array {
    $sensitive_full = [
        'customer_address', 'customer_zip', 'customer_city',
        'ship_address',     'ship_zip',     'ship_city',
        'address', 'zip', 'city',
        'vat', 'vat_number',
    ];
    foreach ($context as $k => $v) {
        if (!is_string($v) || $v === '') continue;

        $lk = strtolower((string)$k);
        if ($lk === 'email' || $lk === 'customer_email' || $lk === 'receipt_email') {
            // first 3 chars + ***@domain
            $at = strrpos($v, '@');
            if ($at !== false && $at >= 1) {
                $local  = substr($v, 0, $at);
                $domain = substr($v, $at + 1);
                $head   = substr($local, 0, min(3, strlen($local)));
                $context[$k] = $head . '***@' . $domain;
            } else {
                $context[$k] = '[redacted]';
            }
            continue;
        }
        if ($lk === 'name' || $lk === 'customer_name') {
            $first = substr($v, 0, 1);
            $context[$k] = $first . '***';
            continue;
        }
        if ($lk === 'phone' || $lk === 'customer_phone') {
            $head = substr($v, 0, min(3, strlen($v)));
            $context[$k] = $head . '***';
            continue;
        }
        if (in_array($lk, $sensitive_full, true)) {
            $context[$k] = '[redacted]';
        }
    }
    return $context;
}

function audit_log(string $event, array $context = []): void {
    $entry = [
        'ts'      => date('c'),
        'event'   => $event,
        'ip'      => audit_client_ip(),
        'ua_hash' => isset($_SERVER['HTTP_USER_AGENT'])
            ? substr(hash('sha256', (string)$_SERVER['HTTP_USER_AGENT']), 0, 16)
            : null,
        'context' => audit_redact($context),
    ];
    $line = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    // Ensure orders/ exists (other endpoints normally guarantee this).
    $dir = dirname(AUDIT_LOG_FILE);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    @file_put_contents(AUDIT_LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Best-effort client IP. Behind Infomaniak's edge, X-Forwarded-For is
 * populated; the leftmost entry is the real client. Falls back to REMOTE_ADDR.
 */
function audit_client_ip(): string {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $first = trim(explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        if (filter_var($first, FILTER_VALIDATE_IP)) return $first;
    }
    return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}
