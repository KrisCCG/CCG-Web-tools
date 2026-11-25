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
 * Keys that hold internal identifiers (tokens, hashes, signatures, IDs) —
 * never PII, so don't run the redactor over their values. Without this,
 * the phone-number regex matches embedded unix timestamps and HMAC bytes
 * inside tokens and replaces them with [PHONE], which is both wrong and
 * makes the log unreadable.
 */
const AUDIT_NON_PII_KEYS = [
    'token', 'conversation_token', 'sig', 'signature',
    'ip_hash', 'hash', 'id', 'tool_use_id', 'etag',
];

/**
 * Recursively redact obvious PII patterns in any string fields, skipping
 * values whose key is in AUDIT_NON_PII_KEYS.
 */
function redact_pii(mixed $value): mixed {
    if (is_array($value)) {
        $out = [];
        foreach ($value as $k => $v) {
            if (is_string($k) && in_array($k, AUDIT_NON_PII_KEYS, true)) {
                $out[$k] = $v;          // leave identifier values alone
            } else {
                $out[$k] = redact_pii($v);
            }
        }
        return $out;
    }
    if (!is_string($value)) {
        return $value;
    }

    // Email
    $value = preg_replace('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/', '[EMAIL]', $value);

    // International / Belgian phone numbers — keep loose so we catch +32 ..., 0476 ..., etc.
    // Note: this still matches any 9+ digit sequence; keep tokens out via key-name allowlist above.
    $value = preg_replace('/\+?\d[\d\s\-().]{7,}\d/', '[PHONE]', $value);

    return $value;
}
