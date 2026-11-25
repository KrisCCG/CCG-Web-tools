<?php
// ── Shop v2 — Dashboard API (orders of THIS shop) ────────────────────────
// Read by the internal calculator-v2 Dashboard tab, next to the live shop's own copy in /calculator/.
//   GET  ?action=orders   all order JSON files of this shop (TEST-… orders included, flagged test_mode)
//   GET  ?action=logs     browse log lines (shop v2 does not write any yet -> empty list)
//   POST ?action=update   production checkboxes: files_checked, printed, shipped, ready_pickup, done
//   GET  ?action=express  {available, since}   POST ?action=express {available:bool}  switch the urgent option on/off
//   GET  ?action=files&invoice=2026-118              the customer's files of one order, per product (name, size)
//   GET  ?action=download&invoice=2026-118           all files of that order as one zip (2026-118.zip)
//   GET  ?action=download&invoice=2026-118&file=…    one file (path as returned by ?action=files)
//        Downloads only ever read inside orders/{invoice}/artwork/ and are written to the audit log (Kris, 25 Oct 2026).
//   GET  ?action=holiday  the planned closure + holiday mode   POST ?action=holiday {from, back} or {clear:true}  (26 Oct 2026)
//   GET  ?action=pricedata   the shop's price file (data/price-data.xlsx) for calculator-v2
//   GET  ?action=selftest    the shop's data/selftest.json for calculator-v2's price check
//        One price file on the server instead of two (Kris, 25 Oct 2026): calculator-v2 no longer keeps its own copy.
// Gated by the "Click Click — dashboard" login in api/.htaccess (same login as /calculator-v2/).
// No CORS header on purpose: it is only called same-origin.

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

define('ORDERS_DIR', __DIR__ . '/orders/');
const INVOICE_RE = '/^(TEST-)?\d{4}-\d{3}$/';

// ── Customer files (artwork) of one order ───────────────────────────────
// webhook.php moves the files into orders/{invoice}/artwork/items/{item id}/ (cart orders) or
// orders/{invoice}/artwork/ (older single-item orders); retention.php deletes the folder 6 months
// after payment and sets artwork_purged_at. The disk is the truth: we list what is there.
function artwork_dir(string $invoice): string { return ORDERS_DIR . $invoice . '/artwork'; }

/** All files under the order's artwork folder: [['path' => 'items/1/x.pdf', 'name' => 'x.pdf', 'size' => 123, 'group' => '1'|''], …] */
function artwork_files(string $invoice): array {
    $root = artwork_dir($invoice);
    if (!is_dir($root)) return [];
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!$f->isFile()) continue;
        $name = $f->getFilename();
        if ($name === '' || $name[0] === '.') continue;                  // .DS_Store, .htaccess …
        $rel = ltrim(str_replace('\\', '/', substr($f->getPathname(), strlen($root))), '/');
        $group = preg_match('#^items/([^/]+)/#', $rel, $m) ? $m[1] : '';
        $out[] = ['path' => $rel, 'name' => $name, 'size' => $f->getSize(), 'group' => $group];
    }
    usort($out, fn($a, $b) => strcmp($a['path'], $b['path']));
    return $out;
}

/** Product line for a group (item id) from the order JSON, e.g. "Card · A6 · 200 copies · RecyStar 300". */
function artwork_label(array $order, string $group): string {
    foreach ((array)($order['items'] ?? []) as $i => $it) {
        if ((string)($it['id'] ?? '') === $group) {
            $line = (string)($it['specs_line'] ?? '');
            if ($line === '') $line = implode(' · ', array_filter([(string)($it['product'] ?? ''), (string)($it['size'] ?? ''), isset($it['qty']) ? $it['qty'] . ' copies' : '']));
            return ($i + 1) . ' · ' . $line;
        }
    }
    return $group === '' ? (string)($order['specs'] ?? 'Order') : 'Product ' . $group;
}

/** Short folder name inside the zip, e.g. "1 Card A6 200". */
function zip_folder(array $order, string $group): string {
    foreach ((array)($order['items'] ?? []) as $i => $it) {
        if ((string)($it['id'] ?? '') === $group) {
            $p = trim(implode(' ', array_filter([ucfirst((string)($it['product'] ?? '')), (string)($it['size'] ?? ''), (string)($it['qty'] ?? '')])));
            return safe_name(($i + 1) . ' ' . ($p !== '' ? $p : 'product'), 40);
        }
    }
    return safe_name('product ' . $group, 40);
}

function read_order(string $invoice): ?array {
    if (!preg_match(INVOICE_RE, $invoice)) return null;
    $file = ORDERS_DIR . $invoice . '.json';
    if (!is_file($file)) return null;
    $o = json_decode((string)file_get_contents($file), true);
    return is_array($o) ? $o : null;
}

