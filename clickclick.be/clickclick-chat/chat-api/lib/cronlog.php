<?php
/**
 * Shared cron-run logger.
 *
 * cleanup.php and retention.php previously only echoed to stdout, which the
 * Infomaniak Task Scheduler captures but nobody can inspect afterwards. That
 * made "did the 05:00 job actually run?" unanswerable without digging through
 * the server access log (18 Aug 2026 — we tried; it is not worth it).
 *
 * Every run now appends one line to logs/cron.log, so the answer is a single
 * tail. Mirrors the pattern backup.php already uses in the calculator.
 */

const CRON_LOG_MAX_LINES = 500;   // ~18 months of daily runs; trimmed in place

function cron_log(string $script, string $summary): void {
    // dirname(__DIR__), NOT __DIR__: this file lives in lib/, so __DIR__ would
    // put the log in chat-api/lib/logs/ — the wrong place, and outside the
    // directory cleanup.php prunes. (Caught in a dry run, 18 Aug 2026.)
    $dir = dirname(__DIR__) . '/logs';
    if (!is_dir($dir) && !@mkdir($dir, 0770, true) && !is_dir($dir)) return;

    $line = sprintf("%s  %-14s %s\n", date('c'), $script, $summary);
    $path = $dir . '/cron.log';
    @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);

    // Trim in place so the file can never grow without bound. Cheap: these
    // scripts run at most once a day and the file stays tiny.
    $lines = @file($path);
    if ($lines !== false && count($lines) > CRON_LOG_MAX_LINES) {
        @file_put_contents($path, implode('', array_slice($lines, -CRON_LOG_MAX_LINES)), LOCK_EX);
    }
}

/**
 * Echo the last N runs. Both cron scripts call this, so an authenticated
 *   curl -u USER https://clickclick.be/chat-api/cleanup.php
 * answers "did yesterday's 05:00 job run?" without SSH — logs/ is blocked over
 * HTTP by design, so this is the only way to see it from outside.
 */
function cron_log_tail(int $n = 5): void {
    $path = dirname(__DIR__) . '/logs/cron.log';
    $lines = @file($path);
    if ($lines === false || !$lines) { echo "\n(no previous runs recorded)\n"; return; }
    echo "\n--- last " . min($n, count($lines)) . " run(s) ---\n";
    echo implode('', array_slice($lines, -$n));
}

