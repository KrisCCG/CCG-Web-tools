<?php
/**
 * Thin cURL wrapper around the Anthropic Messages API.
 *
 * No SDK, no Composer — same zero-dependency style as calculator/webhook.php.
 *
 * Key tricks:
 *   - The system prompt is sent as a content block with cache_control set,
 *     so Anthropic caches it (~90% cost reduction on repeat calls).
 *   - The submit_request tool schema is the bot's "form to fill out" — Claude
 *     calls it once it has enough info, and our chat.php detects that to
 *     signal ready_to_submit to the frontend.
 *
 * Returns the decoded JSON response on success, or throws on failure.
 */

require_once __DIR__ . '/audit.php';

const ANTHROPIC_API_URL = 'https://api.anthropic.com/v1/messages';
const ANTHROPIC_VERSION = '2023-06-01';

// Hard caps on the cURL call. CURLOPT_TIMEOUT must stay below PHP's
// max_execution_time so a stuck Anthropic call surfaces as our own
// "[anthropic_timeout]" RuntimeException (→ clean 504 to the client),
// not a fatal PHP timeout (→ blank page / 500). With PHP at 60s default,
// 25s gives us 35s headroom for the rest of the request (signing, audit
// log, response encode).
const ANTHROPIC_TIMEOUT_S         = 25;
const ANTHROPIC_CONNECT_TIMEOUT_S = 5;

/**
 * Add a prompt-caching breakpoint at the last assistant message in the
 * conversation. Subsequent calls within the cache TTL (~5 min) will read
 * everything up to and including that message from cache, dramatically
 * cutting input tokens billed AND request latency.
 *
 * Cache_control goes on the LAST content block of the chosen message.
 * If the content is a string, we promote it to a content-block array first.
 *
 * On the very first turn (no prior assistant messages) we just return the
 * messages unchanged — only the system + tools prefix gets cached.
 */
function annotate_cache_breakpoint(array $messages): array {
    // Find the last assistant message in the array.
    $lastAssistantIdx = -1;
    foreach ($messages as $i => $msg) {
        if (($msg['role'] ?? '') === 'assistant') $lastAssistantIdx = $i;
    }
    if ($lastAssistantIdx < 0) return $messages;

    $msg = $messages[$lastAssistantIdx];
    if (is_string($msg['content'])) {
        // Promote string content to a single text block with cache_control.
        $messages[$lastAssistantIdx]['content'] = [[
            'type'          => 'text',
            'text'          => $msg['content'],
            'cache_control' => ['type' => 'ephemeral'],
        ]];
    } elseif (is_array($msg['content']) && count($msg['content']) > 0) {
        // Attach cache_control to the last existing block.
        $lastBlockIdx = count($msg['content']) - 1;
        $messages[$lastAssistantIdx]['content'][$lastBlockIdx]['cache_control']
            = ['type' => 'ephemeral'];
    }
    return $messages;
}

/**
 * Tool schema Claude calls to check the online shop for an instant price
 * when the product + specs fit the shop catalog (flyer/card/poster on
 * Multiart/Biotop/Recystar in stocked gsm values).
 *
 * The server executes this tool (calls calc.php on clickclick.be), feeds
 * the result back to Claude as a tool_result, and Claude shapes its
 * follow-up reply around the returned price.
 */
