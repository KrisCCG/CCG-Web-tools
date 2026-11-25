<?php
/**
 * Continue an existing conversation.
 *
 * Now supports an "agentic" tool loop: when the bot calls
 * check_shop_pricing, the server executes it (cURL to calc.php on
 * clickclick.be), feeds the result back to Anthropic as a tool_result,
 * and asks for a follow-up reply. submit_request is left for the
 * frontend to handle (server doesn't execute it).
 *
 * Expected POST body (JSON):
 *   {
 *     conversation_token: string,
 *     history:            [ { role, content, sig }, ... ],
 *     message:            string
 *   }
 *
 * Response (JSON):
 *   {
 *     ok:              true,
 *     history:         [...updated signed history...],
 *     assistant:       string,                  // final text reply
 *     ready_to_submit: boolean,
 *     draft:           object | null,
 *     shop_quote:      object | null            // latest shop quote, if any
 *   }
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/lib/ratelimit.php';
require_once __DIR__ . '/lib/signing.php';
require_once __DIR__ . '/lib/anthropic.php';
require_once __DIR__ . '/lib/audit.php';
require_once __DIR__ . '/lib/conversation.php';
require_once __DIR__ . '/lib/shop.php';

$secrets = require __DIR__ . '/secrets.php';

const TOOL_LOOP_MAX = 3;   // safety cap on agentic iterations

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

// ── Build initial Anthropic-shaped history + append the new user message ─

$user_turn          = signed_turn('user', $message);
$anthropic_history  = to_anthropic_history($history);
$anthropic_history[] = ['role' => 'user', 'content' => $message];

$updated_history    = array_merge($history, [$user_turn]);
$last_shop_quote    = null;

// ── Single Anthropic call ───────────────────────────────────────────────
// The bot generates a lead-in text + optionally a tool_use block (either
// check_shop_pricing or submit_request). If it's check_shop_pricing, we
// execute it server-side and return BOTH the lead-in text AND the shop
// quote — the frontend renders the bot's text then the shop card. We do
// NOT do a second Anthropic call to ask the bot to "say something about
// the price" — that doubled latency for no real UX gain.
try {
    $page_lang  = in_array($body['page_lang'] ?? '', ['fr', 'nl', 'en'], true) ? $body['page_lang'] : null;
    $reply_lang = chat_reply_lang($page_lang, $message, !empty($body['lang_pinned']));
    $response   = anthropic_messages($anthropic_history, $reply_lang);
} catch (Throwable $e) {
    $is_timeout = str_starts_with($e->getMessage(), '[anthropic_timeout]');
    audit_log('anthropic_error', [
        'where'   => 'chat',
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

// Iterate over ALL tool_use blocks in the assistant response, not just the
// first. When the customer asks about two products at once ("flyers and
// posters"), Haiku tends to emit two check_shop_pricing tool_use blocks in
// a single reply. We need to execute every one of them AND append a
// matching tool_result for each in a single user-role message — otherwise
// Anthropic returns HTTP 400 on the next turn ("tool_use must be followed
// by tool_result") and the customer sees "Assistant temporarily unavailable".
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
        // submit_request is handled by the frontend (it shows the summary
        // and the customer clicks SEND). We don't append a synthetic
        // tool_result here — to_anthropic_history() injects one on the
        // next turn if needed, with the wording that nudges Claude to
        // re-call submit_request after the customer's reply.
        $submit_call = ['id' => $tu_id, 'input' => $tu_in];
    }
}

if (!empty($tool_results)) {
    $updated_history[] = signed_turn('user', $tool_results);
}

echo json_encode([
    'ok'              => true,
    'history'         => $updated_history,
    'assistant'       => extract_assistant_text($response),
    'ready_to_submit' => $submit_call !== null,
    'draft'           => $submit_call['input'] ?? null,
    'shop_quotes'     => $shop_quotes,
    'lang'            => $reply_lang,
    'shop_quote'      => $shop_quotes[0] ?? null,   // back-compat for older clients
]);
