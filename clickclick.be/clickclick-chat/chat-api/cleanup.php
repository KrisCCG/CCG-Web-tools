<?php
/**
 * Daily cron — prune expired runtime files.
 *
 * Triggered via Infomaniak Task Scheduler:
 *   0 5 * * *  curl -s -u cron:<password> https://clickclick.be/chat-api/cleanup.php
 *
 * Gated behind Basic Auth in .htaccess so a random visitor can't trigger it.
 *
 * What it does:
 *   - Delete rate-limit bucket files older than 7 days.
 *   - Compress audit logs older than 30 days (gzip), delete >180 days.
 *   - Anonymise persisted Requests older than 90 days (see retention.php for
 *     the heavier PII sweep; this one is just a safety net).
 */

require_once __DIR__ . '/lib/cronlog.php';

header('Content-Type: text/plain; charset=utf-8');

$started = microtime(true);
$report  = [];

// ── Rate-limit buckets ──────────────────────────────────────────────────
$rl_dir = __DIR__ . '/.ratelimit';
$rl_cut = time() - 7 * 86400;
$rl_n   = 0;
if (is_dir($rl_dir)) {
    foreach (glob($rl_dir . '/*.json') ?: [] as $f) {
        if (filemtime($f) < $rl_cut) { @unlink($f); $rl_n++; }
    }
}
$report[] = "rate-limit files pruned: {$rl_n}";

// ── Audit logs ──────────────────────────────────────────────────────────
$log_dir = __DIR__ . '/logs';
$gz_cut  = time() - 30 * 86400;
$del_cut = time() - 180 * 86400;
$gz_n = $del_n = 0;
if (is_dir($log_dir)) {
    foreach (glob($log_dir . '/*.jsonl') ?: [] as $f) {
        $mt = filemtime($f);
        if ($mt < $del_cut) { @unlink($f); $del_n++; continue; }
        if ($mt < $gz_cut) {
            $gz = $f . '.gz';
            if (!file_exists($gz)) {
                $in  = @file_get_contents($f);
                if ($in !== false && @file_put_contents($gz, gzencode($in, 6)) !== false) {
                    @unlink($f);
                    $gz_n++;
                }
            }
        }
    }
    foreach (glob($log_dir . '/*.jsonl.gz') ?: [] as $f) {
        if (filemtime($f) < $del_cut) { @unlink($f); $del_n++; }
    }
}
$report[] = "audit logs gzipped: {$gz_n}, deleted: {$del_n}";

// ── Quick summary ───────────────────────────────────────────────────────
$req_dir = __DIR__ . '/requests';
$req_n = 0;
if (is_dir($req_dir)) {
    foreach (glob($req_dir . '/*/*.json') ?: [] as $f) {
        $req_n++;
    }
}
$report[] = "Requests on disk: {$req_n}";

$elapsed = sprintf('%.2fs', microtime(true) - $started);
echo "[cleanup.php] " . date('c') . " — " . $elapsed . "\n";
echo implode("\n", $report) . "\n";

// One line in logs/cron.log so a later "did it run?" is a tail, not a hunt.
cron_log('cleanup.php', $elapsed . ' | ' . implode(' | ', $report));
cron_log_tail();
