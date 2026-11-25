<?php
/**
 * Per-IP sliding-window rate limiter — flat-file, no DB required.
 *
 * Each bucket is a small JSON file under /.ratelimit/{bucket}-{ip_hash}.json
 * holding a list of recent timestamps. On every hit, expired entries are
 * pruned and the count is compared against the configured limit.
 *
 * The IP is hashed (not stored raw) to keep PII out of disk-resident logs.
 *
 * Usage:
 *   if (!ratelimit_hit('chat', $client_ip, $limits['chat'])) { 429... }
 *
 * where $limits['chat'] = [max_count, window_seconds].
 */

define('RATELIMIT_DIR', __DIR__ . '/../.ratelimit');

function ratelimit_hit(string $bucket, string $ip, array $config): bool {
    [$max, $window] = $config;

    // Bypass rate limits for trusted IPs (your own dev machine, teammates).
    // Configured in secrets.php as a plain array of IP strings.
    static $trusted = null;
    if ($trusted === null) {
        $secrets = require __DIR__ . '/../secrets.php';
        $trusted = is_array($secrets['trusted_ips'] ?? null) ? $secrets['trusted_ips'] : [];
    }
    if (in_array($ip, $trusted, true)) {
        return true;  // never blocked
    }

    if (!is_dir(RATELIMIT_DIR)) {
        @mkdir(RATELIMIT_DIR, 0755, true);
    }

    $ip_hash = substr(hash('sha256', $ip), 0, 16);
    $file    = RATELIMIT_DIR . "/{$bucket}-{$ip_hash}.json";
    $now     = time();
    $cutoff  = $now - $window;

    $fh = @fopen($file, 'c+');
    if (!$fh) return true;  // fail-open if filesystem is broken — better than locking customers out

    flock($fh, LOCK_EX);
    $raw = stream_get_contents($fh);
    $timestamps = $raw ? (json_decode($raw, true) ?: []) : [];

    // Prune expired entries.
    $timestamps = array_values(array_filter($timestamps, fn($t) => $t > $cutoff));

    if (count($timestamps) >= $max) {
        flock($fh, LOCK_UN);
        fclose($fh);
        return false;  // limit exceeded
    }

    $timestamps[] = $now;
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($timestamps));
    flock($fh, LOCK_UN);
    fclose($fh);
    return true;
}

/**
 * Resolve the client IP behind Infomaniak's reverse proxy.
 * Falls back to REMOTE_ADDR if no X-Forwarded-For is present.
 */
function client_ip(): string {
    $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ($xff !== '') {
        // First entry is the original client.
        $first = trim(explode(',', $xff)[0]);
        if (filter_var($first, FILTER_VALIDATE_IP)) return $first;
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