function check_shop_pricing_tool(): array {
    require_once __DIR__ . '/shop.php';
    $catalogue = shop_catalogue_text();
    return [
        'name' => 'check_shop_pricing',
        'description' => "Get the instant online-shop price for one exact spec. The shop sells ONLY what is in this list (it comes live from the price file):\n"
            . $catalogue
            . "\n\nCall it as soon as product + paper + quantity are clear and they fit the list (size and sides may be defaults). Do NOT call for anything outside the list (other papers, custom finishes such as lamination, foil, embossing, die-cut, round corners, other sizes, brochures above the page or quantity limits, books, banners, stickers…) — continue the normal request flow instead.\n\nReturns the price incl. VAT and a deep link to the shop. The customer sees a price card with an 'Order online' button.",
        'input_schema' => [
            'type' => 'object',
            'required' => ['product', 'paper', 'size', 'qty', 'sides', 'express', 'delivery'],
            'properties' => [
                'product' => ['type' => 'string', 'enum' => ['flyer', 'card', 'poster', 'folder', 'brochure'],
                    'description' => 'flyer = light paper (115-170 g); card = sturdy paper (250 g and more), ANY size from BC to A4 — not only business cards; poster; folder = a folded leaflet (fold in half, roll fold, zigzag fold); brochure = a STAPLED booklet with pages (brochure, booklet, magazine, catalogue, programme booklet, livret, cahier, boekje).'],
                'paper' => ['type' => 'string', 'description' => 'Paper id exactly as in the list, e.g. "multiart-170", "biotop-300", "woodstock-285". For brochure: the INSIDE paper (the cover follows automatically).'],
                'size' => ['type' => 'string', 'description' => 'Size code from the list for this product (BC, A7, A6, A5, A4, A3, LP). For folder: the FLAT size (A4 or A5). For brochure: the CLOSED size (A4 or A5).'],
                'pages' => ['type' => 'integer', 'description' => 'Brochure only: total number of pages INCLUDING the cover, a multiple of 4 from the list (8-32).'],
                'fold' => ['type' => 'string', 'enum' => ['half', 'roll-6', 'zigzag-6'],
                    'description' => 'Folder only. half = folded in half (4 pages). roll-6 = roll fold / letter fold / wikkelvouw / rolvouw / pli roulé (3 panels, one panel tucked inside). zigzag-6 = zigzag / accordion / Z-fold / zigzagvouw / harmonicavouw / pli accordéon (3 panels). Roll fold is NOT a Z-fold.'],
                'qty' => ['type' => 'integer'],
                'sides' => ['type' => 'string', 'enum' => ['single', 'double']],
                'express' => ['type' => 'boolean', 'description' => 'true only if the customer signals urgency.'],
                'delivery' => ['type' => 'string', 'enum' => ['pickup', 'dpd'], 'description' => 'dpd only if the customer wants it delivered (Belgium, flat EUR 15).'],
                'lang' => ['type' => 'string', 'enum' => ['fr', 'nl', 'en'], 'description' => 'The REPLY LANGUAGE given in the instructions.'],
                'contact_name'    => ['type' => 'string', 'description' => 'Full name, if collected.'],
                'contact_email'   => ['type' => 'string', 'description' => 'Email, if collected.'],
                'contact_phone'   => ['type' => 'string', 'description' => 'Phone, if collected.'],
                'contact_company' => ['type' => 'string', 'description' => 'Company name, if a business.'],
                'contact_vat'     => ['type' => 'string', 'description' => 'VAT number, if a business.'],
                'contact_address' => ['type' => 'string', 'description' => 'Street + number, if given.'],
                'contact_zip'     => ['type' => 'string', 'description' => 'Postal code, if given.'],
                'contact_city'    => ['type' => 'string', 'description' => 'City, if given.'],
            ],
        ],
    ];
}

/**
 * Tool schema Claude is told to call when it has gathered enough info.
 * The fields here are what the human team needs to write a real quote.
 */
