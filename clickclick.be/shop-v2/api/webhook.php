<?php
// Shop v2 webhook — Mollie calls this (no login: see api/.htaccess). Every call is verified by asking
// Mollie's API for the payment, so a forged POST cannot mark an order paid.
// TEST payments (Mollie mode 'test'): marked [TEST], never e-mailed to the accountant (WinAuditor).
// ── Configuration ─────────────────────────────────────────────────────────
$secrets = @include __DIR__ . '/secrets.php';
if (!is_array($secrets) || empty($secrets['mollie_key'])) {
    error_log('webhook.php: secrets.php missing or incomplete');
    http_response_code(500);
    exit('config');
}
define('MOLLIE_API_KEY',     $secrets['mollie_key']);
define('COMPANY_NAME',       'Click Click Graphics SRL');
define('COMPANY_EMAIL',      'shop@clickclickgraphics.com');
define('WINAUDITOR_EMAIL',   'Invoices+O8H-AE-M8D@winauditor.net');
define('ORDERS_DIR',         __DIR__ . '/orders/');

require_once __DIR__ . '/lib/audit.php';

// ── Mollie IP allowlist (defence-in-depth) ────────────────────────────────
// We already verify every webhook by querying the Mollie API server-side, so
// a spoofed POST can't actually mark an order paid. But filtering by source
// IP keeps the noise out of the audit log and slows down probes.
$webhook_ips = $secrets['mollie_webhook_ips'] ?? [];
if (!empty($webhook_ips)) {
    $client_ip = audit_client_ip();
    // thankyou.php re-triggers this webhook from our own server: allow that address too.
    if (!in_array($client_ip, $webhook_ips, true) && $client_ip !== ($_SERVER['SERVER_ADDR'] ?? '-')) {
        audit_log('webhook.bad_ip', ['ip' => $client_ip]);
        http_response_code(403);
        exit('forbidden');
    }
}

// Load invoice generator only if fpdf is available
$invoice_available = file_exists(__DIR__ . '/fpdf.php') && file_exists(__DIR__ . '/invoice.php');
if ($invoice_available) {
    try { require_once __DIR__ . '/invoice.php'; } catch (Throwable $e) {
        $invoice_available = false;
    }
}

