<?php
/**
 * Click Click — sliding-window IP+endpoint rate limiter (flat-file, no DB).
 *
 * Usage:
 *   require_once __DIR__ . '/lib/ratelimit.php';
 *   if (!rate_limit_check('upload:'.rate_limit_client_ip(), 30, 3600)) {
 *       http_response_code(429);
 *       header('Retry-After: 60');
 *       echo json_encode(['ok' => false, 'error' => 'rate limited']);
 *       exit;
 *   }
 *
 * State is stored as JSON arrays of timestamps under
 * /calculator/orders/.ratelimit/{md5(key)}.json. Old timestamps are pruned on
 * every read. The 1-byte-bucket-per-IP scheme caps disk usage well below 1 MB
 * even under sustained load.
 */

declare(strict_types=1);

const RATELIMIT_DIR = __DIR__ . '/../orders/.ratelimit/';

/**
 * Returns true if the request is within budget; false if it should be denied.
 * Caller is responsible for the 429 response when false.
 */
function rate_limit_check(string $key, int $max, int $window_sec): bool {
    if ($max < 1 || $window_sec < 1) return true;

    if (!is_dir(RATELIMIT_DIR)) {
        @mkdir(RATELIMIT_DIR, 0755, true);
        // Ensure the deny-all .htaccess inherited from /orders/ covers us, but
        // also drop one directly so the dir is self-contained if /orders/.htaccess
        // is ever moved.
        @file_put_contents(
            RATELIMIT_DIR . '.htaccess',
            "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n" .
            "<IfModule !mod_authz_core.c>\n  Order deny,allow\n  Deny from all\n</IfModule>\n" .
            "Options -Indexes\n"
        );
    }

    $path = RATELIMIT_DIR . md5($key) . '.json';
    $now  = time();
    $cutoff = $now - $window_sec;

    $fh = @fopen($path, 'c+');
    if (!$fh) return true; // fail open — don't block legitimate traffic on IO errors

    try {
        @flock($fh, LOCK_EX);
        $raw = stream_get_contents($fh);
        $hits = $raw ? json_decode($raw, true) : [];
        if (!is_array($hits)) $hits = [];

        // Drop anything outside the window.
        $hits = array_values(array_filter($hits, fn($t) => is_int($t) && $t >= $cutoff));

        if (count($hits) >= $max) {
            // Already at the limit — log via audit if available.
            if (function_exists('audit_log')) {
                audit_log('ratelimit.tripped', [
                    'key'    => preg_replace('/^([^:]+:).*/', '$1<ip>', $key),
                    'count'  => count($hits),
                    'max'    => $max,
                    'window' => $window_sec,
                ]);
            }
            return false;
        }

        $hits[] = $now;
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($hits));
        return true;
    } finally {
        @flock($fh, LOCK_UN);
        @fclose($fh);
    }
}

/**
 * Best-effort client IP. Matches audit.php's logic so the same IP appears in
 * rate-limit keys and audit entries.
 */
function rate_limit_client_ip(): string {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $first = trim(explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        if (filter_var($first, FILTER_VALIDATE_IP)) return $first;
    }
    return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

/**
 * Convenience wrapper: returns a 429 response and exits if over budget.
 * Accepts an optional Content-Type ('json' or 'text', default 'text').
 */
function rate_limit_or_die(string $key, int $max, int $window_sec, string $type = 'text'): void {
    if (rate_limit_check($key, $max, $window_sec)) return;
    http_response_code(429);
    header('Retry-After: 60');
    if ($type === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'rate limited']);
    } else {
        header('Content-Type: text/plain; charset=utf-8');
        echo "Too many requests. Please wait a minute and try again.";
    }
    exit;
}
