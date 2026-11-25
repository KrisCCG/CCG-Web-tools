<?php
/**
 * Weekly cron — GDPR retention sweep over persisted Requests.
 *
 * Triggered via Infomaniak Task Scheduler:
 *   0 5 * * 0  curl -s -u cron:<password> https://tiegel.be/request-api/retention.php
 *
 * Policy:
 *   - Requests older than 90 days: PII (contact email/phone/name) is replaced
 *     with hashes so analytics / metrics still work, but no real PII is at rest.
 *   - Requests older than 2 years: full deletion.
 */

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/lib/audit.php';

$now       = time();
$redact_at = $now - 90 * 86400;
$delete_at = $now - 730 * 86400;

$req_dir = __DIR__ . '/requests';
$redacted = $deleted = $skipped = 0;

if (is_dir($req_dir)) {
    foreach (glob($req_dir . '/*/*.json') ?: [] as $file) {
        $mt = filemtime($file);
        if ($mt < $delete_at) {
            @unlink($file);
            $deleted++;
            continue;
        }
        if ($mt < $redact_at) {
            $raw = @file_get_contents($file);
            if ($raw === false) { $skipped++; continue; }
            $data = json_decode($raw, true);
            if (!is_array($data)) { $skipped++; continue; }

            if (!empty($data['_redacted'])) { $skipped++; continue; }

            // Redact contact fields.
            if (isset($data['draft']['contact'])) {
                foreach (['name', 'email', 'phone', 'company'] as $k) {
                    if (!empty($data['draft']['contact'][$k])) {
                        $data['draft']['contact'][$k] = '[redacted-' . substr(hash('sha256', $data['draft']['contact'][$k]), 0, 8) . ']';
                    }
                }
            }
            // Redact free-text fields with the audit redactor.
            foreach (['file_notes', 'free_text'] as $k) {
                if (!empty($data['draft'][$k])) {
                    $data['draft'][$k] = redact_pii($data['draft'][$k]);
                }
            }
            // Redact the transcript.
            if (isset($data['transcript']) && is_array($data['transcript'])) {
                $data['transcript'] = redact_pii($data['transcript']);
            }
            $data['_redacted']    = true;
            $data['_redacted_at'] = date('c');

            if (@file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
                $skipped++;
            } else {
                $redacted++;
            }
        }
    }
}

echo "[retention.php] " . date('c') . "\n";
echo "redacted: {$redacted}\n";
echo "deleted:  {$deleted}\n";
echo "skipped:  {$skipped}\n";
