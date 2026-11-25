<?php
/**
 * Receipt PDF generator — Click Click Graphics.
 *
 * Builds a one-page A4 receipt using FPDF (no composer / no remote deps).
 * The English layout is the master; FR + NL appear as small grey alt-lines
 * (~7pt) under the EN labels and money-line headings — done explicitly per
 * customer request so accountants and bilingual buyers can read the receipt
 * at a glance regardless of language.
 *
 * Public entry: generate_invoice($order) → returns PDF bytes.
 */

require_once __DIR__ . '/fpdf.php';

class ReceiptPDF extends FPDF {
  private $accent = [61, 0, 255];   // Click Click blue
  private $altGrey = [154, 146, 135]; // small-print grey for FR/NL alt-lines

  // FPDF only natively handles ISO-8859-1. UTF-8 strings get transliterated.
  private function u($str) {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', (string)$str);
  }

  // Print a small grey FR/NL alt-line at (x, y) and return the y advance used.
  private function altLine($x, $y, $w, $text, $align = 'L', $h = 3.2) {
    $this->SetFont('Helvetica', 'I', 7);
    $this->SetTextColor(...$this->altGrey);
    $this->SetXY($x, $y);
    $this->Cell($w, $h, $this->u($text), 0, 1, $align);
  }

  function Header() {}
  function Footer() {}