function submit_request_tool(): array {
    return [
        'name' => 'submit_request',
        'description' => 'Call this when you have collected all the information the customer wants to send. The customer will see a summary and confirm before it is actually sent.',
        'input_schema' => [
            'type' => 'object',
            'required' => ['product', 'quantity', 'contact', 'detected_language'],
            'properties' => [
                'product' => [
                    'type' => 'string',
                    'description' => 'What the customer wants printed (e.g. "business cards", "A5 flyers", "roll-up 85x200 cm").',
                ],
                'quantity' => [
                    'type' => 'string',
                    'description' => 'How many. Use the customer\'s own phrasing (e.g. "500", "1000 of each design", "I\'m not sure yet").',
                ],
                'size' => [
                    'type' => 'string',
                    'description' => 'Size or format (e.g. "A5", "85x55mm", "custom 120x180mm").',
                ],
                'sides' => [
                    'type' => 'string',
                    'enum' => ['single', 'double', 'unknown'],
                    'description' => 'Single- or double-sided printing.',
                ],
                'paper_preference' => [
                    'type' => 'string',
                    'description' => 'Paper type and weight, or "no preference, suggest one" if the customer wants advice.',
                ],
                'finishings' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Special finishes mentioned (e.g. "lamination matte", "spot varnish", "hot foil gold", "embossing", "custom die-cut"). Empty array if none.',
                ],
                'binding' => [
                    'type' => 'string',
                    'description' => 'For brochures ONLY: "saddle-stitched" (stapled, the only binding we offer). If the customer asked for something else themselves, write their words. Empty string for products that aren\'t bound.',
                ],
                'deadline' => [
                    'type' => 'string',
                    'description' => 'When the customer needs it ("needed by"), in their own words. Only a note for the team — never a promise, never comment on it.',
                ],
                'files_ready' => [
                    'type' => 'boolean',
                    'description' => 'True if the customer says they have a print-ready file. False otherwise.',
                ],
                'file_link' => [
                    'type' => 'string',
                    'description' => 'URL where the team can download the customer\'s file. Only populated if files_ready is true AND the customer provided a link (WeTransfer, SwissTransfer, Dropbox, Google Drive, OneDrive, etc.). Empty string otherwise.',
                ],
                'file_notes' => [
                    'type' => 'string',
                    'description' => 'Anything noteworthy about the file: format, ICC profile mentioned, custom cut paths, the customer asking for help preparing the file, etc.',
                ],
                'contact' => [
                    'type' => 'object',
                    'properties' => [
                        'name'    => ['type' => 'string', 'description' => 'Full name, exactly as the customer gave it. Never invent one.'],
                        'email'   => ['type' => 'string', 'description' => 'E-mail exactly as the customer typed it. Empty string if not given. NEVER invent or guess one.'],
                        'phone'   => ['type' => 'string', 'description' => 'Phone number exactly as given. Empty string if not given. Required (with the name) when there is no e-mail.'],
                        'company' => ['type' => 'string', 'description' => 'Company name, or "private individual".'],
                        'vat'     => ['type' => 'string', 'description' => 'VAT number, asked only when the customer represents a company. Belgian format is BE followed by 10 digits (e.g. BE0123456789); accept the customer\'s own formatting. Empty string for private individuals.'],
                        'address' => ['type' => 'string', 'description' => 'Postal/delivery address (street, postcode, city, country). Capture whatever the customer offers — full address if they share it spontaneously, partial otherwise. Empty string if not provided.'],
                    ],
                ],
                'competitor_quote_info' => [
                    'type' => 'string',
                    'description' => 'If the customer indicated they have another quote or are shopping around, capture whatever they shared: the competing price, the competitor\'s name, a link to a PDF/screenshot, or just context like "shopping around — declined to share". Empty string if no competitor quote was mentioned.',
                ],
                'requested_visit_time' => [
                    'type' => 'string',
                    'description' => 'If the customer asked to visit the shop in person, capture the date/time they proposed (e.g. "Tuesday March 12 at 14:00", "Wednesday morning around 10"). Available slots are weekdays 9:00-12:30 and 13:30-18:00. Empty string if no visit was requested.',
                ],
                'free_text' => [
                    'type' => 'string',
                    'description' => 'Anything else the customer mentioned that doesn\'t fit in the structured fields. Include their own words verbatim where useful.',
                ],
                'detected_language' => [
                    'type' => 'string',
                    'enum' => ['fr', 'nl', 'en'],
                    'description' => 'The language the conversation took place in.',
                ],
            ],
        ],
    ];
}

/**
 * Call the Messages API.
 *
 * @param array $messages  Array of {role, content} turns. Content can be a
 *                         string or an array of content blocks (for tool_use).
 * @return array Decoded JSON response.
 * @throws RuntimeException on HTTP errors.
 */
