<?php
/**
 * Click Click — temp upload sweeper.
 *
 * Deletes upload-id folders inside /orders/temp/ that are older than
 * UPLOAD_TTL_HOURS — these are uploads from customers who never completed
 * payment. Idempotent and safe to run as often as you like.
 *
 * Run modes:
 *   - Cron (preferred): `0 3 * * * php /home/.../calculator/cleanup.php`
 *   - Probabilistic: included from upload.php for ~1% of requests (safety net
 *     for hosts without cron access).
 */

declare(strict_types=1);

const TEMP_DIR_CLEANUP   = __DIR__ . '/orders/temp/';
const CLEANUP_LOG        = __DIR__ . '/orders/cleanup.log';
const UPLOAD_TTL_HOURS   = 24;

function cleanup_log(string $msg): void {
    @file_put_contents(CLEANUP_LOG, date('c') . ' ' . $msg . "\n", FILE_APPEND);
}

cleanup_log('start (sapi=' . PHP_SAPI . ')');

if (!is_dir(TEMP_DIR_CLEANUP)) {
    cleanup_log('temp dir missing, nothing to do');
    return;
}

$cutoff = time() - (UPLOAD_TTL_HOURS * 3600);
$dirs   = @scandir(TEMP_DIR_CLEANUP);
if (!$dirs) {
    cleanup_log('scandir failed or empty');
    return;
}

$kept = 0;
$swept = 0;
foreach ($dirs as $name) {
    if ($name === '.' || $name === '..' || $name === '.htaccess') continue;
    $path = TEMP_DIR_CLEANUP . $name;
    if (!is_dir($path)) continue;

    // Use the most recent mtime in the folder (file or dir) so a slow upload
    // doesn't get reaped between PUT and the customer hitting Pay.
    $latest = @filemtime($path) ?: 0;
    foreach ((array)@scandir($path) as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $latest = max($latest, (int)@filemtime($path . '/' . $entry));
    }

    if ($latest > 0 && $latest < $cutoff) {
        foreach ((array)@scandir($path) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            @unlink($path . '/' . $entry);
        }
        @rmdir($path);
        $swept++;
    } else {
        $kept++;
    }
}

cleanup_log("done — swept {$swept}, kept {$kept}");
