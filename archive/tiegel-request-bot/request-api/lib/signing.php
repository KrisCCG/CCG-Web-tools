<?php
/**
 * HMAC-based signing for conversation tokens and individual chat turns.
 *
 * Why: the server is stateless; the client sends the conversation history
 * back with every request. Without signatures, a malicious client could
 * forge assistant turns to manipulate the bot. With signatures, the server
 * rejects any history turn it didn't itself produce.
 *
 * Two helpers:
 *   - issue_conversation_token(): returns a signed token with embedded
 *     issued_at timestamp. Verify against TTL on every call.
 *   - sign_turn() / verify_turn(): per-message signature so each assistant
 *     reply can be challenged on the next round-trip.
 *
 * Both use HMAC-SHA256 with the shared secret in secrets.php.
 */

/**
 * Compute the HMAC for an arbitrary string using the global signing key.
 * Lazy-loads secrets on first call.
 */
function sign_hmac(string $data): string {
    static $key = null;
    if ($key === null) {
        $secrets = require __DIR__ . '/../secrets.php';
        $key = $secrets['signing_key'] ?? '';
        if ($key === '' || $key === 'REPLACE_ME_WITH_64_HEX_CHARS') {
            http_response_code(500);
            error_log('signing.php: signing_key is not configured');
            exit('Server misconfigured');
        }
    }
    return hash_hmac('sha256', $data, $key);
}

/**
 * Issue a conversation token. Format: "{issued_at}.{nonce}.{hmac}"
 * Verify with verify_conversation_token().
 */
function issue_conversation_token(): string {
    $issued_at = time();
    $nonce     = bin2hex(random_bytes(8));
    $payload   = "{$issued_at}.{$nonce}";
    $sig       = sign_hmac($payload);
    return "{$payload}.{$sig}";
}

/**
 * Verify a conversation token. Returns the issued_at timestamp on success,
 * or false if the token is missing, malformed, expired, or has a bad signature.
 */
function verify_conversation_token(string $token, int $ttl_seconds): int|false {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;

    [$issued_at_str, $nonce, $sig] = $parts;
    if (!ctype_digit($issued_at_str) || $nonce === '' || $sig === '') return false;

    $issued_at = (int) $issued_at_str;
    if ($issued_at + $ttl_seconds < time()) return false;  // expired

    $expected = sign_hmac("{$issued_at}.{$nonce}");
    if (!hash_equals($expected, $sig)) return false;

    return $issued_at;
}

/**
 * Sign a single conversation turn. Stable across JSON serialisations by
 * hashing role + content explicitly (not the whole turn object).
 *
 * Tool-use content (assistant messages that include a tool_use block) is
 * also signed — the JSON-encoded content array participates in the HMAC.
 */
function sign_turn(string $role, mixed $content): string {
    $canonical = $role . "\0" . json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return sign_hmac($canonical);
}

function verify_turn(string $role, mixed $content, string $sig): bool {
    if ($sig === '') return false;
    return hash_equals(sign_turn($role, $content), $sig);
}
