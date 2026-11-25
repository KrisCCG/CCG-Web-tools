<?php
/**
 * Finalise a Request — persist the draft to disk and email the team.
 *
 * Expected POST body (JSON):
 *   {
 *     conversation_token: string,
 *     history:            [ ... signed turns ... ],
 *     draft:              { ...submit_request schema... }
 *   }
 *
 * Response (JSON):
 *   { ok: true, request_id: "uuid" }
 *
 * Failure modes:
 *   401 invalid/expired token
 *   400 tampered history or invalid draft
 *   429 rate limit
 *   500 disk / mail error (the draft is still persisted to JSON — recoverable)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/lib/ratelimit.php';
require_once __DIR__ . '/lib/signing.php';
require_once __DIR__ . '/lib/audit.php';
require_once __DIR__ . '/lib/conversation.php';
require_once __DIR__ . '/lib/vies.php';

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
$draft   = $body['draft']   ?? null;

$client_ip = client_ip();

// ── Auth + integrity ─────────────────────────────────────────────────────

if (verify_conversation_token($token, $secrets['conversation_token_ttl']) === false) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Conversation expired.']);
    exit;
}

if (!is_array($history) || !verify_signed_history($history)) {
    audit_log('submit_tampered_history', ['token' => substr($token, 0, 16)]);
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Conversation state invalid.']);
    exit;
}

if (!ratelimit_hit('submit', $client_ip, $secrets['rate_limits']['submit'])) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Too many submissions — please try again tomorrow.']);
    exit;
}

// ── Validate draft ───────────────────────────────────────────────────────

$draft = validate_draft($draft);
if ($draft === null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Request data is incomplete or invalid.']);
    exit;
}

// ── VIES VAT check (best-effort, doesn't block submission) ─────────────

$vies = ['status' => 'not_provided'];
if (!empty($draft['contact']['vat'])) {
    $vies = check_vies($draft['contact']['vat']);
    audit_log('vies_check', [
        'status' => $vies['status'] ?? 'unknown',
        'has_name' => !empty($vies['name']),
    ]);
}

// ── Persist to JSON (source of truth even if mail fails) ────────────────

$request_id = strtolower(bin2hex(random_bytes(8)));
$month_dir  = __DIR__ . '/requests/' . date('Y-m');
if (!is_dir($month_dir) && !@mkdir($month_dir, 0755, true)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save your Request — please email us directly.']);
    exit;
}

$payload = [
    'request_id'   => $request_id,
    'submitted_at' => date('c'),
    'ip_hash'      => substr(hash('sha256', $client_ip), 0, 16),
    'draft'        => $draft,
    'vies'         => $vies,
    'transcript'   => array_map(fn($t) => ['role' => $t['role'], 'content' => $t['content']], $history),
];

$json_path = "{$month_dir}/{$request_id}.json";
if (@file_put_contents($json_path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save your Request — please email us directly.']);
    exit;
}

audit_log('request_submitted', ['request_id' => $request_id, 'lang' => $draft['detected_language'] ?? null]);

// ── Send email (best-effort; JSON is the fallback record) ───────────────

$email_sent = send_request_email($draft, $vies, $request_id, $history, $secrets);

echo json_encode([
    'ok'            => true,
    'request_id'    => $request_id,
    'email_sent'    => $email_sent,
]);

// ─────────────────────────────────────────────────────────────────────────

/**
 * Validate and normalise the draft. Returns the cleaned draft on success,
 * or null if required fields are missing / obviously bogus.
 */
