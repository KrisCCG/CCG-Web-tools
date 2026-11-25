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

// ── Single Anthropic call (no agentic loop — see chat.php) ─────────────

require_once __DIR__ . '/lib/shop.php';

$user_turn          = signed_turn('user', $message);
$anthropic_history  = [['role' => 'user', 'content' => $message]];
$updated_history    = [$user_turn];
$last_shop_quote    = null;

try {
    $page_lang  = in_array($body['page_lang'] ?? '', ['fr', 'nl', 'en'], true) ? $body['page_lang'] : null;
    $reply_lang = chat_reply_lang($page_lang, $message, !empty($body['lang_pinned']));
    $response   = anthropic_messages($anthropic_history, $reply_lang);
} catch (Throwable $e) {
    $is_timeout = str_starts_with($e->getMessage(), '[anthropic_timeout]');
    audit_log('anthropic_error', [
        'where'   => 'start',
        'msg'     => $e->getMessage(),
        'timeout' => $is_timeout,
    ]);
    http_response_code($is_timeout ? 504 : 502);
    echo json_encode([
        'ok'    => false,
        'error' => $is_timeout
            ? 'The assistant is taking longer than usual — please try again in a moment.'
            : 'Assistant temporarily unavailable.',
    ]);
    exit;
}

$assistant_content = extract_assistant_content($response);
$assistant_turn    = signed_turn('assistant', $assistant_content);
$updated_history[] = $assistant_turn;

// See chat.php for why we iterate ALL tool_use blocks (not just the first).
// In short: when the customer asks about two products at once, Haiku emits
// two check_shop_pricing tool_use blocks; every one needs a matching
// tool_result or Anthropic returns 400 on the next turn.
$shop_quotes  = [];
$submit_call  = null;
$tool_results = [];

foreach ($response['content'] ?? [] as $block) {
    if (($block['type'] ?? '') !== 'tool_use') continue;
    $tu_id   = $block['id']    ?? '';
    $tu_name = $block['name']  ?? '';
    $tu_in   = $block['input'] ?? [];

    if ($tu_name === 'check_shop_pricing') {
        $tu_in['lang'] = $reply_lang;          // the website decides the language, not the bot
        $quote         = shop_get_quote($tu_in);
        $shop_quotes[] = $quote;
        audit_log('shop_quote', [
            'ok'      => !empty($quote['ok']),
            'product' => $tu_in['product'] ?? null,
            'qty'     => $tu_in['qty']     ?? null,
            'inclVat' => $quote['inclVat'] ?? null,
            'error'   => $quote['error']   ?? null,
        ]);
        $tool_results[] = [
            'type'        => 'tool_result',
            'tool_use_id' => $tu_id,
            'content'     => json_encode($quote, JSON_UNESCAPED_UNICODE),
        ];
    } elseif ($tu_name === 'submit_request') {
        $submit_call = ['id' => $tu_id, 'input' => $tu_in];
    }
}

if (!empty($tool_results)) {
    $updated_history[] = signed_turn('user', $tool_results);
}

$conversation_token = issue_conversation_token();
audit_log('conversation_started', [
    'token'     => substr($conversation_token, 0, 16),
    'lang_hint' => $submit_call['input']['detected_language'] ?? null,
]);

echo json_encode([
    'ok'                 => true,
    'lang'               => $reply_lang,
    'conversation_token' => $conversation_token,
    'history'            => $updated_history,
    'assistant'          => extract_assistant_text($response),
    'ready_to_submit'    => $submit_call !== null,
    'draft'              => $submit_call['input'] ?? null,
    'shop_quotes'        => $shop_quotes,
    'shop_quote'         => $shop_quotes[0] ?? null,   // back-compat
]);
