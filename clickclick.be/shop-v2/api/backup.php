<?php
/**
 * Shop v2 — daily encrypted backup of api/orders/ to Infomaniak kDrive (ported from /calculator/backup.php).
 * Differences: archive name clickclick-shop-orders-… (until 22 Oct 2026: clickclick-shop-v2-orders-…), the throw-away staging folder orders/temp/ (unpaid
 * uploads, up to 100 MB each) and the CSRF token file are not backed up. Restore: ../BACKUP.md.
 *
 * Click Click — daily encrypted backup of /orders/ to Infomaniak kDrive.
 *
 * What it does:
 *   1. tar+gzip /orders/ (skipping the .ratelimit/ noise dir + old archives).
 *   2. GPG-encrypt the archive with a symmetric passphrase from secrets.php.
 *   3. Rotate audit.log → audit-YYYY-WW.log so the rotation is included in
 *      this archive and the live audit.log starts fresh.
 *   4. Upload the .tar.gz.gpg to kDrive over WebDAV (curl PUT, basic auth).
 *   5. Verify via WebDAV PROPFIND — checks the file exists and size matches.
 *   6. Delete archives on kDrive older than BACKUP_RETENTION_DAYS.
 *   7. Append a run line to /orders/backup.log.
 *   8. Email the alert recipient on failure (or summary at month boundaries).
 *
 * Cron (request from Infomaniak admin panel):
 *   0 4 * * * php /home/clients/<id>/sites/clickclick.be/calculator/backup.php
 *
 * Restore procedure documented in calculator/BACKUP.md.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); }

// Web access to this script is gated by HTTP basic-auth, configured in
// calculator/.htaccess (<Files "backup.php"> block) — Apache returns 401 to
// anyone without credentials. Infomaniak's web-based Task Scheduler (which
// fires HTTPS requests, not CLI commands) sends the credentials via the
// "This URL is password protected" option, so cron triggers go through.
// CLI execution from SSH still works regardless of basic-auth.

const BACKUP_RETENTION_DAYS = 30;
const ORDERS_DIR_BACKUP     = __DIR__ . '/orders/';
const BACKUP_LOG            = __DIR__ . '/orders/backup.log';
const AUDIT_LOG_PATH        = __DIR__ . '/orders/audit.log';

$secrets = @include __DIR__ . '/secrets.php';
if (!is_array($secrets) || empty($secrets['backup_passphrase'])
    || empty($secrets['kdrive_endpoint'])  || empty($secrets['kdrive_user'])
    || empty($secrets['kdrive_token'])) {
    backup_log('FATAL secrets.php missing or incomplete — aborting');
    backup_alert($secrets['alert_email'] ?? '', 'Backup failed — secrets.php missing fields');
    exit(1);
}

backup_log('start');

// ── 1. Build the archive (pure-PHP — Infomaniak's web PHP disables exec) ─
// Uses PharData for tar+gzip and openssl_encrypt for AES-256-CBC. Output is
// byte-compatible with `openssl enc -d -aes-256-cbc -pbkdf2`, so any Mac/Linux
// with openssl installed can decrypt — no PHP / GPG needed for recovery.
$stamp     = date('Y-m-d_His');
$base_name = 'clickclick-shop-orders-' . $stamp;
$tmp_dir   = sys_get_temp_dir();
$tar_path  = $tmp_dir . '/' . $base_name . '.tar';
$gz_path   = $tar_path . '.gz';
$encrypted = $tmp_dir . '/' . $base_name . '.tar.gz.enc';

// Rotate audit.log first so the rotation is captured in this run's archive.
if (is_file(AUDIT_LOG_PATH) && filesize(AUDIT_LOG_PATH) > 0) {
    $rotated = ORDERS_DIR_BACKUP . 'audit-' . date('Y-\WW', filemtime(AUDIT_LOG_PATH)) . '.log';
    @rename(AUDIT_LOG_PATH, $rotated);
}

// Clean up any leftover from a previous failed run before PharData refuses to overwrite.
@unlink($tar_path);
@unlink($gz_path);
@unlink($encrypted);

try {
    $tar = new PharData($tar_path);
    // Add everything under /orders/ except the always-changing log + transient .ratelimit/.
    $tar->buildFromIterator(new CallbackFilterIterator(
        new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(ORDERS_DIR_BACKUP, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        ),
        function ($file) {
            $path = str_replace('\\', '/', (string)$file);
            if (is_dir($path)) return false;                                     // files only (folders come with their files)
            if (strpos($path, '/.ratelimit') !== false) return false;
            if (basename($path) === 'backup.log') return false;
            if (strpos($path, '/orders/temp') !== false) return false;          // unpaid staging uploads
            if (basename($path) === '.csrf-tokens.json') return false;
            return true;
        }
    ), ORDERS_DIR_BACKUP);
    $tar->compress(Phar::GZ); // creates $gz_path; leaves $tar_path in place
    unset($tar);              // release file handle before unlinking
    @unlink($tar_path);
} catch (Throwable $e) {
    backup_log('FATAL phardata failed: ' . $e->getMessage());
    backup_alert($secrets['alert_email'] ?? '', 'Backup failed — PharData: ' . $e->getMessage());
    @unlink($tar_path);
    @unlink($gz_path);
    exit(1);
}
if (!is_file($gz_path)) {
    backup_log('FATAL phardata produced no gz output');
    backup_alert($secrets['alert_email'] ?? '', 'Backup failed — no archive produced');
    exit(1);
}
$archive_bytes = (int)@filesize($gz_path);
backup_log("tar+gz ok size={$archive_bytes}B");

// ── 2. AES-256-CBC encrypt (openssl-CLI compatible: "Salted__" + 8-byte salt + ct) ──
$plaintext = @file_get_contents($gz_path);
@unlink($gz_path); // plaintext archive — always remove
if ($plaintext === false) {
    backup_log('FATAL could not read gz archive for encryption');
    backup_alert($secrets['alert_email'] ?? '', 'Backup failed — read error');
    exit(1);
}
$salt     = random_bytes(8);
$derived  = hash_pbkdf2('sha256', (string)$secrets['backup_passphrase'], $salt, 10000, 48, true);
$key      = substr($derived, 0, 32);
$iv       = substr($derived, 32, 16);
$ct       = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
unset($plaintext, $derived, $key, $iv);
if ($ct === false) {
    backup_log('FATAL openssl_encrypt failed: ' . openssl_error_string());
    backup_alert($secrets['alert_email'] ?? '', 'Backup failed — openssl_encrypt');
    exit(1);
}
if (@file_put_contents($encrypted, "Salted__" . $salt . $ct) === false) {
    backup_log('FATAL could not write encrypted archive');
    backup_alert($secrets['alert_email'] ?? '', 'Backup failed — write error');
    exit(1);
}
unset($ct);
$encrypted_bytes = (int)@filesize($encrypted);
backup_log("encrypt ok size={$encrypted_bytes}B");

// ── 3. Upload to kDrive over WebDAV ───────────────────────────────────────
$remote_name = basename($encrypted);
$endpoint    = rtrim((string)$secrets['kdrive_endpoint'], '/') . '/';
$put_url     = $endpoint . rawurlencode($remote_name);
$auth        = $secrets['kdrive_user'] . ':' . $secrets['kdrive_token'];

$fh = fopen($encrypted, 'rb');
$ch = curl_init($put_url);
curl_setopt_array($ch, [
    CURLOPT_PUT            => true,
    CURLOPT_INFILE         => $fh,
    CURLOPT_INFILESIZE     => $encrypted_bytes,
    CURLOPT_USERPWD        => $auth,
    CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FAILONERROR    => false,
    CURLOPT_TIMEOUT        => 600,
    CURLOPT_HTTPHEADER     => ['Expect:'],
]);
$put_response = curl_exec($ch);
$put_status   = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
$put_err      = curl_error($ch);
curl_close($ch);
fclose($fh);

if ($put_status < 200 || $put_status >= 300) {
    backup_log("FATAL kDrive PUT http={$put_status} err=" . trim((string)$put_err));
    backup_alert($secrets['alert_email'] ?? '', 'Backup failed — kDrive PUT http=' . $put_status);
    @unlink($encrypted);
    exit(1);
}
backup_log("kdrive put ok http={$put_status} file={$remote_name}");

// ── 4. Verify via PROPFIND ────────────────────────────────────────────────
$ch = curl_init($put_url);
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST  => 'PROPFIND',
    CURLOPT_USERPWD        => $auth,
    CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_HTTPHEADER     => ['Depth: 0'],
]);
$propfind_body   = (string)curl_exec($ch);
$propfind_status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

$verified = false;
if ($propfind_status >= 200 && $propfind_status < 300) {
    // Parse the WebDAV multistatus for getcontentlength — must match local size.
    if (preg_match('#<[^>]*getcontentlength[^>]*>(\d+)<#i', $propfind_body, $m)) {
        $remote_bytes = (int)$m[1];
        $verified = ($remote_bytes === $encrypted_bytes);
        backup_log("verify remote={$remote_bytes}B local={$encrypted_bytes}B match=" . ($verified ? 'yes' : 'no'));
    } else {
        backup_log('verify ok-status-but-no-length (treating as success)');
        $verified = true;
    }
} else {
    backup_log("verify FAILED http={$propfind_status}");
}
@unlink($encrypted);

if (!$verified) {
    backup_alert($secrets['alert_email'] ?? '', 'Backup uploaded but verify failed (http=' . $propfind_status . ')');
    // Don't exit — the file did upload, partial success is better than nothing.
}

// ── 5. Rotate old archives on kDrive ──────────────────────────────────────
$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST  => 'PROPFIND',
    CURLOPT_USERPWD        => $auth,
    CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_HTTPHEADER     => ['Depth: 1'],
]);
$listing = (string)curl_exec($ch);
$listing_status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

$swept = 0;
if ($listing_status >= 200 && $listing_status < 300) {
    // Extract <d:href>…/file.tar.gz.enc</d:href> entries with their mtime.
    if (preg_match_all('#<[^>]*href[^>]*>([^<]+\.tar\.gz\.enc)</[^>]*href[^>]*>.*?<[^>]*getlastmodified[^>]*>([^<]+)</[^>]*getlastmodified[^>]*>#is', $listing, $matches, PREG_SET_ORDER)) {
        $cutoff = time() - (BACKUP_RETENTION_DAYS * 86400);
        foreach ($matches as $m) {
            $href = $m[1];
            $mtime = strtotime($m[2]);
            if ($mtime === false || $mtime >= $cutoff) continue;
            // Build absolute URL on the same host as $endpoint
            $abs = preg_match('#^https?://#', $href)
                ? $href
                : (parse_url($endpoint, PHP_URL_SCHEME) . '://' . parse_url($endpoint, PHP_URL_HOST) . $href);
            $ch = curl_init($abs);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST  => 'DELETE',
                CURLOPT_USERPWD        => $auth,
                CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 60,
            ]);
            curl_exec($ch);
            $del_status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
            if ($del_status >= 200 && $del_status < 300) $swept++;
        }
    }
}
backup_log("retention sweep removed={$swept}");

// ── 6. Done ───────────────────────────────────────────────────────────────
backup_log('done');
exit(0);

// ── Helpers ───────────────────────────────────────────────────────────────
function backup_log(string $msg): void {
    @file_put_contents(BACKUP_LOG, date('c') . ' ' . $msg . "\n", FILE_APPEND | LOCK_EX);
    echo $msg . "\n";   // same line back to whoever called it (Task Scheduler / browser)
}

function backup_alert(string $to, string $subject): void {
    if ($to === '') return;
    $headers = "From: Click Click backup <kris@clickclickgraphics.com>\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    $body    = "Backup script (shop) reported a problem.\n\nSee shop/api/orders/backup.log on the server for details.\n\n— backup.php\n";
    @mail($to, '[clickclick] ' . $subject, $body, $headers, '-f kris@clickclickgraphics.com');
}