  function generate($order) {
    $this->AddPage();
    $this->SetMargins(18, 15, 18);
    $this->SetAutoPageBreak(false);
    $pageW = $this->GetPageWidth();
    $pageH = $this->GetPageHeight();

    // ── LOGO (top left) ───────────────────────────────────────────────────
    $logo = __DIR__ . '/Logo.jpg';
    if (file_exists($logo)) {
      $this->Image($logo, 18, 10, 42);
    } else {
      $this->SetFont('Helvetica', 'B', 16);
      $this->SetTextColor(...$this->accent);
      $this->SetXY(18, 14);
      $this->Cell(0, 7, 'CLICK CLICK GRAPHICS', 0, 1, 'L');
    }

    // ── RECEIPT title (top right) + FR/NL alt + invoice number / date ────
    $this->SetFont('Helvetica', 'B', 28);
    $this->SetTextColor(20, 20, 20);
    $this->SetXY(110, 13);
    $this->Cell(82, 12, 'RECEIPT', 0, 1, 'R');

    // Tri-lingual subtitle directly under the big title
    $this->altLine(110, 25, 82, 'Recu  .  Ontvangstbewijs', 'R');

    $this->SetFont('Helvetica', '', 9);
    $this->SetTextColor(100, 100, 100);
    $this->SetXY(110, 30);
    $invoice_date = isset($order['created_at']) ? date('d/m/Y', strtotime($order['created_at'])) : date('d/m/Y');
    $this->Cell(82, 5, $order['invoice_num'] . '   |   ' . $invoice_date, 0, 1, 'R');

    // ── Blue divider ─────────────────────────────────────────────────────
    $divY = 57;
    $this->SetDrawColor(...$this->accent);
    $this->SetLineWidth(0.6);
    $this->Line(18, $divY, $pageW - 18, $divY);
    $this->SetLineWidth(0.2);
    $this->SetDrawColor(220, 220, 220);

    // ── FROM block (left) ────────────────────────────────────────────────
    $fy = $divY + 6;
    $this->SetFont('Helvetica', 'B', 7.5);
    $this->SetTextColor(...$this->accent);
    $this->SetXY(18, $fy);
    $this->Cell(0, 4.5, 'FROM', 0, 1, 'L');
    $this->altLine(18, $fy + 4, 80, 'De  .  Van', 'L');
    $fy += 8;
    $this->SetFont('Helvetica', 'B', 8);
    $this->SetTextColor(20, 20, 20);
    $this->SetXY(18, $fy);
    $this->Cell(0, 4.5, 'Click Click Graphics SRL', 0, 1, 'L');
    $fy += 4.5;
    $this->SetFont('Helvetica', '', 7.5);
    $this->SetTextColor(80, 80, 80);
    foreach (['Aalststraat 7-11', '1000 Brussels', 'BE0732.984.161', 'info@clickclickgraphics.com'] as $line) {
      $this->SetXY(18, $fy);
      $this->Cell(0, 4, $line, 0, 1, 'L');
      $fy += 4;
    }

    // ── TO block (right) ─────────────────────────────────────────────────
    $ty2 = $divY + 6;
    $this->SetFont('Helvetica', 'B', 7.5);
    $this->SetTextColor(...$this->accent);
    $this->SetXY(115, $ty2);
    $this->Cell(75, 4.5, 'TO', 0, 1, 'L');
    $this->altLine(115, $ty2 + 4, 75, 'A  .  Aan', 'L');
    $ty2 += 8;
    $this->SetFont('Helvetica', 'B', 8);
    $this->SetTextColor(20, 20, 20);
    $this->SetXY(115, $ty2);
    $this->Cell(75, 4.5, $this->u($order['customer_name'] ?? ''), 0, 1, 'L');
    $ty2 += 4.5;
    $addr_fields = array_filter([
      $order['customer_company'] ?? '',
      $order['customer_address'] ?? '',
      trim(($order['customer_zip'] ?? '') . ' ' . ($order['customer_city'] ?? '')),
      $order['customer_email'] ?? '',
      $order['customer_phone'] ?? '',
      !empty($order['vat_number']) ? 'VAT: ' . $order['vat_number'] : '',
    ]);
    $this->SetFont('Helvetica', '', 7.5);
    $this->SetTextColor(80, 80, 80);
    foreach ($addr_fields as $field) {
      $this->SetXY(115, $ty2);
      $this->Cell(75, 4, $this->u($field), 0, 1, 'L');
      $ty2 += 4;
    }

    // ── Items table ──────────────────────────────────────────────────────
    $tableTop = $divY + 46;
    $c1 = 104; $c2 = 18; $c3 = 52;

    // Header row (purple band, white text)
    $this->SetFillColor(...$this->accent);
    $this->SetTextColor(255, 255, 255);
    $this->SetFont('Helvetica', 'B', 8);
    $this->SetXY(18, $tableTop);
    $this->Cell($c1, 7, 'Description', 0, 0, 'L', true);
    $this->Cell($c2, 7, 'VAT', 0, 0, 'C', true);
    $this->Cell($c3, 7, 'Amount excl. VAT', 0, 1, 'R', true);

    // Tri-lingual sub-headers (small grey, just under the header band)
    $this->SetFont('Helvetica', 'I', 7);
    $this->SetTextColor(...$this->altGrey);
    $this->SetXY(18, $tableTop + 7.2);
    $this->Cell($c1, 3.5, $this->u('Description  .  Omschrijving'), 0, 0, 'L');
    $this->Cell($c2, 3.5, $this->u('TVA  .  BTW'),                  0, 0, 'C');
    $this->Cell($c3, 3.5, $this->u('Montant HT  .  Bedrag excl. BTW'), 0, 1, 'R');

    // ── Parse specs (fix label/value alignment) ──────────────────────────
    // The frontend sends specs as " · "-joined parts, e.g.:
    //   "Flyer · A6 · 105×148 mm · MultiArt Silk · 130 gsm · Full colour (CMYK) · Recto only · Express 48h · Pick-up"
    // We parse content-aware so dimensions merge into Format and gsm merges into Paper.
    $specs = $order['specs'] ?? '';
    $spec_parts = explode(' . ', str_replace(' · ', ' . ', $specs));
    $product_title = trim((string)array_shift($spec_parts));

    // Build canonical list of [label, value] rows.
    $rows = [];
    $add = function($label, $value) use (&$rows) {
      $value = trim($value);
      if ($value === '') return;
      // Append to existing row of same label (e.g. Format dims, Paper gsm)
      foreach ($rows as $i => $r) {
        if ($r[0] === $label) {
          $rows[$i][1] = trim($r[1] . ' . ' . $value);
          return;
        }
      }
      $rows[] = [$label, $value];
    };

    $qty_str = '';
    foreach ($spec_parts as $part) {
      $part = trim($part);
      if ($part === '') continue;

      // Quantity ("500 copies", "1000 ex.", "250 stuks") — captured separately
      if (preg_match('/^\d[\d\s.,]*\s*(copies|ex\.|stuks)$/i', $part)) {
        $qty_str = $part;
        continue;
      }
      // Format dimensions: "105×148 mm", "210x297 mm"
      if (preg_match('/^\d+\s*[x\xD7]\s*\d+/i', $part)) {
        $add('Format', $part);
        continue;
      }
      // Paper weight: "130 gsm", "300gsm"
      if (preg_match('/^\d+\s*gsm$/i', $part)) {
        $add('Paper', $part);
        continue;
      }
      // Print mode: anything that mentions colour / CMYK / black / white
      if (preg_match('/(CMYK|colour|color|noir|wit|zwart|blanc)/i', $part)) {
        $add('Print', $part);
        continue;
      }
      // Sides
      if (preg_match('/^(Recto|Verso|Single|Double|Tweezijdig|Eenzijdig)/i', $part)) {
        $add('Sides', $part);
        continue;
      }
      // Service: express + delivery markers
      if (preg_match('/Express|Pick-?up|Delivery|Afhalen|Retrait|DPD|bike/i', $part)) {
        $add('Service', $part);
        continue;
      }
      // Format size (A6, BC, etc.) — single short alphanumeric
      if (preg_match('/^(BC|A[3-7])$/i', $part)) {
        $add('Format', $part);
        continue;
      }
      // Anything else → assume Paper (the only remaining slot in our schema)
      $add('Paper', $part);
    }

    // ── Render description block ─────────────────────────────────────────
    $ty = $tableTop + 13;
    $this->SetFont('Helvetica', 'B', 8.5);
    $this->SetTextColor(20, 20, 20);
    $this->SetXY(18, $ty);
    $this->Cell(0, 5.5, $this->u($product_title), 0, 1, 'L');
    $ty += 6;

    foreach ($rows as $r) {
      list($label, $value) = $r;
      $this->SetXY(22, $ty);
      $this->SetFont('Helvetica', 'B', 8);
      $this->SetTextColor(20, 20, 20);
      $this->Cell(22, 4.5, $this->u($label . ':'), 0, 0, 'L');
      $this->SetFont('Helvetica', '', 8);
      $this->SetTextColor(80, 80, 80);
      $this->Cell(0, 4.5, $this->u($value), 0, 1, 'L');
      $ty += 4.5;
    }

    // Quantity row: same line, three columns
    $amount_ex  = (float)($order['amount_ex']  ?? 0);
    $amount_inc = (float)($order['amount_inc'] ?? 0);
    $vat_amt    = $amount_inc - $amount_ex;

    $ty += 4;
    $this->SetDrawColor(200, 200, 200);
    $this->Line(18, $ty - 1, $pageW - 18, $ty - 1);
    $this->SetFont('Helvetica', 'B', 8.5);
    $this->SetTextColor(20, 20, 20);
    $this->SetXY(18, $ty);
    $this->Cell($c1, 6, $this->u($qty_str ? 'Quantity: ' . $qty_str : ''), 0, 0, 'L');
    $this->Cell($c2, 6, '21%', 0, 0, 'C');
    $this->Cell($c3, 6, 'EUR ' . number_format($amount_ex, 2, ',', "'"), 0, 1, 'R');
    $ty += 8;

    // ── Totals block ─────────────────────────────────────────────────────
    $this->SetDrawColor(...$this->accent);
    $this->SetLineWidth(0.4);
    $this->Line(18, $ty, $pageW - 18, $ty);
    $this->SetLineWidth(0.2);
    $ty += 5;

    $tw = 50; $tx = $pageW - 18 - $tw;

    // Each money line gets a small FR/NL alt under the EN label
    $totals = [
      ['Subtotal (excl. VAT):', 'Sous-total HT  .  Subtotaal excl. BTW',  number_format($amount_ex,  2, ',', "'"), false],
      ['VAT 21%:',              'TVA 21%  .  BTW 21%',                    number_format($vat_amt,    2, ',', "'"), false],
      ['TOTAL:',                'TOTAL  .  TOTAAL',                       number_format($amount_inc, 2, ',', "'"), true],
    ];
    foreach ($totals as $row) {
      list($label, $alt, $val, $bold) = $row;
      $this->SetFont('Helvetica', $bold ? 'B' : '', $bold ? 10 : 8.5);
      $this->SetTextColor(...($bold ? $this->accent : [80, 80, 80]));
      $this->SetXY($tx - 70, $ty);
      $this->Cell(70, 6, $label, 0, 0, 'R');
      $this->SetTextColor(20, 20, 20);
      $this->Cell($tw, 6, 'EUR ' . $val, 0, 1, 'R');
      // alt-line under the label, right-aligned in the same column
      $this->altLine($tx - 70, $ty + ($bold ? 5.5 : 5), 70, $alt, 'R');
      $ty += $bold ? 9 : 8;
    }

    // ── Payment note + thank-you block (each line tri-lingual) ───────────
    $ty += 6;
    $this->SetXY(18, $ty);
    $this->SetFont('Helvetica', 'I', 8);
    $this->SetTextColor(120, 120, 120);
    $this->Cell(0, 5, $this->u('Paid online via Mollie. / Paye en ligne via Mollie. / Betaald online via Mollie.'), 0, 1, 'L');
    $ty += 5;

    if (!empty($order['notes'])) {
      $this->SetXY(18, $ty);
      $this->Cell(0, 5, $this->u('Notes: ' . $order['notes']), 0, 1, 'L');
      $ty += 5;
    }

    $ty += 2;
    $this->SetXY(18, $ty);
    $this->SetFont('Helvetica', '', 8.5);
    $this->SetTextColor(60, 60, 60);
    $this->Cell(0, 5, $this->u('Your order will be ready for pick-up or shipped to your delivery address.'), 0, 1, 'L');
    $this->altLine(18, $ty + 4.5, 0, "Votre commande sera prete a l'enlevement ou expediee a votre adresse  .  Uw bestelling staat klaar voor afhaling of wordt naar uw leveradres verzonden.", 'L');
    $ty += 9.5;

    $this->SetXY(18, $ty);
    $this->SetFont('Helvetica', '', 8.5);
    $this->SetTextColor(60, 60, 60);
    $this->Cell(0, 5, $this->u('Many thanks for your order - we look forward to printing for you!'), 0, 1, 'L');
    $this->altLine(18, $ty + 4.5, 0, "Merci pour votre commande - au plaisir d'imprimer pour vous  .  Bedankt voor uw bestelling - wij drukken graag voor u.", 'L');
    $ty += 12;

    $this->SetXY(18, $ty);
    $this->SetFont('Helvetica', 'B', 9);
    $this->SetTextColor(20, 20, 20);
    $this->Cell(0, 5, 'Julien & Kris', 0, 1, 'L');

    // ── Footer pinned to bottom ──────────────────────────────────────────
    $footerY = $pageH - 16;
    $this->SetDrawColor(...$this->accent);
    $this->SetLineWidth(0.5);
    $this->Line(18, $footerY - 3, $pageW - 18, $footerY - 3);
    $this->SetFont('Helvetica', '', 6.5);
    $this->SetTextColor(130, 130, 130);
    $half = ($pageW - 36) / 2;
    $this->SetXY(18, $footerY);
    $this->Cell($half, 4, 'Click Click Graphics SRL  |  Aalststraat 7-11, 1000 Brussels', 0, 0, 'L');
    $this->Cell($half, 4, 'T +32 2 880 70 36  |  info@clickclickgraphics.com', 0, 1, 'R');
    $this->SetXY(18, $footerY + 4);
    $this->Cell($half, 4, $this->u('VAT / TVA / BTW BE0732.984.161  |  IBAN BE41 7360 6158 9610'), 0, 0, 'L');
    $this->Cell($half, 4, 'www.clickclickgraphics.com', 0, 1, 'R');

    return $this->Output('S');
  }
}

function generate_invoice($order) {
  $pdf = new ReceiptPDF('P', 'mm', 'A4');
  $pdf->SetCreator('Click Click Graphics');
  $pdf->SetAuthor('Click Click Graphics SRL');
  $pdf->SetTitle('Receipt ' . ($order['invoice_num'] ?? ''));
  return $pdf->generate($order);
}