function safe_name(string $s, int $max = 60): string {   // for folder/file names inside the zip and in headers
    $s = preg_replace('/[^A-Za-z0-9._ -]+/', ' ', $s);
    $s = trim(preg_replace('/\s+/', ' ', $s), ' .-');
    return $s === '' ? 'file' : substr($s, 0, $max);
}

function json_fail(int $code, string $msg): void { http_response_code($code); echo json_encode(['ok' => false, 'error' => $msg]); exit; }

$action = $_GET['action'] ?? '';

if ($action === 'orders') {
    $orders = [];
    if (is_dir(ORDERS_DIR)) {
        foreach (glob(ORDERS_DIR . '*.json') as $file) {
            $data = json_decode((string)file_get_contents($file), true);
            if (is_array($data) && isset($data['invoice_num']) && preg_match(INVOICE_RE, (string)$data['invoice_num'])) {
                $af = artwork_files((string)$data['invoice_num']);
                $data['files_count']  = count($af);                                            // for the download button
                $data['files_one']    = count($af) === 1 ? $af[0]['path'] : null;              // a single file downloads as itself, not as a zip
                $data['files_purged'] = !empty($data['artwork_purged_at']);
                $orders[] = $data;
            }
        }
    }
    usort($orders, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
    echo json_encode($orders);
    exit;
}

if ($action === 'logs') {
    $entries = [];
    $log_file = ORDERS_DIR . 'calc_log.jsonl';
    if (is_file($log_file)) {
        foreach (file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $e = json_decode($line, true);
            if (is_array($e)) $entries[] = $e;
        }
    }
    echo json_encode($entries);
    exit;
}

if ($action === 'express') {
    require_once __DIR__ . '/lib/express.php';
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $input = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($input) || !array_key_exists('available', $input)) { http_response_code(400); echo json_encode(['error' => 'available (true/false) required']); exit; }
        if (!express_set((bool)$input['available'])) { http_response_code(500); echo json_encode(['error' => 'could not save']); exit; }
    }
    echo json_encode(express_status());
    exit;
}

if ($action === 'holiday') {
    require_once __DIR__ . '/lib/holiday.php';
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $input = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($input)) json_fail(400, 'JSON body required');
        if (!empty($input['clear'])) {
            if (!holiday_clear()) json_fail(500, 'Could not remove the closure (server).');
        } else {
            $err = holiday_set((string)($input['from'] ?? ''), (string)($input['back'] ?? ''));
            if ($err !== null) json_fail(400, $err);
        }
        if (is_file(__DIR__ . '/lib/audit.php')) {
            require_once __DIR__ . '/lib/audit.php';
            if (function_exists('audit_log')) audit_log('dashboard.holiday', ['clear' => !empty($input['clear']), 'from' => $input['from'] ?? null, 'back' => $input['back'] ?? null,
                'user' => (string)($_SERVER['REMOTE_USER'] ?? $_SERVER['PHP_AUTH_USER'] ?? '')]);
        }
    }
    require_once __DIR__ . '/lib/workdays.php';
    echo json_encode(['ok' => true] + holiday_status() + ['first_ready' => wd_holiday_first_ready(), 'notice_days' => HOLIDAY_NOTICE_DAYS, 'resume_days' => HOLIDAY_RESUME_DAYS]);
    exit;
}

if ($action === 'pricedata' || $action === 'selftest') {
    // Read-only, behind the same staff login as the rest of this file. data/ itself stays closed to the web.
    $f = __DIR__ . '/data/' . ($action === 'pricedata' ? 'price-data.xlsx' : 'selftest.json');
    if (!is_file($f)) json_fail(404, basename($f) . ' not found in shop/api/data/');
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: ' . ($action === 'pricedata' ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : 'application/json; charset=utf-8'));
    header('X-Content-Type-Options: nosniff');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', (int)filemtime($f)) . ' GMT');   // calculator-v2 shows "uploaded <date>"
    header('Content-Length: ' . filesize($f));
    readfile($f);
    exit;
}

if ($action === 'files') {
    $invoice = (string)($_GET['invoice'] ?? '');
    $order   = read_order($invoice);
    if ($order === null) json_fail(404, 'Order not found');
    $groups = [];
    foreach (artwork_files($invoice) as $f) {
        $g = $f['group'];
        if (!isset($groups[$g])) $groups[$g] = ['item' => $g, 'label' => artwork_label($order, $g), 'files' => []];
        $groups[$g]['files'][] = ['name' => $f['name'], 'size' => $f['size'], 'path' => $f['path']];
    }
    echo json_encode(['ok' => true, 'invoice' => $invoice, 'purged' => !empty($order['artwork_purged_at']),
        'purged_at' => $order['artwork_purged_at'] ?? null, 'total' => array_sum(array_map(fn($g) => count($g['files']), $groups)),
        'groups' => array_values($groups)]);
    exit;
}

