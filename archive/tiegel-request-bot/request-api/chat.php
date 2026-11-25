<?php
/**
 * Continue an existing conversation.
 *
 * Expected POST body (JSON):
 *   {
 *     conversation_token: string,
 *     history:            [ { role, content, sig }, ... ],   // signed history
 *     message:            string
 *   }
 *
 * Response (JSON):
 *   {
 *     ok: true,
 *     history:         [ ...updated signed history... ],
 *     assistant:       string,
 *     ready_to_submit: boolean,
 *     draft:           object | null
 *   }
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/lib/ratelimit.php';
require_once __DIR__ . '/lib/signing.php';
require_once __DIR__ . '/lib/anthropic.php';
require_once __DIR__ . '/lib/audit.php';
require_once __DIR__ . '/lib/conversation.php';

$secrets = require __DIR__ . '/secrets.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST required']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

$token   = (string) ($body['conversation_token'] ?? '');
$history = $body['history'] ?? [];
$message = trim((string) ($body['message'] ?? ''));

$client_ip = client_ip();

// ── Token + history + rate-limit checks ──────────────────────────────────

$issued_at = verify_conversation_token($token, $secrets['conversation_token_ttl']);
if ($issued_at === false) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Conversation expired — please refresh and start again.']);
    exit;
}

if (!is_array($history) || !verify_signed_history($history)) {
    audit_log('tampered_history', ['token' => substr($token, 0, 16)]);
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Conversation state invalid.']);
    exit;
}

if (count_real_turns($history) >= $secrets['max_turns']) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'This conversation is long enough — please submit it now or start a fresh one.']);
    exit;
}

if (!ratelimit_hit('chat', $client_ip, $secrets['rate_limits']['chat'])) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Too many messages — please slow down or come back later.']);
    exit;
}

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

// ── Build Anthropic-shaped history, append new user message, call API ────

$user_turn          = signed_turn('user', $message);
$anthropic_history  = to_anthropic_history($history);
$anthropic_history[] = ['role' => 'user', 'content' => $message];

try {
    $response = anthropic_messages($anthropic_history);
} catch (Throwable $e) {
    audit_log('anthropic_error', ['where' => 'chat', 'msg' => $e->getMessage()]);
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'Assistant temporarily unavailable.']);
    exit;
}

$assistant_content = extract_assistant_content($response);
$assistant_text    = extract_assistant_text($response);
$tool_call         = extract_tool_call($response);

$assistant_turn = signed_turn('assistant', $assistant_content);
$updated_history = array_merge($history, [$user_turn, $assistant_turn]);

echo json_encode([
    'ok'              => true,
    'history'         => $updated_history,
    'assistant'       => $assistant_text,
    'ready_to_submit' => $tool_call !== null,
    'draft'           => $tool_call['input'] ?? null,
]);
