<?php
/**
 * Secrets template — copy to secrets.php and fill in real values.
 *
 * secrets.php is gitignored and .htaccess-denied. NEVER commit real keys.
 *
 * To rotate the signing key, generate a new one with:
 *   php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
 * Rotating it invalidates all in-flight conversations (acceptable).
 */

return [
    // Anthropic Claude API key — get from https://console.anthropic.com/
    // Format: sk-ant-api03-...
    'anthropic_api_key' => 'sk-ant-api03-REPLACE-ME',

    // HMAC signing key for conversation tokens + history turn signatures.
    // 64 hex chars (32 bytes). Generate with:
    //   php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
    'signing_key' => 'REPLACE_ME_WITH_64_HEX_CHARS',

    // Cloudflare Turnstile secret — leave blank unless you flip 'turnstile_enabled' to true.
    // Get from https://dash.cloudflare.com/?to=/:account/turnstile
    'turnstile_secret' => '',
    'turnstile_enabled' => false,

    // Where finished Requests get emailed.
    'notify_email' => 'info@clickclickgraphics.com',

    // From: address used by mail(). What matters for SPF is the DOMAIN IN THIS
    // ADDRESS — not the host the code runs on. submit.php aligns envelope-from to
    // it via -f, so SPF is evaluated against this domain and Infomaniak's IP.
    // Moving the code between domains therefore does NOT affect deliverability.
    //
    // Verified 17 Aug 2026:
    //   clickclickgraphics.com   v=spf1 include:spf.infomaniak.ch ~all   ← use this
    //   clickclick.be            v=spf1 -all   (publishes "sends no mail")
    //   tiegel.be                v=spf1 -all   (same)
    //
    // Keep this on clickclickgraphics.com: it is the only one of the three that
    // authorises Infomaniak, and it outlives both of the others. Sending from
    // clickclick.be would hard-fail SPF and the notifications would be rejected.
    'from_email' => 'noreply@clickclickgraphics.com',
    'from_name'  => 'Click Click Chatbot',

    // (Reply-To in outgoing emails is set automatically to the customer's
    //  address so hitting Reply in your mail client goes back to them.)

    // Anthropic model + token budget per call.
    // Haiku 4.5 is the default; switch to Sonnet 4.5 only if quality demands it.
    'model'      => 'claude-haiku-4-5-20251001',
    'max_tokens' => 1024,

    // Conversation limits.
    'max_turns'                 => 25,    // hard cap on assistant+user turns
    'conversation_token_ttl'    => 3600,  // seconds — 1 hour

    // Anti-abuse — first-message timing check (seconds since page load).
    'min_time_on_page_seconds'  => 1.5,

    // Per-IP rate limits — keys are bucket names, values are [count, window_seconds].
    'rate_limits' => [
        'start'  => [5,  3600],   // 5 new conversations per hour
        'chat'   => [30, 3600],   // 30 chat messages per hour
        'submit' => [3,  86400],  // 3 successful submits per day
    ],

    // IPs that bypass rate limits entirely — for dev testing and trusted teammates.
    // Get yours from https://api.ipify.org. Add as plain strings.
    // Empty array = nobody is whitelisted = all customers get the limits above.
    'trusted_ips' => [
        // '78.21.45.67',
    ],
];
