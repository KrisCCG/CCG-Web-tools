<?php
// ── Anonymous calculator usage logging ───────────────────────────────────
// Receives calculator state via POST and appends to a JSONL log file.
// No personal data — just specs and prices for analytics.

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('POST only'); }

define('LOG_FILE', __DIR__ . '/orders/calc_log.jsonl');
define('RATE_FILE', __DIR__ . '/orders/calc_rate.json');
define('RATE_LIMIT_SEC', 10);

// ── Ensure directory exists ──────────────────────────────────────────────
$dir = dirname(LOG_FILE);
if (!is_dir($dir)) mkdir($dir, 0755, true);

// ── Simple IP rate limiting ──────────────────────────────────────────────
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rates = file_exists(RATE_FILE) ? json_decode(file_get_contents(RATE_FILE), true) : [];
$now = time();

// Clean old entries (older than 60s)
$rates = array_filter($rates, fn($t) => $now - $t < 60);

if (isset($rates[$ip]) && ($now - $rates[$ip]) < RATE_LIMIT_SEC) {
    http_response_code(429);
    exit('Rate limited');
}
$rates[$ip] = $now;
file_put_contents(RATE_FILE, json_encode($rates));

// ── Parse input ──────────────────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['price'])) {
    http_response_code(400);
    exit('Invalid data');
}

// Only keep safe, expected fields
$allowed = ['tab', 'paper', 'weight', 'size', 'quantity', 'sides', 'versions',
            'express', 'price', 'priceExVat'];
$entry = ['timestamp' => date('c')];
foreach ($allowed as $key) {
    if (isset($input[$key])) {
        $entry[$key] = is_string($input[$key])
            ? htmlspecialchars(strip_tags(trim($input[$key])), ENT_QUOTES, 'UTF-8')
            : $input[$key];
    }
}

// ── Append to log ────────────────────────────────────────────────────────
file_put_contents(LOG_FILE, json_encode($entry) . "\n", FILE_APPEND | LOCK_EX);

http_response_code(200);
echo json_encode(['ok' => true]);