function validate_draft(mixed $draft): ?array {
    if (!is_array($draft)) return null;

    $product = trim((string) ($draft['product'] ?? ''));
    if ($product === '' || mb_strlen($product) > 500) return null;

    $contact = $draft['contact'] ?? null;
    if (!is_array($contact)) return null;

    // Minimum to be able to answer: a real e-mail address, OR a name + a phone number.
    $email = trim((string) ($contact['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/@(example\.(com|org|net)|test\.|invalid|localhost)|^(test|unknown|noreply|no-reply|none|n-a)@/i', $email)) $email = '';

    $name = trim((string) ($contact['name'] ?? ''));
    if (mb_strlen($name) > 200) return null;
    $phone = trim((string) ($contact['phone'] ?? ''));
    $phone_ok = preg_match_all('/\d/', $phone) >= 8;
    if ($email === '' && !($name !== '' && $phone_ok)) return null;

    // Whitelist + clamp.
    return [
        'product'           => mb_substr($product, 0, 500),
        'quantity'          => mb_substr(trim((string) ($draft['quantity'] ?? '')), 0, 200),
        'size'              => mb_substr(trim((string) ($draft['size'] ?? '')), 0, 200),
        'sides'             => in_array($draft['sides'] ?? '', ['single', 'double', 'unknown'], true) ? $draft['sides'] : 'unknown',
        'paper_preference'  => mb_substr(trim((string) ($draft['paper_preference'] ?? '')), 0, 500),
        'finishings'        => array_values(array_filter(array_map(
            fn($f) => mb_substr(trim((string) $f), 0, 200),
            (array) ($draft['finishings'] ?? [])
        ))),
        'binding'           => mb_substr(trim((string) ($draft['binding'] ?? '')), 0, 200),
        'deadline'          => mb_substr(trim((string) ($draft['deadline'] ?? '')), 0, 200),
        'files_ready'       => !empty($draft['files_ready']),
        'file_link'         => validate_url(trim((string) ($draft['file_link'] ?? ''))),
        'file_notes'        => mb_substr(trim((string) ($draft['file_notes'] ?? '')), 0, 2000),
        'competitor_quote_info' => mb_substr(trim((string) ($draft['competitor_quote_info'] ?? '')), 0, 2000),
        'requested_visit_time'  => mb_substr(trim((string) ($draft['requested_visit_time']  ?? '')), 0, 500),
        'contact' => [
            'name'    => mb_substr($name, 0, 200),
            'email'   => $email,
            'phone'   => mb_substr($phone, 0, 100),
            'company' => mb_substr(trim((string) ($contact['company'] ?? '')), 0, 200),
            'vat'     => mb_substr(trim((string) ($contact['vat']     ?? '')), 0, 50),
            'address' => mb_substr(trim((string) ($contact['address'] ?? '')), 0, 500),
        ],
        'free_text'         => mb_substr(trim((string) ($draft['free_text'] ?? '')), 0, 4000),
        'detected_language' => in_array($draft['detected_language'] ?? '', ['fr', 'nl', 'en'], true) ? $draft['detected_language'] : 'en',
    ];
}

/**
 * Build and send the human-readable email to the team. Returns true if
 * mail() reports success, false otherwise. We don't fail the request on
 * mail failure — the JSON file on disk is the recoverable record.
 */
function send_request_email(array $draft, array $vies, string $request_id, array $history, array $secrets): bool {
    $lang_labels = ['fr' => 'French', 'nl' => 'Dutch', 'en' => 'English'];
    $lang_label  = $lang_labels[$draft['detected_language']] ?? 'Unknown';

    $subject = sprintf(
        '[Click Click] New Request — %s (%s)',
        $draft['product'],
        $draft['contact']['name'] !== '' ? $draft['contact']['name'] : ($draft['contact']['email'] ?: $draft['contact']['phone'])
    );

    $rows = [
        'Request ID'          => $request_id,
        'Conversation language'=> $lang_label,
        'Product'             => $draft['product'],
        'Quantity'            => $draft['quantity'],
        'Size'                => $draft['size'],
        'Sides'               => $draft['sides'],
        'Paper'               => $draft['paper_preference'],
        'Finishings'          => $draft['finishings'] ? implode(', ', $draft['finishings']) : '—',
        'Binding'             => $draft['binding'],
        'Needed by'           => $draft['deadline'],
        'File ready?'         => $draft['files_ready'] ? 'Yes' : 'No / unsure',
        'File link'           => $draft['file_link'],
        'File notes'          => $draft['file_notes'],
        'Competitor quote'    => $draft['competitor_quote_info'],
        'Shop visit requested'=> $draft['requested_visit_time'],
        'Other notes'         => $draft['free_text'],
        'Name'                => $draft['contact']['name'],
        'Email'               => $draft['contact']['email'],
        'Phone'               => $draft['contact']['phone'],
        'Address'             => $draft['contact']['address'],
        'Company'             => $draft['contact']['company'],
        'VAT'                 => $draft['contact']['vat'],
        'VAT check (VIES)'    => $draft['contact']['vat'] !== '' ? format_vies($vies) : '',
        'Registered name'     => ($vies['status'] ?? '') === 'valid' ? ($vies['name'] ?? '') : '',
        'Registered address'  => ($vies['status'] ?? '') === 'valid' ? format_vies_address($vies['address'] ?? '') : '',
    ];

    $html = '<!doctype html><html><body style="font-family: -apple-system, sans-serif; color: #222;">';
    $html .= '<h2 style="margin:0 0 8px;">New Request via the Click Click chatbot</h2>';
    $html .= '<p style="margin:0 0 16px;color:#666;">' . ($draft['contact']['email'] !== ''
        ? 'Reply directly to this email to reach the customer.'
        : '<strong>No e-mail address given — call the customer on ' . htmlspecialchars($draft['contact']['phone']) . '.</strong>') . '</p>';
    $html .= '<table style="border-collapse:collapse;width:100%;font-size:14px;">';
    foreach ($rows as $label => $value) {
        $row_style = '';
        if ($value === '') {
            $v = '<span style="color:#999;">—</span>';
        } elseif ($label === 'File link') {
            // Render as a clickable link.
            $safe = htmlspecialchars($value, ENT_QUOTES);
            $v = '<a href="' . $safe . '" style="color:#d23a1f;">' . $safe . '</a>';
        } else {
            $v = nl2br(htmlspecialchars($value));
        }
        // Highlight competitor-quote row so the sales team sees it instantly.
        if ($label === 'Competitor quote' && $value !== '') {
            $row_style = 'background:#fff7e0;';
            $v = '<strong style="color:#a06000;">⚑ ' . $v . '</strong>';
        }
        // Highlight shop-visit row — needs an action (confirm the slot).
        if ($label === 'Shop visit requested' && $value !== '') {
            $row_style = 'background:#e7f3ff;';
            $v = '<strong style="color:#0a4a8c;">📍 ' . $v . '</strong>';
        }
        $html .= '<tr style="' . $row_style . '">'
              . '<td style="padding:6px 12px 6px 0;color:#666;vertical-align:top;width:35%;">' . htmlspecialchars($label) . '</td>'
              . '<td style="padding:6px 0;">' . $v . '</td>'
              . '</tr>';
    }
    $html .= '</table>';

    $html .= '<h3 style="margin:24px 0 8px;">Full conversation</h3>';
    $html .= '<div style="border-left:3px solid #eee;padding-left:12px;font-size:13px;color:#444;">';
    foreach ($history as $turn) {
        $role = $turn['role'] === 'user' ? 'Customer' : 'Bot';
        $text = is_string($turn['content'])
            ? $turn['content']
            : implode("\n", array_map(
                fn($b) => ($b['type'] ?? '') === 'text' ? ($b['text'] ?? '') : '',
                $turn['content']
            ));
        $text = trim($text);
        if ($text === '') continue;
        $html .= '<p style="margin:0 0 12px;"><strong>' . $role . ':</strong><br>' . nl2br(htmlspecialchars($text)) . '</p>';
    }
    $html .= '</div></body></html>';

    // Send to the team, with Reply-To set to the customer so hitting Reply
    // in their mail client goes straight back to the customer.
    $headers  = "From: " . sprintf('%s <%s>', $secrets['from_name'], $secrets['from_email']) . "\r\n";
    if ($draft['contact']['email'] !== '') $headers .= "Reply-To: " . $draft['contact']['email'] . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    // Setting -f to align envelope-from with header From: helps SPF alignment
    // on Infomaniak. Without this, the envelope sender is the web server's
    // default (often something like web@h2web488...) which fails SPF for clickclick.be.
    $additional_params = '-f' . $secrets['from_email'];

    $sent = mail($secrets['notify_email'], $subject, $html, $headers, $additional_params);

    if (!$sent) {
        $err = error_get_last();
        error_log(sprintf(
            'submit.php: mail() failed for request %s — last error: %s',
            $request_id,
            $err['message'] ?? 'unknown'
        ));
        audit_log('mail_failed', ['request_id' => $request_id, 'last_error' => $err['message'] ?? null]);
    } else {
        audit_log('mail_sent', ['request_id' => $request_id]);
    }

    return $sent;
}

/**
 * Light URL validation for the customer-supplied file_link. Only http(s)
 * URLs are accepted; everything else becomes empty string.
 */
function validate_url(string $url): string {
    if ($url === '') return '';
    $u = filter_var($url, FILTER_VALIDATE_URL);
    if ($u === false) return '';
    $scheme = strtolower(parse_url($u, PHP_URL_SCHEME) ?? '');
    if (!in_array($scheme, ['http', 'https'], true)) return '';
    return mb_substr($u, 0, 2000);
}
