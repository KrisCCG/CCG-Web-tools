<?php
/**
 * Shared cart-item validation + helpers.
 *
 * The /flyers/ frontend sends an array of items to calc.php and pay.php.
 * Both endpoints need to validate items the same way and turn each item
 * into computePrice() input. Centralizing that here means a single source
 * of truth for the schema.
 */

declare(strict_types=1);

const ITEM_MAX_COUNT       = 10;
const ITEM_ALLOWED_PRODUCT = ['flyer', 'card', 'poster'];
const ITEM_ALLOWED_SIZE    = ['BC', 'A7', 'A6', 'A5', 'A4', 'A3', 'LP'];

/**
 * Which sizes each product may actually be ordered in — mirrors
 * flyers/index.html PRODUCTS[x].sizes.
 *
 * Without this, ITEM_ALLOWED_SIZE is global and a request could ask for a
 * "flyer" at A3, or at 300x600. The shop UI never offers those, so such an
 * order deep-links into the shop, finds no matching size, and silently falls
 * back to the product default — the customer then buys something other than
 * what was quoted. That exact bug already happened with papers on 17 Aug 2026
 * ("card on Biotop 250" arrived as MultiArt Silk 130); this is the same guard
 * for sizes.
 */
const ITEM_SIZE_BY_PRODUCT = [
    'flyer'  => ['A6', 'A5'],
    'card'   => ['BC', 'A7', 'A6', 'A5', 'A4'],
    'poster' => ['A4', 'A3', 'LP'],
];
const ITEM_ALLOWED_SIDES   = ['single', 'double'];
const ITEM_ALLOWED_DELIV   = ['pickup', 'dpd'];
const ITEM_UUID_REGEX      = '/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/';

const PRODUCT_LABEL = [
    'flyer'  => 'Flyer',
    'card'   => 'Card',
    'poster' => 'Poster',
];
// Sizes print as their own code ("A6", "A3") except where that is not
// self-explanatory. Anything missing here falls back to the raw code.
const SIZE_LABEL = [
    'BC' => 'Business Card 85×55 mm',
    'LP' => 'Tall poster 300×600 mm',
];
const PAPER_LABEL = [
    'multiart' => 'MultiArt Silk',
    'biotop'   => 'Biotop',
    'recystar' => 'Recystar',
];

/**
 * Validate one raw cart item from the client. Returns ['ok'=>true,'item'=>normalized]
 * or ['ok'=>false,'error'=>'msg'].
 *
 * Does NOT validate upload_id — that lives in pay.php only (calc.php doesn't need it).
 */
function validateItem(array $raw): array
{
    $product   = (string)($raw['product']   ?? '');
    $paper_key = (string)($raw['paper_key'] ?? '');
    $size      = (string)($raw['size']      ?? '');
    $qty       = (int)   ($raw['qty']       ?? 0);
    $versions  = max(1, (int)($raw['versions'] ?? 1));
    $sides     = (string)($raw['sides']     ?? 'single');

    if (!in_array($product, ITEM_ALLOWED_PRODUCT, true)) {
        return ['ok' => false, 'error' => 'invalid product'];
    }
    if (!in_array($size, ITEM_ALLOWED_SIZE, true)) {
        return ['ok' => false, 'error' => 'invalid size'];
    }
    if (!in_array($size, ITEM_SIZE_BY_PRODUCT[$product] ?? [], true)) {
        return ['ok' => false, 'error' => 'size not available for this product'];
    }
    if (!in_array($sides, ITEM_ALLOWED_SIDES, true)) {
        return ['ok' => false, 'error' => 'invalid sides'];
    }
    if ($qty < 1 || $qty > 10000) {
        return ['ok' => false, 'error' => 'qty out of range'];
    }
    if ($versions > 10) {
        return ['ok' => false, 'error' => 'versions out of range'];
    }
    $paper = parsePaperKey($paper_key);
    if (!$paper) {
        return ['ok' => false, 'error' => 'invalid paper_key'];
    }

    $id = (string)($raw['id'] ?? '');
    if ($id !== '' && !preg_match(ITEM_UUID_REGEX, $id)) {
        return ['ok' => false, 'error' => 'invalid item id'];
    }

    return [
        'ok' => true,
        'item' => [
            'id'        => $id,
            'product'   => $product,
            'paper_key' => $paper_key,
            'paperType' => $paper['paperType'],
            'weight'    => $paper['weight'],
            'size'      => $size,
            'qty'       => $qty,
            'versions'  => $versions,
            'sides'     => $sides,
        ],
    ];
}

/**
 * Validate a list of raw items. Returns ['ok'=>true,'items'=>[...]] or
 * ['ok'=>false,'error'=>'msg','item_index'=>i] so callers can highlight the bad row.
 */
function validateItems(array $rawItems): array
{
    if (empty($rawItems)) {
        return ['ok' => false, 'error' => 'no items'];
    }
    if (count($rawItems) > ITEM_MAX_COUNT) {
        return ['ok' => false, 'error' => 'too many items (max ' . ITEM_MAX_COUNT . ')'];
    }
    $items = [];
    foreach ($rawItems as $i => $raw) {
        if (!is_array($raw)) {
            return ['ok' => false, 'error' => 'item not an object', 'item_index' => $i];
        }
        $r = validateItem($raw);
        if (!$r['ok']) {
            return ['ok' => false, 'error' => $r['error'], 'item_index' => $i];
        }
        $items[] = $r['item'];
    }
    return ['ok' => true, 'items' => $items];
}

/**
 * Build a human-readable single-line spec for an item, used in emails / specs string.
 * Example: "Flyer · A6 · MultiArt Silk 130 gsm · Recto · 500"
 */
function itemSpecsLine(array $item): string
{
    $productLabel = PRODUCT_LABEL[$item['product']] ?? ucfirst($item['product']);
    $paperLabel   = (PAPER_LABEL[$item['paperType']] ?? ucfirst($item['paperType'])) . ' ' . $item['weight'] . ' gsm';
    $sidesLabel   = $item['sides'] === 'double' ? 'Recto & Verso' : 'Recto';
    $parts = [
        $productLabel,
        SIZE_LABEL[$item['size']] ?? $item['size'],
        $paperLabel,
        $sidesLabel,
        $item['qty'] . ($item['versions'] > 1 ? ' × ' . $item['versions'] . ' versions' : ''),
    ];
    return implode(' · ', $parts);
}

/**
 * Parse the top-level order envelope (express + delivery). Used by both endpoints
 * so the toggle semantics are identical.
 */
function readOrderEnvelope(array $src): array
{
    $delivery = (string)($src['delivery'] ?? 'pickup');
    if (!in_array($delivery, ITEM_ALLOWED_DELIV, true)) {
        $delivery = 'pickup';
    }
    return [
        'express'  => !empty($src['express']) && $src['express'] !== '0' && $src['express'] !== false,
        'delivery' => $delivery,
    ];
}
