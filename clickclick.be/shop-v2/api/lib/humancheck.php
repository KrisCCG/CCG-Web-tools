<?php
/**
 * Shop — "are you a client?" check for calc.php.
 *
 * A visitor gets CALC_FREE_REQUESTS price calculations per hour. After that calc.php answers 403
 * {error:'human_check'} and the shop shows a tick box. Ticking it (calc.php?human=1) sets a signed
 * cookie for CALC_HUMAN_TTL seconds; from then on the visitor has a generous budget (CALC_HUMAN_MAX per
 * CALC_HUMAN_WINDOW seconds) instead. A plain script that never ticks stops after 5 calculations.
 * The tick must come 2 s or more after the challenge was issued (server-signed timestamp).
 * Storage: a random key in orders/.ratelimit/human.key + the existing flat-file rate limiter.
 */

declare(strict_types=1);

require_once __DIR__ . '/ratelimit.php';

const CALC_FREE_REQUESTS = 5;
const CALC_FREE_WINDOW   = 3600;
const CALC_HUMAN_MAX     = 120;
const CALC_HUMAN_WINDOW  = 600;
const CALC_HUMAN_TTL     = 7200;
const CALC_HUMAN_COOKIE  = 'ccp_human';
const CALC_HUMAN_MIN_MS  = 2000;

function hc_key(): string
{
    $f = RATELIMIT_DIR . 'human.key';
    if (!is_file($f)) {
        if (!is_dir(RATELIMIT_DIR)) { @mkdir(RATELIMIT_DIR, 0755, true); }
        @file_put_contents($f, bin2hex(random_bytes(32)), LOCK_EX);
    }
    $k = (string)@file_get_contents($f);
    return $k !== '' ? $k : 'fallback-' . ($_SERVER['SERVER_NAME'] ?? '');
}

function hc_sign(string $v): string
{
    return hash_hmac('sha256', $v . '|' . rate_limit_client_ip(), hc_key());
}

function hc_is_verified(): bool
{
    $c = (string)($_COOKIE[CALC_HUMAN_COOKIE] ?? '');
    if (!preg_match('/^(\d{9,11})\.([a-f0-9]{64})$/', $c, $m)) return false;
    return (int)$m[1] > time() && hash_equals(hc_sign('ok' . $m[1]), $m[2]);
}

/** Challenge token handed to the browser with the 403 (timestamp + signature). */
function hc_challenge(): string
{
    $ts = (string)(int)(microtime(true) * 1000);
    return $ts . '.' . hc_sign('ch' . $ts);
}

/** Called for every price calculation. Exits with 403/429 when the visitor must tick or wait. */
function hc_gate(): void
{
    $ip = rate_limit_client_ip();
    if (hc_is_verified()) {
        if (!rate_limit_check('calchuman:' . $ip, CALC_HUMAN_MAX, CALC_HUMAN_WINDOW)) {
            http_response_code(429);
            header('Retry-After: 60');
            echo json_encode(['ok' => false, 'error' => 'rate limited']);
            exit;
        }
        return;
    }
    if (!rate_limit_check('calcfree:' . $ip, CALC_FREE_REQUESTS, CALC_FREE_WINDOW)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'human_check', 'human' => true, 'challenge' => hc_challenge()]);
        exit;
    }
}

/** POST calc.php?human=1 {tick:true, challenge:"ts.sig"} → sets the cookie. */
function hc_verify_request(): void
{
    $ip = rate_limit_client_ip();
    $src = json_decode((string)file_get_contents('php://input'), true);
    $ok = false;
    if (is_array($src) && ($src['tick'] ?? false) === true
        && preg_match('/^(\d{12,14})\.([a-f0-9]{64})$/', (string)($src['challenge'] ?? ''), $m)
        && hash_equals(hc_sign('ch' . $m[1]), $m[2])) {
        $age = (int)(microtime(true) * 1000) - (int)$m[1];
        $ok = $age >= CALC_HUMAN_MIN_MS && $age <= 1800000;
    }
    if (!rate_limit_check('calcverify:' . $ip, 10, 3600)) { http_response_code(429); echo json_encode(['ok' => false, 'error' => 'rate limited']); exit; }
    if (!$ok) { http_response_code(400); echo json_encode(['ok' => false, 'error' => 'check failed', 'challenge' => hc_challenge()]); exit; }
    $exp = time() + CALC_HUMAN_TTL;
    setcookie(CALC_HUMAN_COOKIE, $exp . '.' . hc_sign('ok' . $exp), [
        'expires' => $exp, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax',
    ]);
    echo json_encode(['ok' => true]);
    exit;
}
