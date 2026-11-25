<?php
/**
 * Helpers for translating between two views of the conversation:
 *
 *   - "Signed history" (what the client sees and sends back to us):
 *     each turn is { role, content, sig } where sig was produced by
 *     sign_turn() at the time the server first saw the turn.
 *
 *   - "Anthropic history" (what the Messages API expects):
 *     each turn is { role, content } with no sig.
 *
 * Additionally, the Anthropic API requires every assistant turn that
 * contains a tool_use block to be followed by a user turn with a
 * matching tool_result block. If the conversation continues past a
 * submit_request tool call (e.g. the customer wants to amend something
 * after seeing the summary), we synthesise a benign tool_result so the
 * API is happy.
 */

require_once __DIR__ . '/signing.php';

/**
 * Verify every turn in the client-supplied signed history. Returns true
 * if all sigs are valid (or the history is empty), false on the first
 * tampered or missing signature.
 */
function verify_signed_history(array $signed_history): bool {
    foreach ($signed_history as $turn) {
        $role    = $turn['role']    ?? '';
        $content = $turn['content'] ?? '';
        $sig     = $turn['sig']     ?? '';
        if ($role === '' || $sig === '') return false;
        if (!verify_turn($role, $content, $sig)) return false;
    }
    return true;
}

/**
 * Convert signed history -> Anthropic-friendly history.
 * Strips sigs, and injects a synthetic tool_result turn after any
 * assistant tool_use that isn't already followed by one.
 */
function to_anthropic_history(array $signed_history): array {
    $out = [];
    foreach ($signed_history as $i => $turn) {
        $out[] = [
            'role'    => $turn['role'],
            'content' => $turn['content'],
        ];

        // If this is an assistant turn containing a tool_use, ensure the
        // next turn is a user message with a tool_result for the same id.
        if ($turn['role'] !== 'assistant' || !is_array($turn['content'])) continue;
        $tool_use_id = null;
        foreach ($turn['content'] as $block) {
            if (($block['type'] ?? '') === 'tool_use') {
                $tool_use_id = $block['id'] ?? null;
                break;
            }
        }
        if ($tool_use_id === null) continue;

        $next = $signed_history[$i + 1] ?? null;
        $has_result = false;
        if ($next && $next['role'] === 'user' && is_array($next['content'])) {
            foreach ($next['content'] as $block) {
                if (($block['type'] ?? '') === 'tool_result'
                    && ($block['tool_use_id'] ?? '') === $tool_use_id) {
                    $has_result = true;
                    break;
                }
            }
        }
        if (!$has_result) {
            // Inject a synthetic tool_result acknowledging the draft. The text
            // deliberately pushes Claude to re-call submit_request on the next
            // turn — otherwise Claude tends to just chat ("ok, sending") without
            // calling the tool, and the SEND button never appears for the customer.
            $out[] = [
                'role'    => 'user',
                'content' => [
                    [
                        'type'        => 'tool_result',
                        'tool_use_id' => $tool_use_id,
                        'content'     => 'Draft shown to the customer. They have NOT clicked the SEND button yet — submission only happens when they click it. On your NEXT response: if the customer confirms ("ok", "yes", "send it", "go ahead", "perfect", etc.) OR provides corrections, you MUST call submit_request again with the appropriate values. Plain text like "one moment" or "sending now" does nothing — the tool call is the only way the SEND button appears.',
                    ],
                ],
            ];
        }
    }
    return $out;
}

/**
 * Sign a turn and produce the { role, content, sig } shape the client sees.
 */
function signed_turn(string $role, mixed $content): array {
    return [
        'role'    => $role,
        'content' => $content,
        'sig'     => sign_turn($role, $content),
    ];
}

/**
 * Count "real" turns (assistant + non-tool-result user). Used to enforce
 * the max_turns cap without counting synthetic glue.
 */
function count_real_turns(array $signed_history): int {
    $n = 0;
    foreach ($signed_history as $turn) {
        if (is_array($turn['content'] ?? null)) {
            // Skip pure tool_result turns.
            $only_tool_result = true;
            foreach ($turn['content'] as $block) {
                if (($block['type'] ?? '') !== 'tool_result') { $only_tool_result = false; break; }
            }
            if ($only_tool_result) continue;
        }
        $n++;
    }
    return $n;
}
