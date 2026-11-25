<?php
/**
 * Click Click — artwork upload endpoint.
 *
 * Accepts a single file (PDF, JPG, PNG, TIFF) via multipart POST and stores
 * it in /orders/temp/{upload_id}/. The customer hasn't paid yet at this point,
 * so this is a holding area — webhook.php moves the file into the per-invoice
 * folder once payment confirms. cleanup.php sweeps abandoned temp folders.
 *
 * Endpoint:
 *   POST /calculator/upload.php
 *   Content-Type: multipart/form-data
 *   Body: file=<binary>
 *
 * Success → 200 JSON: { ok:true, upload_id, filename, size }
 * Failure → 4xx JSON: { ok:false, error }
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }

const TEMP_DIR    = __DIR__ . '/orders/temp/';
const MAX_BYTES   = 100 * 1024 * 1024; // 100 MB
const ALLOWED_EXT = ['pdf', 'jpg', 'jpeg', 'png', 'tiff', 'tif'];

require_once __DIR__ . '/lib/audit.php';
require_once __DIR__ . '/lib/ratelimit.php';

// Rate-limit: 30 uploads / IP / hour. One real customer order rarely exceeds
// 4–6 files; 30 leaves comfortable headroom while stopping disk-fill attacks.
if (in_array($_SERVER['REQUEST_METHOD'] ?? '', ['POST', 'DELETE'], true)) {
    rate_limit_or_die('upload:' . rate_limit_client_ip(), 30, 3600, 'json');
}

// Probabilistic cleanup: ~1% of uploads sweep the temp dir.
// Cron is preferred (calculator/cleanup.php) but this is a safety net for hosts without cron.
if (random_int(0, 99) === 0 && is_file(__DIR__ . '/cleanup.php')) {
    @include __DIR__ . '/cleanup.php';
}

function fail(int $code, string $err): void
{
    if (function_exists('audit_log')) {
        audit_log('upload.rejected', [
            'http_status' => $code,
            'reason'      => $err,
            'method'      => $_SERVER['REQUEST_METHOD'] ?? '',
        ]);
    }
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $err]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? '';

// ─── DELETE: remove a single file from an existing upload_id folder ─────
if ($method === 'DELETE') {
    $uploadId = (string)($_GET['upload_id'] ?? '');
    $filename = (string)($_GET['filename'] ?? '');
    if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/', $uploadId)) {
        fail(400, 'invalid upload_id');
    }
    // Filename must be a plain leaf — no path separators, no traversal
    if ($filename === '' || $filename !== basename($filename) || strpos($filename, '..') !== false) {
        fail(400, 'invalid filename');
    }
    $target = TEMP_DIR . $uploadId . '/' . $filename;
    if (!is_file($target)) {
        fail(404, 'not found');
    }
    if (!@unlink($target)) {
        fail(500, 'could not delete');
    }
    // Remove the upload_id folder if it's now empty
    $rest = @scandir(TEMP_DIR . $uploadId);
    if (is_array($rest) && count(array_diff($rest, ['.', '..'])) === 0) {
        @rmdir(TEMP_DIR . $uploadId);
    }
    echo json_encode(['ok' => true]);
    exit;
}

if ($method !== 'POST') {
    fail(405, 'method not allowed');
}

if (empty($_FILES['file'])) {
    fail(400, 'no file');
}

$f = $_FILES['file'];

switch ($f['error']) {
    case UPLOAD_ERR_OK:           break;
    case UPLOAD_ERR_INI_SIZE:
    case UPLOAD_ERR_FORM_SIZE:    fail(413, 'file too large');
    case UPLOAD_ERR_PARTIAL:
    case UPLOAD_ERR_NO_FILE:      fail(400, 'no file');
    default:                      fail(500, 'upload error');
}

if ($f['size'] <= 0)         fail(400, 'empty file');
if ($f['size'] > MAX_BYTES)  fail(413, 'file too large');

// Sanitize filename — strip path separators, keep [A-Za-z0-9._-]
$origName = basename((string)$f['name']);
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
if (!in_array($ext, ALLOWED_EXT, true)) {
    fail(415, 'invalid type');
}

$cleanBase = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($origName, PATHINFO_FILENAME) ?: 'artwork');
$cleanBase = trim($cleanBase, '._-');
if ($cleanBase === '') $cleanBase = 'artwork';
$cleanName = $cleanBase . '.' . $ext;

// Magic-byte check — defends against an `.exe` renamed to `.pdf`
$head = file_get_contents($f['tmp_name'], false, null, 0, 16);
if ($head === false || $head === '') fail(500, 'read error');

$ok = false;
switch ($ext) {
    case 'pdf':
        $ok = strncmp($head, "%PDF", 4) === 0;
        break;
    case 'jpg':
    case 'jpeg':
        $ok = strncmp($head, "\xFF\xD8\xFF", 3) === 0;
        break;
    case 'png':
        $ok = strncmp($head, "\x89PNG\r\n\x1A\n", 8) === 0;
        break;
    case 'tif':
    case 'tiff':
        // Two byte orders: little-endian (II*\0) or big-endian (MM\0*)
        $ok = strncmp($head, "II*\x00", 4) === 0 || strncmp($head, "MM\x00*", 4) === 0;
        break;
}
if (!$ok) fail(415, 'invalid type');

// Use a client-provided upload_id when present (lets the browser mint a UUID
// once and send the same id with concurrent uploads, so they share one bucket).
// Fall back to server-generated id only if the client didn't send one.
$existingId = (string)($_POST['upload_id'] ?? '');
if ($existingId !== '') {
    if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/', $existingId)) {
        fail(400, 'invalid upload_id');
    }
    $uploadId = $existingId;
} else {
    // Server-generated UUID v4 (random_bytes is built-in)
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    $uploadId = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
}

// Ensure the temp tree exists, with deny-all .htaccess at BOTH the orders/
// and orders/temp/ levels — protects customer PII (in /orders/) and staged
// uploads (in /orders/temp/) from direct HTTP access.
$ordersDir = __DIR__ . '/orders/';
if (!is_dir($ordersDir)) {
    if (!mkdir($ordersDir, 0755, true) && !is_dir($ordersDir)) {
        fail(500, 'storage unavailable');
    }
}
$denyHtaccess =
    "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n" .
    "<IfModule !mod_authz_core.c>\n  Order deny,allow\n  Deny from all\n</IfModule>\n" .
    "Options -Indexes\n";

$ordersHtaccess = $ordersDir . '.htaccess';
if (!is_file($ordersHtaccess)) {
    @file_put_contents($ordersHtaccess, "# Block direct HTTP access to order JSONs and uploaded artwork.\n" . $denyHtaccess);
}

if (!is_dir(TEMP_DIR)) {
    if (!mkdir(TEMP_DIR, 0755, true) && !is_dir(TEMP_DIR)) {
        fail(500, 'storage unavailable');
    }
}
$tempHtaccess = TEMP_DIR . '.htaccess';
if (!is_file($tempHtaccess)) {
    @file_put_contents($tempHtaccess, "# Block direct HTTP access to staged uploads.\n" . $denyHtaccess);
}

$dest = TEMP_DIR . $uploadId;
$folderCreatedHere = false;
if (!is_dir($dest)) {
    if (!mkdir($dest, 0755, true) && !is_dir($dest)) {
        fail(500, 'storage unavailable');
    }
    $folderCreatedHere = true;
}

// If a file with the same cleaned name already exists in the bucket, append a
// counter to keep both ("foo.pdf" → "foo (2).pdf") rather than silently overwriting.
$finalName = $cleanName;
if (is_file($dest . '/' . $finalName)) {
    $i = 2;
    while (is_file($dest . '/' . $cleanBase . " ({$i})." . $ext)) $i++;
    $finalName = $cleanBase . " ({$i})." . $ext;
}

if (!move_uploaded_file($f['tmp_name'], $dest . '/' . $finalName)) {
    if ($folderCreatedHere) @rmdir($dest);
    fail(500, 'storage error');
}
$cleanName = $finalName;

audit_log('upload.accepted', [
    'upload_id' => $uploadId,
    'filename'  => $cleanName,
    'ext'       => $ext,
    'size'      => (int)$f['size'],
]);

echo json_encode([
    'ok'        => true,
    'upload_id' => $uploadId,
    'filename'  => $cleanName,
    'size'      => (int)$f['size'],
]);
