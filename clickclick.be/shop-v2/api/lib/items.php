<?php
/**
 * Shop v2 — cart-item validation, shared by calc.php and pay.php.
 *
 * Everything an item may contain is checked against the price file (Products tab):
 * product active, size / paper / fold offered for that product, sides offered,
 * quantity between 1 and the product's max, versions 1–10. The shop UI never
 * offers anything else, but the UI can be bypassed — this is the authority.
 */

declare(strict_types=1);

const ITEM_MAX_COUNT     = 10;
const ITEM_MAX_VERSIONS  = 10;
const ITEM_DEFAULT_MAXQ  = 10000;
const ITEM_ALLOWED_DELIV = ['pickup', 'dpd'];
const ITEM_UUID_REGEX    = '/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/';

function validateItem(array $raw, array $d): array
{
    $product = (string)($raw['product'] ?? '');
    $size    = (string)($raw['size'] ?? '');
    $paper   = (string)($raw['paper'] ?? '');
    $fold    = (string)($raw['fold'] ?? '');
    $sides   = (string)($raw['sides'] ?? 'double');
    $qty     = (int)($raw['qty'] ?? 0);
    $versions = (int)($raw['versions'] ?? 1);
    $pages   = (int)($raw['pages'] ?? 0);
    $cover   = (string)($raw['cover'] ?? '');

    $p = $d['products'][$product] ?? null;
    if (!$p || !$p['active']) return ['ok' => false, 'error' => 'invalid product'];
    if (!in_array($size, $p['sizes'], true)) return ['ok' => false, 'error' => 'size not available for this product'];
    if (!in_array($paper, $p['papers'], true) || ($d['papers'][$paper]['active'] ?? '') !== 'yes')
        return ['ok' => false, 'error' => 'paper not available for this product'];
    if ($p['folds']) {
        if (!in_array($fold, $p['folds'], true) || !isset($d['folds']["$fold|$size"])) return ['ok' => false, 'error' => 'invalid fold'];
    } else {
        $fold = '';
    }
    if (!in_array($sides, $p['sides'], true)) return ['ok' => false, 'error' => 'sides not available for this product'];
    $maxQ = $p['maxQty'] ?? ITEM_DEFAULT_MAXQ;
    $minQ = $p['minQty'] ?? 1;
    if ($qty < $minQ || $qty > $maxQ) return ['ok' => false, 'error' => 'qty out of range'];
    if ($versions < 1 || $versions > ITEM_MAX_VERSIONS) return ['ok' => false, 'error' => 'versions out of range'];
    if (!empty($p['pages'])) {
        // brochure: page count from the list, one version, cover = a 250 g of the SAME paper line as
        // the inside paper (Kris, 17 Oct: mixing paper lines is a special request, not in the shop)
        if (!in_array($pages, $p['pages'], true)) return ['ok' => false, 'error' => 'invalid page count'];
        if ($versions !== 1) return ['ok' => false, 'error' => 'brochures: one version per item'];
        if (!in_array($cover, $p['coverPapers'], true) || ($d['papers'][$cover]['active'] ?? '') !== 'yes')
            return ['ok' => false, 'error' => 'cover paper not available'];
        if (explode('-', $cover)[0] !== explode('-', $paper)[0]) return ['ok' => false, 'error' => 'cover and inside must be the same paper'];
    } else {
        $pages = 0; $cover = '';
    }
    $id = (string)($raw['id'] ?? '');
    if ($id !== '' && !preg_match(ITEM_UUID_REGEX, $id)) return ['ok' => false, 'error' => 'invalid item id'];

    return ['ok' => true, 'item' => [
        'id' => $id, 'product' => $product, 'size' => $size, 'paper' => $paper, 'fold' => $fold,
        'sides' => $sides, 'qty' => $qty, 'versions' => $versions, 'pages' => $pages, 'cover' => $cover,
    ]];
}

