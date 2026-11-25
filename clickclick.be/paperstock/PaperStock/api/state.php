<?php
/**
 * Paper Stock — shared state API (with automatic backups)
 *
 *   GET  api/state.php  -> returns the saved JSON state
 *   POST api/state.php  -> validates, snapshots the previous state, then
 *                          atomically overwrites the live state document.
 *
 * Storage:
 *   ../data/state.json              the live document (always the latest)
 *   ../data/backups/state-*.json    one snapshot per save (rolling, keeps last N)
 *   ../data/backups/daily/state-*   one snapshot per calendar day (keeps last N)
 *
 * "should not break or change":
 *   - the live file is written temp-then-rename (atomic) so a crash mid-write
 *     can never leave a half-written / corrupt state.json;
 *   - every save first copies the previous good state into backups/, so any
 *     bad save, accidental reset, or deletion can be rolled back by copying a
 *     backup over state.json;
 *   - writers are serialised with an exclusive lock.
 *
 * The whole ../data/ folder (incl. backups) is blocked from the web by
 * ../data/.htaccess — backups are not publicly reachable.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$dataDir   = __DIR__ . '/../data';
$file      = $dataDir . '/state.json';
$backupDir = $dataDir . '/backups';
$dailyDir  = $backupDir . '/daily';

// --- how much history to keep (adjust freely) ---
$KEEP_SNAPSHOTS = 300;   // rolling per-save backups
$KEEP_DAILY     = 365;   // daily snapshots (~1 year)

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (!is_file($file)) {
        echo json_encode(['v' => 2, 'papers' => [], 'offer' => []]);
        exit;
    }
    readfile($file);
    exit;
}

if ($method === 'POST' || $method === 'PUT') {
    $raw  = file_get_contents('php://input');
    $json = json_decode($raw, true);

    // Validate before touching anything on disk.
    if (!is_array($json) || !isset($json['papers']) || !is_array($json['papers'])) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid payload']);
        exit;
    }

    if (!is_dir($dataDir))   { @mkdir($dataDir, 0775, true); }
    if (!is_dir($backupDir)) { @mkdir($backupDir, 0775, true); }
    if (!is_dir($dailyDir))  { @mkdir($dailyDir, 0775, true); }

    // Defense in depth: also drop a deny rule inside backups/ in case the
    // parent .htaccess is ever lost.
    $ht = $backupDir . '/.htaccess';
    if (!is_file($ht)) {
        @file_put_contents(
            $ht,
            "# Block all direct web access to backups.\n" .
            "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n" .
            "<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n"
        );
    }

    // Serialise concurrent writers.
    $lockFp = fopen($dataDir . '/.state.lock', 'c');
    if ($lockFp) { flock($lockFp, LOCK_EX); }

    // 1) Snapshot the PREVIOUS good state before overwriting it.
    if (is_file($file) && filesize($file) > 0) {
        @copy($file, $backupDir . '/state-' . date('Y-m-d_His') . '.json');
        prune_dir($backupDir, $KEEP_SNAPSHOTS);
    }

    // 2) Atomic write of the new state: temp file, then rename over the target.
    $payload = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $tmp = $file . '.' . getmypid() . '.tmp';
    $ok  = (file_put_contents($tmp, $payload) !== false);
    if ($ok) {
        @chmod($tmp, 0664);
        $ok = rename($tmp, $file);
    }

    // 3) Once per day, keep a daily snapshot of the now-current state.
    if ($ok) {
        $dailyFile = $dailyDir . '/state-' . date('Y-m-d') . '.json';
        if (!is_file($dailyFile)) {
            @copy($file, $dailyFile);
            prune_dir($dailyDir, $KEEP_DAILY);
        }
    }

    if ($lockFp) { flock($lockFp, LOCK_UN); fclose($lockFp); }

    if (!$ok) {
        if (is_file($tmp)) { @unlink($tmp); }
        http_response_code(500);
        echo json_encode(['error' => 'could not write state']);
        exit;
    }

    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'method not allowed']);

/**
 * Keep only the newest $keep "state-*.json" files in $dir.
 * Our filenames embed a zero-padded timestamp, so lexicographic order is
 * chronological order.
 */
function prune_dir($dir, $keep) {
    $files = glob($dir . '/state-*.json');
    if ($files === false || count($files) <= $keep) { return; }
    sort($files);                                  // oldest first
    $remove = array_slice($files, 0, count($files) - $keep);
    foreach ($remove as $f) { @unlink($f); }
}
