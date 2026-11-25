<?php
/**
 * Click Click — CSRF token issuer + validator.
 *
 * Endpoints:
 *   GET  /calculator/csrf.php?issue=1   → JSON { ok:true, token, expires_at }
 *                                         (used by flyers/index.html on load)
 *
 * Helpers (when require'd from another script):
 *   csrf_validate(string $token): bool   — true on hit; consumes the token
 *   csrf_load_tokens(): array            — internal, returns live token map
 *
 * Storage: /calculator/orders/.csrf-tokens.json   { token => expiry_unix }
 * TTL:     1 hour (CSRF_TTL_SEC)
 *
 * The form flow is slow ("fill in, upload, pay"), so 1h gives breathing room
 * without holding tokens forever. Tokens are one-shot — consumed by pay.php on
 * successful validation.
 */

declare(strict_types=1);

const CSRF_TOKEN_FILE = __DIR__ . '/orders/.csrf-tokens.json';
const CSRF_TTL_SEC    = 3600;
const CSRF_MAX_LIVE   = 5000; // sanity cap — bigger than any realistic concurrent-customer count

require_once __DIR__ . '/lib/audit.php';
require_once __DIR__ . '/lib/ratelimit.php';

/** Load + prune expired tokens. Returns [token => expiry] map. */
function csrf_load_tokens(): array {
    if (!is_file(CSRF_TOKEN_FILE)) return [];
    $raw = @file_get_contents(CSRF_TOKEN_FILE);
    if ($raw === false || $raw === '') return [];
    $map = json_decode($raw, true);
    if (!is_array($map)) return [];
    $now = time();
    $live = [];
    foreach ($map as $tok => $exp) {
        if (is_string($tok) && is_int($exp) && $exp > $now) {
            $live[$tok] = $exp;
        }
    }
    return $live;
}

/** Persist the token map atomically. */
function csrf_save_tokens(array $map): void {
    $dir = dirname(CSRF_TOKEN_FILE);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $tmp = CSRF_TOKEN_FILE . '.tmp';
    @file_put_contents($tmp, json_encode($map), LOCK_EX);
    @rename($tmp, CSRF_TOKEN_FILE);
}

/** Issue a new one-shot token (used by the GET endpoint below). */
function csrf_issue(): array {
    $token = bin2hex(random_bytes(32));
    $exp   = time() + CSRF_TTL_SEC;

    $fh = @fopen(CSRF_TOKEN_FILE, 'c+');
    if ($fh) {
        @flock($fh, LOCK_EX);
        $raw = stream_get_contents($fh);
        $map = $raw ? json_decode($raw, true) : [];
        if (!is_array($map)) $map = [];

        // Prune expired.
        $now = time();
        foreach ($map as $t => $e) {
            if (!is_int($e) || $e <= $now) unset($map[$t]);
        }
        // Sanity cap — drop oldest if we overflow.
        if (count($map) >= CSRF_MAX_LIVE) {
            asort($map);
            $map = array_slice($map, -CSRF_MAX_LIVE + 1, null, true);
        }

        $map[$token] = $exp;

        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($map));
        @flock($fh, LOCK_UN);
        @fclose($fh);
    } else {
        // Fail open: keep token in memory even if we couldn't persist; client
        // will fail the validate step gracefully and reload.
    }
    return ['token' => $token, 'expires_at' => $exp];
}

/**
 * Validate-and-consume. Returns true on success; the token is removed so it
 * can't be replayed.
 */
function csrf_validate(string $token): bool {
    if ($token === '' || strlen($token) !== 64 || !ctype_xdigit($token)) {
        return false;
    }

    $fh = @fopen(CSRF_TOKEN_FILE, 'c+');
    if (!$fh) return false;

    try {
        @flock($fh, LOCK_EX);
        $raw = stream_get_contents($fh);
        $map = $raw ? json_decode($raw, true) : [];
        if (!is_array($map)) $map = [];

        $now = time();
        $ok  = isset($map[$token]) && is_int($map[$token]) && $map[$token] > $now;
        if ($ok) {
            unset($map[$token]); // one-shot consumption
            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, json_encode($map));
        }
        return $ok;
    } finally {
        @flock($fh, LOCK_UN);
        @fclose($fh);
    }
}

// ── Issuer endpoint ──────────────────────────────────────────────────────
// Only runs when this file is hit directly via HTTP, not when require'd.
if (PHP_SAPI !== 'cli' && realpath(__FILE__) === realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? ''))) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    // Rate-limit token issuance per IP — keeps a noisy client from filling the token store.
    if (!rate_limit_check('csrf:' . rate_limit_client_ip(), 60, 3600)) {
        http_response_code(429);
        header('Retry-After: 60');
        echo json_encode(['ok' => false, 'error' => 'rate limited']);
        exit;
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'method not allowed']);
        exit;
    }

    $result = csrf_issue();
    echo json_encode(['ok' => true] + $result);
    exit;
}
