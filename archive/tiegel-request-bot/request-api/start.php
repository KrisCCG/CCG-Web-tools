<?php
/**
 * Start a new conversation. Called when the customer sends their first message.
 *
 * Validates anti-abuse signals (honeypot, time-on-page, rate limit), then
 * issues a conversation_token, calls Anthropic with the first user message,
 * and returns the signed history + assistant reply.
 *
 * Expected POST body (JSON):
 *   {
 *     message: string,
 *     honeypot: string,           // must be empty
 *     page_loaded_at: number,     // ms timestamp from client
 *     turnstile_token?: string    // optional, ignored unless enabled
 *   }
 *
 * Response (JSON):
 *   {
 *     ok: true,
 *     conversation_token: string,
 *     history: [ { role, content, sig }, ... ],
 *     assistant: string,                  // text reply, may be empty if tool_use
 *     ready_to_submit: boolean,
 *     draft: object | null
 *   }
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/lib/ratelimit.php';
require_once __DIR__ . '/lib/signing.php';
require_once __DIR__ . '/lib/anthropic.php';
require_once __DIR__ . '/lib/audit.php';
require_once __DIR__ . '/lib/turnstile.php';
require_once __DIR__ . '/lib/conversation.php';

$secrets = require __DIR__ . '/secrets.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST required']);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

$message        = trim($body['message'] ?? '');
$honeypot       = (string) ($body['honeypot'] ?? '');
$page_loaded_at = (float)  ($body['page_loaded_at'] ?? 0);   // ms since epoch
$turnstile_tok  = (string) ($body['turnstile_token'] ?? '');

$client_ip = client_ip();

// ── Anti-abuse gates ──────────────────────────────────────────────────────

// 1. Honeypot — silently reject (return a 200 to not give away the trap).
if ($honeypot !== '') {
    audit_log('honeypot_filled', ['ip_hash' => substr(hash('sha256', $client_ip), 0, 16)]);
    echo json_encode(['ok' => false, 'error' => 'invalid request']);
    exit;
}

// 2. Time-on-page — first message before $min seconds = bot.
$now_ms = (int) (microtime(true) * 1000);
$min_ms = (int) (($secrets['min_time_on_page_seconds'] ?? 1.5) * 1000);
if ($page_loaded_at <= 0 || ($now_ms - $page_loaded_at) < $min_ms) {
    audit_log('too_fast', [
        'ip_hash' => substr(hash('sha256', $client_ip), 0, 16),
        'delta_ms' => $now_ms - $page_loaded_at,
    ]);
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Please take a moment before sending.']);
    exit;
}

// 3. Per-IP rate limit on new conversations.
if (!ratelimit_hit('start', $client_ip, $secrets['rate_limits']['start'])) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Too many new conversations from this address — please try again later.']);
    exit;
}

// 4. Optional Turnstile (stubbed off by default).
if (!verify_turnstile($turnstile_tok)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Verification failed.']);
    exit;
}

// 5. Message content guards.
if ($message === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Message is empty.']);
    exit;
}
if (mb_strlen($message) > 4000) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Message is too long.']);
    exit;
}

// ── Call Claude with the first user message ──────────────────────────────

$user_turn = signed_turn('user', $message);

try {
    $response = anthropic_messages([
        ['role' => 'user', 'content' => $message],
    ]);
} catch (Throwable $e) {
    audit_log('anthropic_error', ['where' => 'start', 'msg' => $e->getMessage()]);
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'Assistant temporarily unavailable.']);
    exit;
}

$assistant_content = extract_assistant_content($response);
$assistant_text    = extract_assistant_text($response);
$tool_call         = extract_tool_call($response);

$assistant_turn = signed_turn('assistant', $assistant_content);
$history        = [$user_turn, $assistant_turn];

$conversation_token = issue_conversation_token();

audit_log('conversation_started', [
    'token'    => substr($conversation_token, 0, 16),
    'lang_hint' => $tool_call['input']['detected_language'] ?? null,
]);

echo json_encode([
    'ok'                 => true,
    'conversation_token' => $conversation_token,
    'history'            => $history,
    'assistant'          => $assistant_text,
    'ready_to_submit'    => $tool_call !== null,
    'draft'              => $tool_call['input'] ?? null,
]);