// ── Fetch payment from Mollie ─────────────────────────────────────────────
function mollie_get($endpoint) {
    $ch = curl_init('https://api.mollie.com/v2/' . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . MOLLIE_API_KEY],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// ── Texts: customer mails are NL / FR / EN (the language the customer used in the shop) ──
// The internal mail to the shop is always English.
function order_lang($order): string {
    $l = (string)($order['lang'] ?? 'en');
    return in_array($l, ['nl', 'fr', 'en'], true) ? $l : 'en';
}
function T(string $key, string $lang): string {
    static $x = [
        'item'      => ['en' => 'Item',            'nl' => 'Artikel',            'fr' => 'Article'],
        'price_inc' => ['en' => 'Price incl. VAT', 'nl' => 'Prijs incl. btw',    'fr' => 'Prix TVAC'],
        'subtotal'  => ['en' => 'Subtotal',        'nl' => 'Subtotaal',          'fr' => 'Sous-total'],
        'delivery'  => ['en' => 'Delivery',        'nl' => 'Levering',           'fr' => 'Livraison'],
        'pickup'    => ['en' => 'Pick-up',         'nl' => 'Afhalen',            'fr' => 'Retrait'],
        'total_inc' => ['en' => 'Total incl. VAT', 'nl' => 'Totaal incl. btw',   'fr' => 'Total TVAC'],
        'files'     => ['en' => 'Files',           'nl' => 'Bestanden',          'fr' => 'Fichiers'],
        'none'      => ['en' => 'none',            'nl' => 'geen',               'fr' => 'aucun'],
        'receipt'   => ['en' => 'Receipt',         'nl' => 'Ontvangstbewijs',    'fr' => 'Reçu'],
        'invoice'   => ['en' => 'Invoice',         'nl' => 'Factuur',            'fr' => 'Facture'],
        'receipt_l' => ['en' => 'receipt',         'nl' => 'ontvangstbewijs',    'fr' => 'reçu'],
        'invoice_l' => ['en' => 'invoice',         'nl' => 'factuur',            'fr' => 'facture'],
        'hello'     => ['en' => 'Hello',           'nl' => 'Hallo',              'fr' => 'Bonjour'],
        'thanks'    => ['en' => 'Thank you for your order! Please find your %s attached.',
                        'nl' => 'Bedankt voor je bestelling! In bijlage vind je je %s.',
                        'fr' => 'Merci pour votre commande ! Vous trouverez votre %s en pièce jointe.'],
        'delivery_c'=> ['en' => 'Delivery',        'nl' => 'Levering',           'fr' => 'Livraison'],
        'pickup_shop'=>['en' => 'Pick-up at our shop', 'nl' => 'Afhalen in onze winkel', 'fr' => 'Retrait dans notre magasin'],
        'questions' => ['en' => "Any questions? We're happy to help at %s or +32 2 880 70 36.",
                        'nl' => 'Vragen? We helpen je graag via %s of +32 2 880 70 36.',
                        'fr' => 'Des questions ? Nous sommes à votre disposition à %s ou au +32 2 880 70 36.'],
        'bye'       => ['en' => 'Many thanks for your order.<br><strong>Julien and Kris</strong>',
                        'nl' => 'Hartelijk dank voor je bestelling.<br><strong>Julien en Kris</strong>',
                        'fr' => 'Merci pour votre commande.<br><strong>Julien et Kris</strong>'],
    ];
    return $x[$key][$lang] ?? $x[$key]['en'] ?? $key;
}

// ── Send e-mail (HTML + optional PDF) ─────────────────────────────────────
// Preferred: authenticated SMTP through our own mailbox (secrets.php: smtp_user / smtp_pass), so the
// mail is DKIM-signed by Infomaniak and passes SPF/DMARC. Without those keys it falls back to PHP mail()
// with a matching envelope sender (-f). Subject is always RFC-2047 encoded, body is base64.
function mail_encode_subject(string $s): string {
    return (strpos($s, '=?UTF-8?') === 0 || !preg_match('/[^\x20-\x7E]/', $s)) ? $s : '=?UTF-8?B?' . base64_encode($s) . '?=';
}
function build_mime(string $to, string $subject, string $html, ?string $pdf, ?string $pdfName): array {
    $b = 'cc_' . md5(uniqid('', true));
    $headers  = "From: Click Click Graphics SRL <shop@clickclickgraphics.com>\r\n";
    $headers .= "Reply-To: shop@clickclickgraphics.com\r\n";
    $headers .= "Date: " . date('r') . "\r\n";
    $headers .= "Message-ID: <" . bin2hex(random_bytes(12)) . "@clickclickgraphics.com>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$b}\"\r\n";
    $body  = "--{$b}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($html)) . "\r\n";
    if ($pdf && $pdfName) {
        $body .= "--{$b}\r\nContent-Type: application/pdf; name=\"{$pdfName}\"\r\n";
        $body .= "Content-Disposition: attachment; filename=\"{$pdfName}\"\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($pdf)) . "\r\n";
    }
    $body .= "--{$b}--\r\n";
    return [$headers, $body];
}
function smtp_send(array $cfg, string $to, string $subject, string $headers, string $body): bool {
    $host = $cfg['smtp_host'] ?? 'mail.infomaniak.com';
    $port = (int)($cfg['smtp_port'] ?? 465);
    $fp = @stream_socket_client("ssl://{$host}:{$port}", $en, $es, 20);
    if (!$fp) { error_log("smtp: connect failed {$en} {$es}"); return false; }
    stream_set_timeout($fp, 20);
    $read = function () use ($fp) { $r = ''; while (($l = fgets($fp, 1024)) !== false) { $r .= $l; if (strlen($l) < 4 || $l[3] === ' ') break; } return $r; };
    $cmd = function (string $c, string $ok) use ($fp, $read) { if ($c !== '') fwrite($fp, $c . "\r\n"); $r = $read(); return strpos($r, $ok) === 0 ? true : $r; };
    $steps = [
        ['', '220'], ['EHLO clickclick.be', '250'], ['AUTH LOGIN', '334'],
        [base64_encode($cfg['smtp_user']), '334'], [base64_encode($cfg['smtp_pass']), '235'],
        ['MAIL FROM:<shop@clickclickgraphics.com>', '250'], ['RCPT TO:<' . $to . '>', '250'], ['DATA', '354'],
    ];
    foreach ($steps as [$c, $ok]) {
        $r = $cmd($c, $ok);
        if ($r !== true) { error_log('smtp: step failed (' . ($c === '' ? 'greeting' : preg_replace('/^(\w+).*/', '$1', $c)) . '): ' . trim((string)$r)); fclose($fp); return false; }
    }
    $msg = "To: {$to}\r\nSubject: {$subject}\r\n" . $headers . "\r\n" . $body;
    $msg = preg_replace('/^\./m', '..', str_replace("\r\n", "\n", $msg));
    fwrite($fp, str_replace("\n", "\r\n", $msg) . "\r\n.\r\n");
    $r = $read();
    fwrite($fp, "QUIT\r\n"); fclose($fp);
    if (strpos($r, '250') !== 0) { error_log('smtp: message rejected: ' . trim($r)); return false; }
    return true;
}
function send_email($to, $subject, $html_body, $pdf_bytes = null, $pdf_filename = null) {
    global $secrets;
    $subject = mail_encode_subject((string)$subject);
    [$headers, $body] = build_mime($to, $subject, $html_body, $pdf_bytes, $pdf_filename);
    if (!empty($secrets['smtp_user']) && !empty($secrets['smtp_pass'])) {
        if (smtp_send($secrets, $to, $subject, $headers, $body)) return true;
        error_log('send_email: SMTP failed, falling back to mail()');
    }
    return mail($to, $subject, $body, $headers, '-f shop@clickclickgraphics.com');
}

// ── Move staged artwork for ONE upload_id into a destination folder ────────
// Re-used by both the new per-item move (v2 orders) and the legacy single-folder
// move (v1 orders). Returns the list of relative paths moved.
function move_artwork_folder(string $uploadId, string $dstDir, string $relPrefix): array {
    $tempDir = ORDERS_DIR . 'temp/' . $uploadId;
    if (!is_dir($tempDir)) {
        error_log("webhook: missing temp folder at {$tempDir}");
        return [];
    }
    $entries = @scandir($tempDir) ?: [];
    $files = array_values(array_filter(
        $entries,
        fn($e) => $e !== '.' && $e !== '..' && is_file($tempDir . '/' . $e)
    ));
    if (!$files) {
        error_log("webhook: no files in temp at {$tempDir}");
        return [];
    }
    if (!is_dir($dstDir) && !mkdir($dstDir, 0755, true) && !is_dir($dstDir)) {
        error_log("webhook: could not create {$dstDir}");
        return [];
    }
    $moved = [];
    foreach ($files as $name) {
        $src = $tempDir . '/' . $name;
        $dst = rtrim($dstDir, '/') . '/' . $name;
        if (!@rename($src, $dst)) {
            if (!@copy($src, $dst) || !@unlink($src)) {
                error_log("webhook: could not move {$name}");
                continue;
            }
        }
        $moved[] = rtrim($relPrefix, '/') . '/' . $name;
    }
    @rmdir($tempDir);
    return $moved;
}

// ── Move staged artwork from /orders/temp/ into the per-invoice folder ─────
// For schema_version 2 orders: one subfolder per item under /artwork/items/{item_id}/.
// For schema_version 1 orders (legacy): one flat /artwork/ folder.
// Mutates $order in-place: writes resolved paths back to each item, and returns
// a flat list of all moved relative paths for top-level convenience fields.
function finalize_all_artwork(array &$order): array {
    $invoice = $order['invoice_num'] ?? '';
    if ($invoice === '') return [];

    if (!empty($order['items']) && is_array($order['items'])) {
        $allMoved = [];
        foreach ($order['items'] as $idx => $item) {
            $uid    = (string)($item['upload_id'] ?? '');
            $itemId = (string)($item['id']        ?? '');
            if ($uid === '' || $itemId === '') continue;
            $dstDir  = ORDERS_DIR . $invoice . '/artwork/items/' . $itemId . '/';
            $relPrefix = 'orders/' . $invoice . '/artwork/items/' . $itemId;
            $moved = move_artwork_folder($uid, $dstDir, $relPrefix);
            $order['items'][$idx]['artwork_paths'] = $moved;
            foreach ($moved as $m) $allMoved[] = $m;
        }
        return $allMoved;
    }

    // Schema v1 legacy: single upload_id
    $uploadId = $order['upload_id'] ?? '';
    if ($uploadId === '') return [];
    $dstDir = ORDERS_DIR . $invoice . '/artwork/';
    return move_artwork_folder($uploadId, $dstDir, 'orders/' . $invoice . '/artwork');
}

// ── Build a per-item HTML block for the email body ────────────────────────
// For schema_version 2 orders: a rows table listing every line item.
// For legacy v1 orders: a single line using the old specs / artwork fields.
function build_items_html(array $order, bool $is_internal): string {
    $inv = htmlspecialchars($order['invoice_num'] ?? '');
    $L = $is_internal ? 'en' : order_lang($order);

    if (!empty($order['items']) && is_array($order['items'])) {
        $rows = [];
        foreach ($order['items'] as $idx => $item) {
            $line  = htmlspecialchars($item['specs_line'] ?? '');
            $price = number_format((float)($item['price_incl'] ?? 0), 2, ',', "'");
            $files = array_map('htmlspecialchars', (array)($item['artwork_filenames'] ?? []));
            $filesStr = $files ? implode(', ', $files) : '<em>' . T('none', $L) . '</em>';
            if ($is_internal) {
                $itemId = htmlspecialchars($item['id'] ?? '');
                $folder = "/orders/{$inv}/artwork/items/{$itemId}/";
                $rows[] = "<tr><td style='padding:6px 8px;border-top:1px solid #eee'>{$line}</td>"
                       . "<td style='padding:6px 8px;border-top:1px solid #eee;text-align:right;white-space:nowrap'>&euro; {$price}</td></tr>"
                       . "<tr><td colspan='2' style='padding:0 8px 8px;font-size:12px;color:#666'>" . T('files', $L) . ($L === 'fr' ? ' :' : ':') . " {$filesStr} <span style='color:#999'>(in {$folder})</span></td></tr>";
            } else {
                $rows[] = "<tr><td style='padding:6px 8px;border-top:1px solid #eee'>{$line}</td>"
                       . "<td style='padding:6px 8px;border-top:1px solid #eee;text-align:right;white-space:nowrap'>&euro; {$price}</td></tr>"
                       . "<tr><td colspan='2' style='padding:0 8px 8px;font-size:12px;color:#666'>" . T('files', $L) . ($L === 'fr' ? ' :' : ':') . " {$filesStr}</td></tr>";
            }
        }
        $subInc = number_format((float)($order['subtotal_inc'] ?? 0), 2, ',', "'");
        $delInc = number_format((float)($order['delivery_inc'] ?? 0), 2, ',', "'");
        $totInc = number_format((float)($order['amount_inc']   ?? 0), 2, ',', "'");
        $delivery_label = ($order['delivery'] ?? 'no') === 'yes' ? T('delivery', $L) : T('pickup', $L);
        $rowsHtml = implode("\n", $rows);
        return "
<table style='width:100%;border-collapse:collapse;margin:8px 0 12px;font-size:13px;border:1px solid #eee;border-radius:6px'>
  <thead><tr><th style='text-align:left;padding:6px 8px;background:#fafafa;font-weight:600'>" . T('item', $L) . "</th><th style='text-align:right;padding:6px 8px;background:#fafafa;font-weight:600'>" . T('price_inc', $L) . "</th></tr></thead>
  <tbody>
  {$rowsHtml}
  <tr><td style='padding:6px 8px;border-top:2px solid #ddd'>" . T('subtotal', $L) . "</td><td style='padding:6px 8px;border-top:2px solid #ddd;text-align:right'>&euro; {$subInc}</td></tr>
  <tr><td style='padding:6px 8px'>{$delivery_label}</td><td style='padding:6px 8px;text-align:right'>&euro; {$delInc}</td></tr>
  <tr><td style='padding:6px 8px;font-weight:700;border-top:1px solid #ccc'>" . T('total_inc', $L) . "</td><td style='padding:6px 8px;font-weight:700;border-top:1px solid #ccc;text-align:right'>&euro; {$totInc}</td></tr>
  </tbody>
</table>";
    }

    // Legacy v1: original single-product line
    $specs   = htmlspecialchars($order['specs'] ?? '');
    $artwork = htmlspecialchars($order['artwork_filename'] ?? '');
    $inc     = number_format((float)($order['amount_inc'] ?? 0), 2, ',', "'");
    $artworkBlock = $artwork !== '' ? "<p style='margin:0 0 12px'><strong>Artwork:</strong> {$artwork}" . ($is_internal ? " (in /orders/{$inv}/artwork/)" : ' — received') . "</p>" : '';
    return "
<p style='margin:0'><strong>Order:</strong> {$specs}</p>
<p style='margin:0'><strong>Amount:</strong> &euro; {$inc} incl. VAT</p>
{$artworkBlock}";
}

// ── Build HTML email body ─────────────────────────────────────────────────
function build_email_html($order, $is_internal = false) {
    $inv      = htmlspecialchars($order['invoice_num'] ?? '');
    $name     = htmlspecialchars($order['customer_name'] ?? 'Client');
    $ship_addr = htmlspecialchars(trim(($order['ship_address'] ?? '') . ', ' . ($order['ship_zip'] ?? '') . ' ' . ($order['ship_city'] ?? ''), ", \t\n\r\0\x0B"));
    $date     = date('d/m/Y');
    $doc_type = $order['doc_type'] ?? 'receipt';
    $doc_label = $doc_type === 'invoice' ? 'Invoice' : 'Receipt';
    $deliveryLabel = (($order['delivery'] ?? 'no') === 'yes' && $ship_addr) ? $ship_addr : "Pick-up at our shop";
    $itemsHtml = build_items_html($order, $is_internal);

    // Last possible production deadline, fixed by pay.php when the order was
    // created. Older orders (schema < 2.1) have no such field — say so rather
    // than recompute, because recomputing now would give today's date, not the
    // date this customer was actually promised.
    $deadlineLabel = 'not recorded (order predates deadline tracking)';
    if (!empty($order['production_deadline'])) {
        $ts = strtotime($order['production_deadline']);
        $deadlineLabel = htmlspecialchars(date('D d/m/Y', $ts))
            . ' &nbsp;<span style="color:#888">(' . (int)($order['production_days'] ?? 0)
            . ' working days' . (!empty($order['express']) ? ', EXPRESS' : '') . ')</span>';
    }

    if ($is_internal) {
        return "
<div style='font-family:Arial,sans-serif;max-width:600px;font-size:14px;color:#222;line-height:1.6;'>
  <p style='margin:0 0 12px'>Hello,</p>
  <p style='margin:0 0 12px'>You have a new order paid online.</p>
  <p style='margin:0'><strong>{$doc_label}:</strong> {$inv} &nbsp;|&nbsp; {$date}</p>
  <p style='margin:0 0 12px'><strong>Customer:</strong> {$name}</p>
  {$itemsHtml}
  <p style='margin:0'><strong>Delivery:</strong> {$deliveryLabel}</p>
  <p style='margin:0'><strong>Production deadline:</strong> {$deadlineLabel}</p>
  <p style='margin:12px 0 12px'>The {$doc_label} PDF is attached to this email.</p>
  <p style='margin:0'>Many thanks for your order.<br><strong>Julien and Kris</strong></p>
  <hr style='border:none;border-top:1px solid #eee;margin:16px 0'>
  <p style='font-size:11px;color:#999;margin:0;'>Click Click Graphics SRL &middot; Aalststraat 7-11, Brussels &middot; +32 2 880 70 36 &middot; BE0732.984.161</p>
</div>";
    }

    $L = order_lang($order);
    $docL = $doc_type === 'invoice' ? T('invoice_l', $L) : T('receipt_l', $L);
    $deliveryLabel = (($order['delivery'] ?? 'no') === 'yes' && $ship_addr) ? $ship_addr : T('pickup_shop', $L);
    $mail = "<a href='mailto:info@clickclickgraphics.com'>info@clickclickgraphics.com</a>";
    $colon = $L === 'fr' ? ' :' : ':';
    return "
<div style='font-family:Arial,sans-serif;max-width:600px;font-size:14px;color:#222;line-height:1.6;'>
  <p style='margin:0 0 12px'>" . T('hello', $L) . " {$name},</p>
  <p style='margin:0 0 12px'>" . sprintf(T('thanks', $L), $docL) . "</p>
  {$itemsHtml}
  <p style='margin:0 0 12px'><strong>" . T('delivery_c', $L) . "{$colon}</strong> {$deliveryLabel}</p>
  <p style='margin:0 0 12px'>" . sprintf(T('questions', $L), $mail) . "</p>
  <p style='margin:0'>" . T('bye', $L) . "</p>
  <hr style='border:none;border-top:1px solid #eee;margin:16px 0'>
  <p style='font-size:11px;color:#999;margin:0;'>Click Click Graphics SRL &middot; Aalststraat 7-11, Brussels &middot; +32 2 880 70 36 &middot; BE0732.984.161</p>
</div>";
}

// ── Webhook entry point ───────────────────────────────────────────────────
$payment_id = $_POST['id'] ?? null;
if (!$payment_id) {
    audit_log('webhook.invalid', ['reason' => 'no_payment_id']);
    http_response_code(400);
    exit('No payment ID');
}

$payment = mollie_get('payments/' . $payment_id);
if (!isset($payment['id'])) {
    audit_log('webhook.invalid', ['reason' => 'mollie_lookup_failed', 'payment_id' => substr((string)$payment_id, 0, 20)]);
    http_response_code(400);
    exit('Invalid payment');
}

$status      = $payment['status'];
$is_test     = ($payment['mode'] ?? '') === 'test';
$subj_tag    = $is_test ? '[TEST] ' : '';
$metadata    = $payment['metadata'] ?? [];
$invoice_num = $metadata['invoice_num'] ?? 'UNKNOWN';
$order_file  = ORDERS_DIR . $invoice_num . '.json';

$order = file_exists($order_file)
    ? json_decode(file_get_contents($order_file), true)
    : [];

if (($order['status'] ?? '') === 'paid' && $status === 'paid') {
    audit_log('webhook.replay', ['invoice' => $invoice_num]);
    http_response_code(200); exit('Already processed');
}

audit_log('webhook.status', [
    'invoice'   => $invoice_num,
    'mollie_id' => $payment['id'],
    'status'    => $status,
]);

$order['status']     = $status;
$order['updated_at'] = date('c');
$order['test_mode']  = $is_test;
$order['mollie_id']  = $payment['id'];

foreach ($metadata as $k => $v) {
    if (!isset($order[$k])) $order[$k] = $v;
}
file_put_contents($order_file, json_encode($order, JSON_PRETTY_PRINT));

// ── On successful payment ─────────────────────────────────────────────────
if ($status === 'paid') {
    $amount_inc = (float)$payment['amount']['value'];
    // pay.php already worked out the excl.-VAT amount per item (21 % for all products today).
    $amount_ex  = isset($order['amount_ex']) ? (float)$order['amount_ex'] : round($amount_inc / 1.21, 2);

    $order['amount_inc'] = $amount_inc;
    $order['amount_ex']  = $amount_ex;
    $order['paid_at']    = date('c');

    // ── Move staged artwork into the per-invoice folder ───────────────────
    // For schema_version 2 orders this writes per-item paths back onto $order['items'][*].
    $artwork_paths = finalize_all_artwork($order);
    if (!empty($artwork_paths)) {
        $order['artwork_paths'] = $artwork_paths;
        // Keep a single string field too for older display code / emails.
        $order['artwork_path']  = $artwork_paths[0];
    }

    file_put_contents($order_file, json_encode($order, JSON_PRETTY_PRINT));

    // ── Generate PDF ──────────────────────────────────────────────────────
    $pdf_bytes    = null;
    $doc_type     = $order['doc_type'] ?? 'receipt';
    $doc_label    = $doc_type === 'invoice' ? 'Invoice' : 'Receipt';
    $pdf_filename = $doc_label . '_' . $invoice_num . '.pdf';
    if ($invoice_available && function_exists('generate_invoice')) {
        try {
            $pdf_bytes = generate_invoice($order);
            file_put_contents(ORDERS_DIR . $pdf_filename, $pdf_bytes);
        } catch (Throwable $e) {
        }
    }

    // ── Email to customer ─────────────────────────────────────────────────
    $customer_email = $order['customer_email'] ?? '';
    if ($customer_email) {
        send_email(
            $customer_email,
            $subj_tag . 'Click Click Graphics — ' . T($doc_type === 'invoice' ? 'invoice' : 'receipt', order_lang($order)) . ' ' . $invoice_num,
            build_email_html($order, false),
            $pdf_bytes,
            $pdf_filename
        );
    }

    // ── Email to WinAuditor (accountant) — real payments only ──────────────
    if (!$is_test) {
        $wa_email = WINAUDITOR_EMAIL;
        $wa_subject = '=?UTF-8?B?' . base64_encode($doc_label . ' ' . $invoice_num . ' - Click Click Graphics') . '?=';
        send_email(
            $wa_email,
            $wa_subject,
            '<p>Facture automatique. Montant HTVA: EUR ' . number_format($amount_ex, 2) . ' / TVA incl.: EUR ' . number_format($amount_inc, 2) . '</p>',
            $pdf_bytes,
            $pdf_filename
        );
    }

    // ── Internal notification to Kris ─────────────────────────────────────
    send_email(
        COMPANY_EMAIL,
        $subj_tag . 'Order paid — ' . $doc_label . ' ' . $invoice_num,
        build_email_html($order, true),
        $pdf_bytes,
        $pdf_filename
    );

    // ── Log ───────────────────────────────────────────────────────────────
    $log = implode(' | ', [date('c'), $is_test ? 'TEST' : 'LIVE', $invoice_num, '€' . number_format($amount_inc, 2), $order['customer_name'] ?? '?', $order['customer_email'] ?? '?']) . "\n";
    file_put_contents(ORDERS_DIR . 'paid_orders.log', $log, FILE_APPEND);
}

http_response_code(200);
exit('OK');