if ($action === 'download') {
    $invoice = (string)($_GET['invoice'] ?? '');
    $order   = read_order($invoice);
    if ($order === null) json_fail(404, 'Order not found');
    $files = artwork_files($invoice);
    if (!$files) json_fail(404, !empty($order['artwork_purged_at']) ? 'The files of this order were deleted (6 months after payment).' : 'This order has no files.');
    require_once __DIR__ . '/lib/audit.php';
    $user = (string)($_SERVER['REMOTE_USER'] ?? $_SERVER['PHP_AUTH_USER'] ?? '');
    @set_time_limit(300);
    while (ob_get_level()) ob_end_clean();

    // One file: it must be one of the files listed for this order (no paths from outside the folder).
    if (isset($_GET['file'])) {
        $want = (string)$_GET['file'];
        $hit  = null;
        foreach ($files as $f) if ($f['path'] === $want) { $hit = $f; break; }
        $root = realpath(artwork_dir($invoice));
        $full = $hit ? realpath(artwork_dir($invoice) . '/' . $hit['path']) : false;
        if (!$hit || !$root || !$full || strpos($full, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($full)) json_fail(404, 'File not found');
        if (function_exists('audit_log')) audit_log('dashboard.download', ['invoice' => $invoice, 'file' => $hit['path'], 'user' => $user]);
        $name = safe_name(pathinfo($hit['name'], PATHINFO_FILENAME), 80) . '.' . strtolower(safe_name(pathinfo($hit['name'], PATHINFO_EXTENSION), 8));
        header('Content-Type: application/octet-stream');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="' . $name . '"; filename*=UTF-8\'\'' . rawurlencode($hit['name']));
        header('Content-Length: ' . filesize($full));
        readfile($full);
        exit;
    }

    // All files as one zip, one folder per product: "2026-118/1 Card A6 200 copies/front.pdf".
    if (!class_exists('ZipArchive')) json_fail(501, 'Zip is not available on this server; use the separate download links in the order window.');
    $tmp = tempnam(sys_get_temp_dir(), 'ccz');
    $zip = new ZipArchive();
    if ($tmp === false || $zip->open($tmp, ZipArchive::OVERWRITE) !== true) json_fail(500, 'Could not create the zip');
    $used = [];
    foreach ($files as $f) {
        $dir   = $f['group'] === '' ? '' : zip_folder($order, $f['group']) . '/';
        $entry = $invoice . '/' . $dir . $f['name'];
        for ($n = 2; isset($used[$entry]); $n++) $entry = $invoice . '/' . $dir . $n . '-' . $f['name'];
        $used[$entry] = true;
        $zip->addFile(artwork_dir($invoice) . '/' . $f['path'], $entry);
        if (method_exists($zip, 'setCompressionName')) $zip->setCompressionName($entry, ZipArchive::CM_STORE);   // PDFs/JPGs are already compressed: store = fast
    }
    if (!$zip->close()) { @unlink($tmp); json_fail(500, 'Could not create the zip'); }
    if (function_exists('audit_log')) audit_log('dashboard.download', ['invoice' => $invoice, 'file' => '(all, zip)', 'count' => count($files), 'user' => $user]);
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $invoice . '.zip"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    @unlink($tmp);
    exit;
}

if ($action === 'update') {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'POST required']);
        exit;
    }
    $input   = json_decode((string)file_get_contents('php://input'), true);
    $invoice = (string)($input['invoice_num'] ?? '');
    if (!preg_match(INVOICE_RE, $invoice)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid invoice number']);
        exit;
    }
    $file = ORDERS_DIR . $invoice . '.json';
    if (!is_file($file)) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }
    $fh = fopen($file, 'c+');
    if (!$fh) { http_response_code(500); echo json_encode(['error' => 'Cannot open order']); exit; }
    flock($fh, LOCK_EX);
    $order = json_decode((string)stream_get_contents($fh), true);
    if (!is_array($order)) { flock($fh, LOCK_UN); fclose($fh); http_response_code(500); echo json_encode(['error' => 'Corrupt order']); exit; }
    foreach (['files_checked', 'printed', 'shipped', 'ready_pickup', 'done'] as $field) {
        if (isset($input[$field])) $order[$field] = (bool)$input[$field];
    }
    $order['production_updated_at'] = date('c');
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($order, JSON_PRETTY_PRINT));
    flock($fh, LOCK_UN);
    fclose($fh);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action. Use ?action=orders, ?action=logs, ?action=express, ?action=files, ?action=download, ?action=pricedata, ?action=selftest, ?action=holiday or ?action=update']);
