<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Click Click Graphics</title>
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
$L = in_array(($order['lang'] ?? ($_GET['lang'] ?? '')), ['nl', 'fr', 'en'], true) ? ($order['lang'] ?? $_GET['lang']) : 'en';
$S = [
  'title'   => ['en' => 'Payment confirmed', 'nl' => 'Betaling bevestigd', 'fr' => 'Paiement confirmé'],
  'h_ok'    => ['en' => 'Payment confirmed!', 'nl' => 'Betaling bevestigd!', 'fr' => 'Paiement confirmé !'],
  'receipt' => ['en' => 'Receipt', 'nl' => 'Ontvangstbewijs', 'fr' => 'Reçu'],
  'invoice' => ['en' => 'Invoice', 'nl' => 'Factuur', 'fr' => 'Facture'],
  'thanks'  => ['en' => "Thank you for your order. You'll receive a confirmation email shortly.",
                'nl' => 'Bedankt voor je bestelling. Je ontvangt zo meteen een bevestigingsmail.',
                'fr' => 'Merci pour votre commande. Vous recevrez sous peu un e-mail de confirmation.'],
  'got1'    => ['en' => 'Your file is received:', 'nl' => 'We hebben je bestand ontvangen:', 'fr' => 'Nous avons bien reçu votre fichier :'],
  'gotN'    => ['en' => 'Your files are received:', 'nl' => 'We hebben je bestanden ontvangen:', 'fr' => 'Nous avons bien reçu vos fichiers :'],
  'review1' => ['en' => "We'll review your file and start production within one business day.",
                'nl' => 'We controleren je bestand en starten de productie binnen één werkdag.',
                'fr' => 'Nous vérifions votre fichier et lançons la production sous un jour ouvrable.'],
  'reviewN' => ['en' => "We'll review your files and start production within one business day.",
                'nl' => 'We controleren je bestanden en starten de productie binnen één werkdag.',
                'fr' => 'Nous vérifions vos fichiers et lançons la production sous un jour ouvrable.'],
  'nofile_h'=> ['en' => 'No artwork received yet.', 'nl' => 'Nog geen bestand ontvangen.', 'fr' => "Aucun fichier reçu pour l'instant."],
  'nofile'  => ['en' => 'Please reply to your confirmation email with your print file (PDF, JPG, PNG or TIFF) so we can start production.',
                'nl' => 'Antwoord op je bevestigingsmail met je drukbestand (PDF, JPG, PNG of TIFF) zodat we kunnen starten met de productie.',
                'fr' => "Répondez à votre e-mail de confirmation avec votre fichier d'impression (PDF, JPG, PNG ou TIFF) afin que nous puissions lancer la production."],
  'h_wait'  => ['en' => 'Processing your payment…', 'nl' => 'Je betaling wordt verwerkt…', 'fr' => 'Votre paiement est en cours de traitement…'],
  'wait'    => ['en' => 'Your payment is being confirmed. This page will update in a few seconds.',
                'nl' => 'Je betaling wordt bevestigd. Deze pagina wordt binnen enkele seconden bijgewerkt.',
                'fr' => 'Votre paiement est en cours de confirmation. Cette page se mettra à jour dans quelques secondes.'],
  'q'       => ['en' => 'Questions?', 'nl' => 'Vragen?', 'fr' => 'Des questions ?'],
  'test'    => ['en' => '<strong>TEST ORDER</strong> — paid with a Mollie test payment. No real money, not sent to the accountant.',
                'nl' => '<strong>TESTBESTELLING</strong> — betaald met een Mollie-testbetaling. Geen echt geld, niet naar de boekhouder gestuurd.',
                'fr' => "<strong>COMMANDE TEST</strong> — payée avec un paiement test Mollie. Pas d'argent réel, non envoyée au comptable."],
];
$tr = fn(string $k) => $S[$k][$L] ?? $S[$k]['en'];
$dbg = [];   // shown on the page for TEST orders only

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
    $dbg[] = 'Mollie says: status=' . ($payment['status'] ?? ('no status — ' . substr((string)$response, 0, 160))) . ', mode=' . ($payment['mode'] ?? '?');

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
        $whBody = curl_exec($wh);
        $dbg[] = 'Webhook answered: HTTP ' . curl_getinfo($wh, CURLINFO_HTTP_CODE) . ' ' . substr(trim(strip_tags((string)$whBody)), 0, 200) . ($whBody === false ? ' / curl error: ' . curl_error($wh) : '');
        curl_close($wh);

        // Re-read the order file (webhook may have updated it)
        $order = file_exists($order_file)
            ? json_decode(file_get_contents($order_file), true)
            : $order;
        $is_paid = ($order['status'] ?? '') === 'paid';
        $dbg[] = 'Order file status after webhook: ' . ($order['status'] ?? '?');
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
    <?php if (!empty($order['test_mode'])): ?>
      <div class="fallback" style="margin-top:0"><?= $tr('test') ?></div>
    <?php endif; ?>
    <?php if ($is_paid): ?>
      <div class="icon">✅</div>
      <h1><?= $tr('h_ok') ?></h1>
      <?php
        $doc_label = $tr(($order['doc_type'] ?? 'receipt') === 'invoice' ? 'invoice' : 'receipt');
      ?>
      <?php if ($invoice_num): ?>
        <div class="invoice"><?= $doc_label ?> <?= $invoice_num ?></div>
      <?php endif; ?>
      <p><?= $tr('thanks') ?></p>

      <?php if ($has_any_files): ?>
        <div class="artwork">
          <strong>📎 <?= $tr($total_file_count === 1 ? 'got1' : 'gotN') ?></strong>
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
        <p><?= $tr($total_file_count === 1 ? 'review1' : 'reviewN') ?></p>
      <?php else: ?>
        <div class="fallback">
          <strong><?= $tr('nofile_h') ?></strong><br>
          <?= $tr('nofile') ?>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="icon">⏳</div>
      <h1><?= $tr('h_wait') ?></h1>
      <?php if ($invoice_num): ?>
        <div class="invoice"><?= $tr(($order['doc_type'] ?? 'receipt') === 'invoice' ? 'invoice' : 'receipt') ?> <?= $invoice_num ?></div>
      <?php endif; ?>
      <p><?= $tr('wait') ?></p>
      <?php if (!empty($order['test_mode']) && $dbg): ?>
        <pre style="text-align:left;font-size:11px;white-space:pre-wrap;background:#f4f4f4;padding:10px;border-radius:8px;color:#444"><?= htmlspecialchars(implode("\n", $dbg)) ?></pre>
      <?php endif; ?>
      <script>setTimeout(function(){ location.reload(); }, 4000);</script>
    <?php endif; ?>

    <div class="contact">
      <?= $tr('q') ?> <a href="mailto:info&#64;clickclickgraphics.com">info&#64;clickclickgraphics.com</a><br>
      +32 2 880 70 36 &middot; Aalststraat 7-11, 1000 Brussels
    </div>

    <div class="logo">Click Click Graphics</div>
  </div>
<script>document.documentElement.lang=<?= json_encode($L) ?>;document.title=<?= json_encode(($is_paid ? $S['title'][$L] : $S['h_wait'][$L]) . ' — Click Click Graphics') ?>;</script>
</body>
</html>
