<?php
/**
 * VIES VAT validator — queries the EU's free VAT Information Exchange System.
 *
 * Docs: https://ec.europa.eu/taxation_customs/vies/
 * REST endpoint (no auth needed):
 *   GET https://ec.europa.eu/taxation_customs/vies/rest-api/ms/{CC}/vat/{NUMBER}
 *
 * On success, VIES returns the officially registered company name and
 * address — useful for the team to confirm the customer is who they claim
 * to be, and to pre-fill billing info.
 *
 * Returns:
 *   ['status' => 'valid',       'name' => '...', 'address' => '...']
 *   ['status' => 'invalid',     'name' => '',    'address' => '']
 *   ['status' => 'malformed']                                              // not a VAT format
 *   ['status' => 'unavailable', 'reason' => '...']                         // VIES down or slow
 */

function check_vies(string $vat): array {
    // Normalise: strip whitespace, dots, hyphens. Uppercase.
    $clean = strtoupper(preg_replace('/[\s.\-]/', '', $vat));
    if ($clean === '') return ['status' => 'malformed'];

    // Format must be: 2-letter country code + 2-12 alphanumeric chars.
    if (!preg_match('/^([A-Z]{2})([0-9A-Z]{2,12})$/', $clean, $m)) {
        return ['status' => 'malformed'];
    }
    [, $country, $number] = $m;

    $url = sprintf(
        'https://ec.europa.eu/taxation_customs/vies/rest-api/ms/%s/vat/%s',
        urlencode($country),
        urlencode($number)
    );

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_USERAGENT      => 'tiegel-request-bot/1.0',
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $raw  = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($raw === false || $http !== 200) {
        return ['status' => 'unavailable', 'reason' => $err ?: "HTTP {$http}"];
    }

    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['isValid'])) {
        return ['status' => 'unavailable', 'reason' => 'unparseable response'];
    }

    if ($data['isValid'] === true) {
        return [
            'status'  => 'valid',
            'name'    => trim((string)($data['name']    ?? '')),
            'address' => trim((string)($data['address'] ?? '')),
        ];
    }
    return [
        'status'  => 'invalid',
        'name'    => '',
        'address' => '',
    ];
}

/**
 * Render only the VIES STATUS line (valid/invalid/etc). The registered
 * name and address get their own rows in the email — see submit.php.
 */
function format_vies(array $vies): string {
    switch ($vies['status'] ?? '') {
        case 'valid':
            return '✓ VALID per VIES';
        case 'invalid':
            return '✗ INVALID per VIES — number not recognised in EU registry';
        case 'malformed':
            return '⚠ format not recognised — could not query VIES (not a standard EU VAT format?)';
        case 'unavailable':
            return '⚠ VIES service unavailable — could not verify (' . ($vies['reason'] ?? 'unknown') . ')';
        case 'not_provided':
            return '';
        default:
            return '—';
    }
}

/**
 * Tidy up the multi-line address VIES returns. EU member states return it
 * with newlines (sometimes \n, sometimes blank lines). Collapse runs of
 * blank lines but preserve line breaks between street / city / country.
 */
function format_vies_address(string $address): string {
    if ($address === '') return '';
    // Normalise newlines, collapse multiple blank lines into single newlines.
    $address = str_replace(["\r\n", "\r"], "\n", $address);
    $lines = array_filter(array_map('trim', explode("\n", $address)), fn($l) => $l !== '');
    return implode("\n", $lines);
}
