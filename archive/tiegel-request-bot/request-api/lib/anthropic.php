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

const ANTHROPIC_API_URL = 'https://api.anthropic.com/v1/messages';
const ANTHROPIC_VERSION = '2023-06-01';

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
                    'description' => 'What the customer wants printed (e.g. "business cards", "A5 flyers", "64-page perfect-bound book").',
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
                    'description' => 'For brochures and books ONLY. Combined binding + cover description. Examples: "saddle-stitched", "perfect-bound, soft cover", "perfect-bound, hard cover", "thread-sewn, soft cover", "thread-sewn, hard cover". Empty string for products that aren\'t bound (flyers, business cards, stickers, etc.).',
                ],
                'deadline' => [
                    'type' => 'string',
                    'description' => 'When they need it by. Use their own phrasing.',
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
                    'required' => ['name', 'email'],
                    'properties' => [
                        'name'    => ['type' => 'string'],
                        'email'   => ['type' => 'string'],
                        'phone'   => ['type' => 'string'],
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
                    'description' => 'If the customer asked to visit the shop in person, capture the date/time they proposed (e.g. "Tuesday March 12 at 14:00", "Wednesday morning around 10"). Available slots are weekdays 9:30-12:30 and 13:30-18:00. Empty string if no visit was requested.',
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
function anthropic_messages(array $messages): array {
    $secrets = require __DIR__ . '/../secrets.php';
    $api_key = $secrets['anthropic_api_key'] ?? '';
    if ($api_key === '' || str_contains($api_key, 'REPLACE')) {
        throw new RuntimeException('Anthropic API key not configured');
    }

    $system_prompt = require __DIR__ . '/../system_prompt.php';

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
        'tools'    => [submit_request_tool()],
        'messages' => $messages,
    ];

    $ch = curl_init(ANTHROPIC_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $api_key,
            'anthropic-version: ' . ANTHROPIC_VERSION,
        ],
    ]);

    $raw = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
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