function validateItems(array $rawItems, array $d): array
{
    if (empty($rawItems)) return ['ok' => false, 'error' => 'no items'];
    if (count($rawItems) > ITEM_MAX_COUNT) return ['ok' => false, 'error' => 'too many items (max ' . ITEM_MAX_COUNT . ')'];
    $items = [];
    foreach (array_values($rawItems) as $i => $raw) {
        if (!is_array($raw)) return ['ok' => false, 'error' => 'item not an object', 'item_index' => $i];
        $r = validateItem($raw, $d);
        if (!$r['ok']) return ['ok' => false, 'error' => $r['error'], 'item_index' => $i];
        $items[] = $r['item'];
    }
    return ['ok' => true, 'items' => $items];
}

/** Price one validated item (pickup — delivery is added once per order). */
function priceItem(array $item, bool $express, array $d): array
{
    $r = ccp_price($d, [
        'product' => $item['product'], 'size' => $item['size'], 'paperId' => $item['paper'], 'qty' => $item['qty'],
        'sides' => $item['sides'], 'versions' => $item['versions'], 'express' => $express,
        'fold' => $item['fold'] !== '' ? $item['fold'] : null, 'delivery' => false, 'ink' => 'cmyk',
        'pages' => $item['pages'] ?: null, 'coverPaperId' => ($item['cover'] ?? '') !== '' ? $item['cover'] : null,
    ]);
    $pieces = max(1, $item['qty'] * $item['versions']);
    $vat = $d['products'][$item['product']]['vat'] ?? null;
    return ['inclVat' => $r['incl'], 'exVat' => $r['excl'], 'vatRate' => $vat ?? (float)$d['rules']['vat_rate'], 'perPieceIncl' => round($r['incl'] / $pieces, 4),
            'perPieceEx' => round($r['excl'] / $pieces, 4), 'qty' => $item['qty'], 'versions' => $item['versions'], 'totalPieces' => $pieces];
}

/** "Folder · A4 → 99 x 210 mm (DL), 6 pages — A4 zigzag fold · MultiArt Silk 170 gsm · Recto & Verso · 250"
 *  "Brochure (saddle stitched) · A4 · 16 pages · cover MultiArt Silk 250 gsm · inside MultiArt Silk 130 gsm · Recto & Verso · 100" */
function itemSpecsLine(array $item, array $d): string
{
    $p = $d['products'][$item['product']] ?? ['name' => $item['product']];
    $pa = $d['papers'][$item['paper']] ?? [];
    $sz = $d['sizes'][$item['size']] ?? [];
    $size = ($sz['name'] ?? $item['size']);
    if ($item['fold'] !== '') {
        $ft = $d['folds'][$item['fold'] . '|' . $item['size']] ?? [];
        $size .= ' → ' . ($ft['name in the shop (EN)'] ?? $item['fold']);
    }
    $paperTxt = trim(($pa['name (shown to customers)'] ?? $item['paper']) . ' ' . ($pa['gsm'] ?? '') . ' gsm');
    if (!empty($item['pages'])) {
        $cv = $d['papers'][$item['cover']] ?? [];
        $size .= ' · ' . $item['pages'] . ' pages';
        $paperTxt = 'cover ' . trim(($cv['name (shown to customers)'] ?? $item['cover']) . ' ' . ($cv['gsm'] ?? '') . ' gsm')
                  . ' · inside ' . $paperTxt;
    }
    $parts = [
        $p['name'], $size,
        $paperTxt,
        $item['sides'] === 'double' ? 'Recto & Verso' : 'Recto',
        $item['qty'] . ($item['versions'] > 1 ? ' × ' . $item['versions'] . ' versions' : ''),
    ];
    return implode(' · ', $parts);
}

function readOrderEnvelope(array $src): array
{
    $delivery = (string)($src['delivery'] ?? 'pickup');
    if (!in_array($delivery, ITEM_ALLOWED_DELIV, true)) $delivery = 'pickup';
    return [
        'express'  => !empty($src['express']) && $src['express'] !== '0' && $src['express'] !== false,
        'delivery' => $delivery,
    ];
}
