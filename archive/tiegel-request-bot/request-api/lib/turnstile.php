<?php
/**
 * Cloudflare Turnstile verifier — STUBBED by default.
 *
 * Per the plan, we ship with NO captcha to minimise friction. This file is
 * a flag-gated stub so we can switch on invisible Turnstile later without
 * a redeploy — flip 'turnstile_enabled' in secrets.php and add the secret.
 *
 * Returns true if (a) Turnstile is disabled, or (b) Cloudflare confirms the
 * token. Returns false only on explicit verification failure.
 *
 * To activate:
 *   1. Sign up at https://dash.cloudflare.com/?to=/:account/turnstile
 *   2. Create a widget in "invisible" mode for tiegel.be
 *   3. Add the site key to index.html
 *   4. Set turnstile_secret + turnstile_enabled=true in secrets.php
 *   5. Reload — done. Customers see nothing unless flagged as suspicious.
 */

function verify_turnstile(string $token): bool {
    $secrets = require __DIR__ . '/../secrets.php';
    if (empty($secrets['turnstile_enabled'])) {
        return true;  // disabled — pass through
    }

    $secret = $secrets['turnstile_secret'] ?? '';
    if ($secret === '' || $token === '') {
        return false;
    }

    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
    ]);
    $response = curl_exec($ch);
    $http     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http !== 200 || !$response) return false;
    $data = json_decode($response, true);
    return !empty($data['success']);
}
