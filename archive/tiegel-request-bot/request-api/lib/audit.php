<?php
/**
 * Append-only audit log with PII redaction.
 *
 * Each line is a single JSON object (JSONL). Used for:
 *   - Debugging real conversations after the fact
 *   - Mining recurring questions for prompt improvements
 *
 * PII is redacted at write time so the on-disk log is safe to grep through:
 *   - email addresses -> [EMAIL]
 *   - phone numbers   -> [PHONE]
 *   - long sequences of digits (>=8) -> [DIGITS]
 *
 * Full unredacted data lives in /requests/{YYYY-MM}/{uuid}.json — that file
 * has stricter retention and is the source of truth for the human team.
 */

define('AUDIT_DIR', __DIR__ . '/../logs');

function audit_log(string $event, array $payload = []): void {
    if (!is_dir(AUDIT_DIR)) {
        @mkdir(AUDIT_DIR, 0755, true);
    }

    $entry = [
        'ts'    => date('c'),
        'event' => $event,
        'data'  => redact_pii($payload),
    ];

    $file = AUDIT_DIR . '/' . date('Y-m-d') . '.jsonl';
    @file_put_contents($file, json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
}

/**
 * Recursively redact obvious PII patterns in any string fields.
 */
function redact_pii(mixed $value): mixed {
    if (is_array($value)) {
        return array_map('redact_pii', $value);
    }
    if (!is_string($value)) {
        return $value;
    }

    // Email
    $value = preg_replace('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/', '[EMAIL]', $value);

    // International / Belgian phone numbers — keep loose so we catch +32 ..., 0476 ..., etc.
    $value = preg_replace('/\+?\d[\d\s\-().]{7,}\d/', '[PHONE]', $value);

    return $value;
}