function anthropic_messages(array $messages, ?string $reply_lang = null): array {
    $secrets = require __DIR__ . '/../secrets.php';
    $api_key = $secrets['anthropic_api_key'] ?? '';
    if ($api_key === '' || str_contains($api_key, 'REPLACE')) {
        throw new RuntimeException('Anthropic API key not configured');
    }

    $system_prompt = require __DIR__ . '/../system_prompt.php';

    // ── Build a cacheable tools array. We attach cache_control to the LAST
    // tool — that caches everything that comes before it in the request
    // (system prompt + all tools). Tools never change between calls, so
    // we get a near-100% cache hit on this portion.
    $tools = [check_shop_pricing_tool(), submit_request_tool()];
    $tools[count($tools) - 1]['cache_control'] = ['type' => 'ephemeral'];

    // ── Add a cache breakpoint at the LAST assistant message in the history.
    // Everything up to and including that message can be read from cache on
    // subsequent calls. The new user message + any new content stays fresh.
    $messages = annotate_cache_breakpoint($messages);

    $body = [
        'model'      => $secrets['model'] ?? 'claude-haiku-4-5-20251001',
        'max_tokens' => $secrets['max_tokens'] ?? 1024,
        'system'     => [
            [
                'type'          => 'text',
                'text'          => $system_prompt,
                'cache_control' => ['type' => 'ephemeral'],
            ],
        ],
        'tools'    => $tools,
        'messages' => $messages,
    ];

    // The reply language is decided by the website (see chat_reply_lang() in shop.php),
    // not guessed by the bot. Kept outside the cached prompt block.
    $names = ['fr' => 'French', 'nl' => 'Dutch', 'en' => 'English'];
    if ($reply_lang !== null && isset($names[$reply_lang])) {
        $body['system'][] = [
            'type' => 'text',
            'text' => 'REPLY LANGUAGE: ' . $reply_lang . ' (' . $names[$reply_lang] . '). Write your whole reply in ' . $names[$reply_lang]
                . ', whatever language earlier messages were in, and pass lang="' . $reply_lang . '" in tool calls.',
        ];
    }
    // Today's date + ready dates (Brussels), so the bot never has to ask which day "Friday" is.
    // Outside the cached prompt block (it changes every minute).
    if (function_exists('chat_today_text')) {
        $body['system'][] = ['type' => 'text', 'text' => chat_today_text()];
    }
    // Short checklist as the very last system text (Kris, 17 Oct: Haiku asked sides, paper weight,
    // finish and the date before pricing although the prompt says price first — the end of the
    // prompt gets the most attention). Static text, a few dozen tokens.
    $body['system'][] = ['type' => 'text', 'text' =>
        'BEFORE YOU REPLY, check: (1) Does the customer name a product the shop sells and a quantity? '
        . 'Then call check_shop_pricing in THIS reply, with the defaults for anything missing — the only '
        . 'question allowed before a first price is the page count of a brochure. (2) Before the first price '
        . 'card and in the reply that shows it: no question about sides, paper weight, deadline or contact '
        . 'details. (3) Never ask about or offer a finish (varnish, lamination …) — the shop has none. '
        . '(4) Quoting express? Say in the same reply that express is pickup only — no time for shipping. '
        . '(5) Every date comes from the CALENDAR above, never computed. (6) Plain text: no markdown, no bold, '
        . 'no numbered lists. (7) Not a shop product? No price, no production time, no ready or delivery date, '
        . 'no express — only that the team replies within 1–2 business days; note their date as "needed by".'];

    $json_body = json_encode($body, JSON_UNESCAPED_UNICODE);

    // Visibility on context bloat. Anthropic's hard cap is ~200k tokens,
    // and a single intake conversation should stay far below that — but
    // tool_result blocks accumulate over turns and are the most likely
    // source of unexpected growth. Logging the assembled-body size +
    // tool_result count lets us spot a runaway conversation before it
    // hits the model's context window.
    $tool_result_count = 0;
    foreach ($messages as $m) {
        if (is_array($m['content'] ?? null)) {
            foreach ($m['content'] as $b) {
                if (($b['type'] ?? '') === 'tool_result') $tool_result_count++;
            }
        }
    }
    audit_log('anthropic_request', [
        'bytes'        => strlen($json_body),
        'msg_count'    => count($messages),
        'tool_results' => $tool_result_count,
        'model'        => $body['model'],
    ]);

    $ch = curl_init(ANTHROPIC_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $json_body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => ANTHROPIC_CONNECT_TIMEOUT_S,
        CURLOPT_TIMEOUT        => ANTHROPIC_TIMEOUT_S,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $api_key,
            'anthropic-version: ' . ANTHROPIC_VERSION,
        ],
    ]);

    $raw   = curl_exec($ch);
    $http  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err   = curl_error($ch);
    $errno = curl_errno($ch);
    curl_close($ch);

    if ($raw === false) {
        // Tag timeouts so chat.php / start.php can return a clean 504 with a
        // specific message, instead of a generic 502 transport error.
        if ($errno === CURLE_OPERATION_TIMEDOUT) {
            throw new RuntimeException("[anthropic_timeout] {$err}");
        }
        throw new RuntimeException("Anthropic transport error: {$err}");
    }
    $decoded = json_decode($raw, true);
    if ($http !== 200) {
        $msg = $decoded['error']['message'] ?? $raw;
        throw new RuntimeException("Anthropic HTTP {$http}: {$msg}");
    }

    return $decoded;
}

/**
 * Extract the human-readable assistant reply text from a Messages API response.
 * Concatenates all text blocks; ignores tool_use blocks (those are surfaced
 * separately via extract_tool_call).
 */
function extract_assistant_text(array $response): string {
    $out = '';
    foreach ($response['content'] ?? [] as $block) {
        if (($block['type'] ?? '') === 'text') {
            $out .= $block['text'];
        }
    }
    return trim($out);
}

/**
 * If the response contains a tool_use block for submit_request, return its
 * input (the draft Request data). Otherwise return null.
 */
function extract_tool_call(array $response, string $tool_name = 'submit_request'): ?array {
    foreach ($response['content'] ?? [] as $block) {
        if (($block['type'] ?? '') === 'tool_use' && ($block['name'] ?? '') === $tool_name) {
            return [
                'id'    => $block['id'] ?? '',
                'input' => $block['input'] ?? [],
            ];
        }
    }
    return null;
}

/**
 * Return the full assistant content array — used so the next request can
 * faithfully replay the assistant turn (including tool_use blocks if any).
 */
function extract_assistant_content(array $response): array {
    return $response['content'] ?? [];
}
