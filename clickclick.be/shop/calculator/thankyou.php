<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Payment confirmed — Click Click Graphics</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: #f7f7f7;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }
    .card {
      background: #fff;
      border-radius: 16px;
      padding: 48px 40px;
      max-width: 480px;
      width: 100%;
      text-align: center;
      box-shadow: 0 4px 24px rgba(0,0,0,0.08);
    }
    .icon {
      width: 64px;
      height: 64px;
      background: #e8f5e9;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 24px;
      font-size: 28px;
    }
    h1 {
      font-size: 24px;
      font-weight: 800;
      color: #0a0a0a;
      letter-spacing: -0.02em;
      margin-bottom: 12px;
    }
    .invoice {
      display: inline-block;
      background: #f0f0f0;
      color: #333;
      font-size: 13px;
      font-weight: 700;
      padding: 6px 14px;
      border-radius: 20px;
      margin-bottom: 24px;
      font-family: monospace;
    }
    p {
      font-size: 15px;
      color: #555;
      line-height: 1.7;
      margin-bottom: 16px;
    }
    .artwork {
      background: #ecfdf5;
      border: 1.5px solid #6ee7b7;
      border-radius: 10px;
      padding: 14px 18px;
      margin: 24px 0;
      font-size: 13px;
      color: #065f46;
    }
    .artwork strong { color: #064e3b; display: block; margin-bottom: 6px; }
    .artwork .files { list-style: none; padding: 0; margin: 0; text-align: left; }
    .artwork .file { font-family: monospace; word-break: break-all; padding: 2px 0; }
    .artwork .file::before { content: "✓ "; color: #10b981; }
    .fallback {
      background: #fef3c7;
      border: 1.5px solid #fcd34d;
      border-radius: 10px;
      padding: 14px 18px;
      margin: 24px 0;
      font-size: 13px;
      color: #78350f;
    }
    .contact {
      font-size: 12px;
      color: #999;
      margin-top: 24px;
      line-height: 1.8;
    }
    .contact a { color: #003DFF; text-decoration: none; }
    .logo {
      margin-top: 32px;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #ccc;
    }
  </style>
</head>
<body>
<?php
$secrets = @include __DIR__ . '/secrets.php';
if (!is_array($secrets) || empty($secrets['mollie_key'])) {
    error_log('thankyou.php: secrets.php missing or incomplete');
    echo '<p style="color:#c00;text-align:center;margin-top:2em">Service unavailable. Please contact <a href="mailto:shop@clickclickgraphics.com">shop@clickclickgraphics.com</a>.</p>';
    exit;
}
define('MOLLIE_API_KEY', $secrets['mollie_key']);
define('ORDERS_DIR', __DIR__ . '/orders/');

$invoice_num = htmlspecialchars($_GET['invoice'] ?? '');

// Check payment status from order file
$order_file = ORDERS_DIR . $invoice_num . '.json';
$order = file_exists($order_file)
    ? json_decode(file_get_contents($order_file), true)
    : null;

$is_paid = ($order['status'] ?? '') === 'paid';

// If not paid yet, check Mollie directly (handles test mode + slow webhooks)
if (!$is_paid && $order && !empty($order['mollie_id'])) {
    $ch = curl_init('https://api.mollie.com/v2/payments/' . $order['mollie_id']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . MOLLIE_API_KEY],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $payment = json_decode($response, true);

    if (($payment['status'] ?? '') === 'paid') {
        // Trigger webhook FIRST (before we update status to paid)
        // This way the webhook won't see "already processed" and will send emails.
        // Use the script's own directory so /calculator-v2/thankyou.php hits its own /calculator-v2/webhook.php.
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $wh = curl_init('https://' . $_SERVER['HTTP_HOST'] . $base . '/webhook.php');
        curl_setopt_array($wh, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query(['id' => $order['mollie_id']]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 15,
        ]);
        curl_exec($wh);
        curl_close($wh);

        // Re-read the order file (webhook may have updated it)
        $order = file_exists($order_file)
            ? json_decode(file_get_contents($order_file), true)
            : $order;
        $is_paid = ($order['status'] ?? '') === 'paid';
    }
}

// Build a flat list of files (legacy v1) AND a per-item grouping (v2).
$items_for_display = [];
$has_any_files = false;
if (!empty($order['items']) && is_array($order['items'])) {
    foreach ($order['items'] as $item) {
        $files = is_array($item['artwork_filenames'] ?? null) ? $item['artwork_filenames'] : [];
        if ($files) $has_any_files = true;
        $items_for_display[] = [
            'specs_line' => (string)($item['specs_line'] ?? ''),
            'files'      => $files,
        ];
    }
} else {
    $flat = isset($order['artwork_filenames']) && is_array($order['artwork_filenames'])
        ? $order['artwork_filenames']
        : (isset($order['artwork_filename']) && $order['artwork_filename'] !== '' ? [$order['artwork_filename']] : []);
    if ($flat) {
        $has_any_files = true;
        $items_for_display[] = [
            'specs_line' => (string)($order['specs'] ?? ''),
            'files'      => $flat,
        ];
    }
}
$total_file_count = array_sum(array_map(fn($it) => count($it['files']), $items_for_display));
?>
  <div class="card">
    <?php if ($is_paid): ?>
      <div class="icon">✅</div>
      <h1>Payment confirmed!</h1>
      <?php
        $doc_label = ($order['doc_type'] ?? 'receipt') === 'invoice' ? 'Invoice' : 'Receipt';
      ?>
      <?php if ($invoice_num): ?>
        <div class="invoice"><?= $doc_label ?> <?= $invoice_num ?></div>
      <?php endif; ?>
      <p>Thank you for your order. You'll receive a confirmation email shortly.</p>

      <?php if ($has_any_files): ?>
        <div class="artwork">
          <strong>📎 <?= $total_file_count === 1 ? 'Your file is received:' : 'Your files are received:' ?></strong>
          <?php foreach ($items_for_display as $g): if (!$g['files']) continue; ?>
            <?php if (count($items_for_display) > 1 && $g['specs_line'] !== ''): ?>
              <div style="margin-top:8px;font-weight:600;color:#064e3b;font-size:12px"><?= htmlspecialchars($g['specs_line']) ?></div>
            <?php endif; ?>
            <ul class="files">
              <?php foreach ($g['files'] as $f): ?>
                <li class="file"><?= htmlspecialchars($f) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endforeach; ?>
        </div>
        <p>We'll review your <?= $total_file_count === 1 ? 'file' : 'files' ?> and start production within one business day.</p>
      <?php else: ?>
        <div class="fallback">
          <strong>No artwork received yet.</strong><br>
          Please reply to your confirmation email with your print file (PDF, JPG, PNG or TIFF) so we can start production.
        </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="icon">⏳</div>
      <h1>Processing your payment…</h1>
      <?php if ($invoice_num): ?>
        <div class="invoice"><?= ($order['doc_type'] ?? 'receipt') === 'invoice' ? 'Invoice' : 'Receipt' ?> <?= $invoice_num ?></div>
      <?php endif; ?>
      <p>Your payment is being confirmed. This page will update in a few seconds.</p>
      <script>setTimeout(function(){ location.reload(); }, 4000);</script>
    <?php endif; ?>

    <div class="contact">
      Questions? <a href="mailto:info&#64;clickclickgraphics.com">info&#64;clickclickgraphics.com</a><br>
      +32 2 880 70 36 &middot; Aalststraat 7-11, 1000 Brussels
    </div>

    <div class="logo">Click Click Graphics</div>
  </div>
</body>
</html>
