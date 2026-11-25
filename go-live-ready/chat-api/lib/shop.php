<?php
/**
 * Click Click online shop — pricing for the chat (Oct 2026: new price engine).
 *
 * The chat no longer calls the old /calculator/calc.php over HTTP. It loads the
 * SAME price engine and price file as the new shop (shop-v2) and the internal
 * calculator (calculator-v2), directly from disk:
 *   <site root>/shop-v2/api/lib/engine.php
 *   <site root>/shop-v2/api/data/price-data.xlsx + selftest.json
 * So the chat, the shop and the internal calculator always give the same price,
 * and products / papers / folds come from the Excel (Update prices).
 *
 * When the new shop replaces the old one at /shop/, change SHOP_DIR_NAME to 'shop'.
 */

declare(strict_types=1);

const SHOP_DIR_NAME = 'shop';
const SHOP_BASE_URL = 'https://clickclick.be/' . SHOP_DIR_NAME . '/';
const SHOP_SIDES    = ['single', 'double'];
const SHOP_DELIVERY = ['pickup', 'dpd'];

function shop_dir(): string { return dirname(__DIR__, 2) . '/' . SHOP_DIR_NAME; }
/** Express available? Asks the shop itself (api/lib/express.php: the Dashboard switch AND the holiday mode, 26 Oct 2026).
 *  Fallback for an older shop without that file: only the flag file api/data/express_off.json. */
function shop_express_available(): bool {
    $lib = shop_dir() . '/api/lib/express.php';
    if (is_file($lib)) {
        require_once $lib;
        if (function_exists('express_available')) return express_available();
    }
    return !is_file(shop_dir() . '/api/data/express_off.json');
}

/** Price data (cached by the engine; refuses to work if its self-test fails). Null if unavailable. */
function shop_data(): ?array {
    static $d = false;
    if ($d !== false) return $d;
    $engine = shop_dir() . '/api/lib/engine.php';
    if (!is_file($engine)) { $d = null; return $d; }
    require_once $engine;
    try {
        $d = ccp_data(shop_dir() . '/api/data');
    } catch (Throwable $e) {
        error_log('chat shop_data: ' . $e->getMessage());
        $d = null;
    }
    return $d;
}

/** Public catalogue (no costs) of the active shop products. */
function shop_catalogue(): ?array {
    $d = shop_data();
    return $d ? ccp_catalogue($d) : null;
}

/**
 * The shop catalogue as plain text, for the tool description — generated from the
 * price file, so the bot always knows exactly what the shop sells.
 */
function shop_catalogue_text(): string {
    $c = shop_catalogue();
    if (!$c) return "(The online shop is unavailable right now — do not call check_shop_pricing.)";
    $lines = [];
    foreach ($c['products'] as $id => $p) {
        if (!empty($p['pages'])) {   // brochure: stapled booklet, closed size, pages, cover follows the inside paper
            $lines[] = shop_brochure_text($id, $p, $c);
            continue;
        }
        $sizes = array_map(function ($s) use ($c) {
            $z = $c['sizes'][$s] ?? null;
            return $s . ($z ? " ({$z['w']}x{$z['h']} mm" . ($z['onlyOn'] ? ', only ' . implode('/', $z['onlyOn']) : '') . ')' : '');
        }, $p['sizes']);
        $papers = array_map(fn($k) => $k . ' = ' . ($c['papers'][$k]['name'] ?? $k) . ' ' . ($c['papers'][$k]['gsm'] ?? '') . ' g/m²', $p['papers']);
        $l = "- {$id}: sizes " . implode(', ', $sizes) . '. Papers: ' . implode('; ', $papers)
           . ". Default paper {$p['defaultPaper']}. Quantity 1–{$p['maxQty']}. Sides: " . implode('/', $p['sides']) . '.';
        if ($p['folds']) {
            $f = [];
            foreach ($p['folds'] as $fid) {
                $closed = [];
                foreach ($p['sizes'] as $s) {
                    $fi = $c['folds']["$fid|$s"] ?? null;
                    if ($fi) $closed[] = "$s → " . str_replace('.0 ', ' ', (string)$fi['closed']) . ' mm';
                }
                $f[] = $fid . ' (' . implode(', ', $closed) . ')';
            }
            $l .= ' Sizes are the FLAT (open) sheet. Folds: ' . implode('; ', $f) . '.';
        }
        $lines[] = $l;
    }
    return implode("\n", $lines);
}

/**
 * TODAY block for the bot (Kris, 17 Oct: the bot did not know the date and asked
 * "is Friday tomorrow or next week?"). Server clock in Europe/Brussels; ready dates
 * from the shop's own working-day rules (Belgian holidays, 15:00 cut-off).
 */
function chat_today_text(?DateTimeImmutable $now = null): string {
    $tz  = new DateTimeZone('Europe/Brussels');
    $now = $now ? $now->setTimezone($tz) : new DateTimeImmutable('now', $tz);
    $fmt = fn(DateTimeImmutable $d) => $d->format('l j F Y');
    $txt = 'TODAY: ' . $fmt($now) . ', ' . $now->format('H:i') . ' (Brussels time). This is the real date. For every date the '
         . 'customer gives ("Friday", "next Wednesday", "in two weeks", "tomorrow") use the CALENDAR below — '
         . 'never ask the customer which day they mean. Working days are Monday to Friday, '
         . 'except Belgian public holidays.';
    $wd = shop_dir() . '/api/lib/workdays.php';
    if (is_file($wd)) {
        require_once $wd;
        try {
            $expOn = shop_express_available();
            $std = wd_production_deadline(false, $now);
            $exp = wd_production_deadline(true, $now);
            $d = fn(string $ymd) => $fmt(new DateTimeImmutable($ymd, $tz));
            $txt .= ' An order placed now (orders after 15:00 count from the next working day) is READY for pickup: '
                  . 'standard (' . $std['days'] . ' working days) on ' . $d($std['date']) . '; express (48 h, '
                  . $exp['days'] . ' working days, pickup only) on ' . $d($exp['date']) . '. '
                  . 'Never promise a date the shop does not show, and never an arrival date for DPD delivery.';
            // CALENDAR lookup (Kris, 17 Oct: Haiku read "mardi" as 22 Oct and "Thursday" as the 17th —
            // it must look dates up, never compute them). Today + 21 days, each with its verdict.
            $today = $now->setTime(0, 0);
            // Holiday closure: which days are closed for the holiday, and which are the restart days after it
            // (26 Oct 2026: the bot called Tue 10 Nov, a restart day, part of the holiday).
            $holC = (function_exists('holiday_get') && ($hg = holiday_get()) !== null) ? $hg : null;
            $holFirst = ($holC && function_exists('wd_holiday_first_ready')) ? wd_holiday_first_ready() : null;
            $cache = [];
            $rows  = [];
            for ($i = 0; $i <= 21; $i++) {
                $day  = $today->modify("+{$i} day");
                $ymd  = $day->format('Y-m-d');
                $tags = [];
                if ($i === 0) $tags[] = 'TODAY';
                if ($i === 1) $tags[] = 'tomorrow';
                if ($holC && $ymd >= $holC['from'] && $ymd < $holC['back']) $tags[] = 'HOLIDAY, shop closed';
                elseif (!wd_is_working_day($day, $cache)) $tags[] = 'shop closed';
                elseif ($holC && $holFirst && $ymd >= $holC['back'] && $ymd < $holFirst) $tags[] = 'back at work after the holiday, restarting - NOT a holiday day';
                if ($i > 0) {
                    if (!$expOn && $ymd < $std['date']) $tags[] = 'too soon, express is OFF';
                    elseif ($ymd < $exp['date']) $tags[] = 'too soon, even for express';
                    elseif ($ymd < $std['date']) $tags[] = 'EXPRESS needed';
                    else                         $tags[] = 'standard OK';
                }
                if ($expOn && $ymd === $exp['date']) $tags[] = 'express ready';
                if ($ymd === $std['date']) $tags[] = 'standard ready';
                $rows[] = $day->format('D j M') . ' (' . implode(', ', $tags) . ')';
            }
            $names = [];
            for ($i = 1; $i <= 7; $i++) {
                $day     = $today->modify("+{$i} day");
                $names[] = $day->format('l') . ' = ' . $day->format('D j M');
            }
            $mon = $today->modify('next monday');
            $txt .= "\nCALENDAR — look every date up here, never work out a weekday or a date yourself: "
                  . implode('; ', $rows) . '.'
                  . "\nA day name on its own (\"Thursday\", \"jeudi\", \"donderdag\", \"mardi prochain\") = the FIRST such day after today: "
                  . implode(', ', $names) . '. "Next week" / "la semaine prochaine" / "volgende week" = '
                  . $mon->format('D j M') . ' to ' . $mon->modify('+6 day')->format('D j M') . '.'
                  . "\nThe verdict in brackets decides: \"standard OK\" → express=false; \"EXPRESS needed\" → express=true "
                  . 'and say standard would be too late; "too soon, even for express" → still show the express price, '
                  . 'say the online order is ready on the express date and ask whether the team should check an earlier date. '
                  . 'Express is ALWAYS pickup only (Aalststraat 7-11) — say so every time you quote it: there is no time for shipping.';
            // Holiday closure (set in the Dashboard; shop/api/lib/holiday.php is loaded by workdays.php). The closed days are
            // already "shop closed" in the CALENDAR and skipped in the ready dates above.
            $hol = function_exists('holiday_status') ? holiday_status($now) : ['planned' => false];
            $holExpOff = !empty($hol['active']) && function_exists('express_switched_off') && !express_switched_off();
            if (!empty($hol['planned']) && empty($hol['past'])) {
                $txt .= "\nHOLIDAY CLOSURE: the shop is closed from " . $d($hol['from']) . ' to ' . $d($hol['last_closed'])
                      . ' (both included); first working day back: ' . $d($hol['back']) . '. Orders can still be placed; '
                      . 'the ready dates above already take the closure into account'
                      . (function_exists('wd_holiday_first_ready') && wd_holiday_first_ready()
                          ? ', and nothing is ready before ' . $d(wd_holiday_first_ready()) . ' (the first days back are for restarting). '
                            . 'Only the days tagged "HOLIDAY" in the CALENDAR are holiday days. A date between the first day back and '
                            . $d(wd_holiday_first_ready()) . ' is NOT in the holiday: say we are back at work then but the order cannot be ready that soon. '
                          : '. ')
                      . (!empty($hol['active'])
                          ? 'Mention the closure and the first day back whenever you give a date or a quote.'
                          : 'Mention it when the customer asks about a date around the closure.')
                      // Wording chosen by Kris (26 Oct 2026); the bot had said "fermeture annuelle" and "pour la Toussaint".
                      . ' Say it with exactly these words: French "Nous sommes en vacances", Dutch "We zijn op vakantie", English "We are on holidays". '
                      . 'Never "annual closure" / "fermeture annuelle" / "jaarlijkse sluiting", never a holiday name, never a reason.';
            }
            if (!$expOn && $holExpOff) {
                $txt .= "\nEXPRESS IS OFF AROUND OUR HOLIDAY CLOSURE. This overrides everything above about express: "
                      . 'never offer, quote or promise express; always express=false; ignore the express dates. If the customer needs it '
                      . 'earlier than the standard date: say kindly that rush jobs are not possible because of the holiday closure, still show the '
                      . 'standard price in the same reply (express=false; the card shows the ready date and the closure), and offer to send the request '
                      . 'so the team can check by hand.';
            } elseif (!$expOn) {
                $txt .= "\nEXPRESS IS SWITCHED OFF RIGHT NOW (the team is fully booked). This overrides everything above about express: "
                      . 'never offer, quote or promise express; always express=false; ignore the express dates. If the customer needs it '
                      . 'earlier than the standard date: say kindly that rush jobs are not possible at the moment, give the standard ready date, '
                      . 'still show the standard price in the same reply (express=false), '
                      . 'and offer to send the request so the team can check by hand whether an earlier date is possible.';
            }
        } catch (Throwable $e) {
            error_log('chat_today_text: ' . $e->getMessage());
        }
    }
    return $txt;
}

/** Brochure line of the tool description (Kris, 17 Oct: stapled only, cover = 250 g of the same paper line). */
function shop_brochure_text(string $id, array $p, array $c): string {
    $sizes = array_map(function ($s) use ($c) {
        $z = $c['sizes'][$s] ?? null;
        return $s . ($z ? " ({$z['w']}x{$z['h']} mm)" : '');
    }, $p['sizes']);
    $papers = array_map(fn($k) => $k . ' = ' . ($c['papers'][$k]['name'] ?? $k) . ' ' . ($c['papers'][$k]['gsm'] ?? '') . ' g/m²', $p['papers']);
    $covers = array_map(fn($k) => $k . ' = ' . ($c['papers'][$k]['name'] ?? $k) . ' ' . ($c['papers'][$k]['gsm'] ?? '') . ' g/m²', $p['coverPapers']);
    $min = $p['minQty'] ?? 1;
    return "- {$id} = STAPLED brochure / booklet (saddle-stitched — the only binding we sell). Sizes are the CLOSED (finished) size: "
        . implode(', ', $sizes) . '. Pages (cover included): ' . implode(', ', $p['pages']) . '; pass them as "pages". '
        . 'Papers (= the INSIDE paper): ' . implode('; ', $papers) . ". Default inside paper {$p['defaultPaper']}. "
        . 'The cover is chosen automatically: the cover paper of the SAME paper line (' . implode('; ', $covers) . ') — never pass it, never offer another combination. '
        . "Quantity {$min}–{$p['maxQty']}. Always printed on both sides. VAT 21 % like every product (prices include it).";
}

/** Cover paper for a brochure: the cover of the same paper line as the inside paper (multiart-130 → multiart-250). */
function shop_brochure_cover(array $p, string $inside): ?string {
    foreach ($p['coverPapers'] ?? [] as $k) if (explode('-', $k)[0] === explode('-', $inside)[0]) return $k;
    return null;
}

/**
 * Validate the bot's input against the catalogue. Returns null if OK, else a reason.
 * Mutates $in: safe defaults for sides / delivery / express / fold.
 */
function shop_validate(array &$in, array $d): ?string {
    $p = $d['products'][$in['product'] ?? ''] ?? null;
    if (!$p || !$p['active']) return 'product not in shop catalog';
    if (!in_array($in['size'] ?? '', $p['sizes'], true)) return 'size not available for this product in the shop';
    $paper = (string)($in['paper'] ?? '');
    if (!isset($d['papers'][$paper]) || ($d['papers'][$paper]['active'] ?? '') !== 'yes') return 'paper not in shop catalog';
    if (!in_array($paper, $p['papers'], true)) return 'paper not available for this product in the shop';
    $qty = (int)($in['qty'] ?? 0);
    $minQ = $p['minQty'] ?? 1;
    if ($qty < $minQ || $qty > $p['maxQty']) return "qty out of range ({$minQ}-{$p['maxQty']})";
    if (!empty($p['pages'])) {
        $in['pages'] = (int)($in['pages'] ?? 0);
        if (!in_array($in['pages'], $p['pages'], true)) return 'page count not in the shop (' . implode(', ', $p['pages']) . ')';
        $in['cover'] = shop_brochure_cover($p, $paper);
        if (!$in['cover']) return 'paper not available for this product in the shop';
        $in['fold'] = null; $in['sides'] = 'double'; $in['versions'] = 1;
    } else {
        $in['pages'] = null; $in['cover'] = null;
    }
    if ($p['folds']) {
        if (!in_array($in['fold'] ?? '', $p['folds'], true)) $in['fold'] = $p['folds'][0];
    } else {
        $in['fold'] = null;
    }
    if (!in_array($in['sides'] ?? '', SHOP_SIDES, true) || !in_array($in['sides'], $p['sides'], true)) {
        // defaults as in the prompt: both sides for cards (all sizes), folders and brochures; one side for flyers and posters
        $want = in_array($in['product'], ['card', 'folder', 'brochure'], true) ? 'double' : 'single';
        $in['sides'] = in_array($want, $p['sides'], true) ? $want : $p['sides'][0];
    }
    if (!in_array($in['delivery'] ?? '', SHOP_DELIVERY, true)) $in['delivery'] = 'pickup';
    $in['express'] = !empty($in['express']) && $in['express'] !== 'false';
    if ($in['express'] && !shop_express_available()) $in['express'] = false;   // switched off by the team
    if ($in['express']) $in['delivery'] = 'pickup';   // express is pickup only, as in the shop (Kris, 17 Oct)
    return null;
}

/** "mardi 20 octobre" / "dinsdag 20 oktober" / "Tuesday 20 October" for the price card. */
function shop_date_label(string $ymd, ?string $lang): string {
    $d = new DateTimeImmutable($ymd, new DateTimeZone('Europe/Brussels'));
    $names = [
        'fr' => [['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'],
                 ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre']],
        'nl' => [['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'],
                 ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december']],
    ];
    if (!isset($names[$lang])) return $d->format('l j F');
    [$days, $months] = $names[$lang];
    return $days[(int)$d->format('N') - 1] . ' ' . $d->format('j') . ' ' . $months[(int)$d->format('n') - 1];
}

/** Price a spec with the shop's own engine. Always returns an array with `ok`. Never throws. */
function shop_get_quote(array $in): array {
    $d = shop_data();
    if (!$d) return ['ok' => false, 'error' => 'shop unreachable'];
    $err = shop_validate($in, $d);
    if ($err !== null) return ['ok' => false, 'error' => $err];
    try {
        $r = ccp_price($d, [
            'product' => $in['product'], 'size' => $in['size'], 'paperId' => $in['paper'], 'qty' => (int)$in['qty'],
            'sides' => $in['sides'], 'versions' => 1, 'express' => $in['express'], 'fold' => $in['fold'],
            'delivery' => $in['delivery'] === 'dpd', 'ink' => 'cmyk',
            'pages' => $in['pages'], 'coverPaperId' => $in['cover'],
        ]);
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
    $qty = (int)$in['qty'];
    $lang = in_array($in['lang'] ?? '', ['fr', 'nl', 'en'], true) ? $in['lang'] : null;
    // Ready date for the card, from the shop's own working-day rules (same as the shop shows).
    $ready = null;
    $wd = shop_dir() . '/api/lib/workdays.php';
    if (is_file($wd)) {
        require_once $wd;
        try { $ready = shop_date_label(wd_production_deadline($in['express'])['date'], $lang); }
        catch (Throwable $e) { error_log('shop ready date: ' . $e->getMessage()); }
    }
    // Holiday closure on the card while the holiday mode runs (same period as the shop banner) — a fixed line,
    // not left to the bot (26 Oct 2026: Haiku gave the right ready date but did not mention the closure).
    $holiday = null;
    if (function_exists('holiday_status')) {
        $hs = holiday_status();
        if (!empty($hs['active'])) $holiday = ['from' => shop_date_label($hs['from'], $lang), 'to' => shop_date_label($hs['last_closed'], $lang), 'back' => shop_date_label($hs['back'], $lang)];
    }
    return [
        'ok'           => true,
        'inclVat'      => $r['incl'],
        'exVat'        => $r['excl'],
        'perPieceIncl' => round($r['incl'] / max(1, $qty), 4),
        'qty'          => $qty,
        'productionDays' => (int)($d['products'][$in['product']]['days'] ?? 5),
        'readyDate'    => $ready,
        'express'      => (bool)$in['express'],
        'holiday'      => $holiday,
        'summary'      => shop_summary_line($in, $d),
        'shopUrl'      => shop_deep_link($in),
        'lang'         => in_array($in['lang'] ?? '', ['fr', 'nl', 'en'], true) ? $in['lang'] : null,
    ];
}

/** "Folder · A4 → 99 x 210 mm · 250 · recto/verso · MultiArt Silk 170 g/m²" */
function shop_summary_line(array $in, array $d): string {
    $pa = $d['papers'][$in['paper']] ?? [];
    $size = $in['size'];
    if (!empty($in['fold'])) {
        $fi = $d['folds'][$in['fold'] . '|' . $in['size']] ?? [];
        $lang = in_array($in['lang'] ?? '', ['fr', 'nl', 'en'], true) ? strtoupper($in['lang']) : 'EN';
        $size .= ' → ' . ($fi["name in the shop ($lang)"] ?? $fi['name in the shop (EN)'] ?? $in['fold']);
    }
    if (!empty($in['pages'])) {   // "Brochure (geniet) · A4 · 16 pagina's · 50 · cover MultiArt Silk 250 g/m² · binnenwerk MultiArt Silk 130 g/m²"
        $l = in_array($in['lang'] ?? '', ['fr', 'nl', 'en'], true) ? $in['lang'] : 'en';
        $w = ['en' => ['Booklet (stapled)', 'pages', 'cover', 'inside'], 'fr' => ['Brochure (agrafée)', 'pages', 'couverture', 'intérieur'],
              'nl' => ['Brochure (geniet)', "pagina's", 'cover', 'binnenwerk']][$l];
        $cv = $d['papers'][$in['cover']] ?? [];
        $bits = [$w[0], $in['size'], $in['pages'] . ' ' . $w[1], (string)$in['qty'],
            $w[2] . ' ' . trim(($cv['name (shown to customers)'] ?? $in['cover']) . ' ' . ($cv['gsm'] ?? '') . ' g/m²'),
            $w[3] . ' ' . trim(($pa['name (shown to customers)'] ?? $in['paper']) . ' ' . ($pa['gsm'] ?? '') . ' g/m²')];
        if (!empty($in['express']))            $bits[] = 'express';
        if (($in['delivery'] ?? '') === 'dpd') $bits[] = 'DPD';
        return implode(' · ', $bits);
    }
    // Product name + sides in the card's language, same words as the shop (shop-v2/lib/i18n.js).
    // Kris, 17 Oct: the FR card said "Card · A6".
    $l = in_array($in['lang'] ?? '', ['fr', 'nl', 'en'], true) ? $in['lang'] : 'en';
    $pn = [
        'fr' => ['flyer' => 'Flyer', 'card' => 'Carte', 'poster' => 'Affiche', 'folder' => 'Dépliant'],
        'nl' => ['flyer' => 'Flyer', 'card' => 'Kaart', 'poster' => 'Poster', 'folder' => 'Folder'],
    ];
    $sd = ['fr' => ['recto & verso', 'recto seul'], 'nl' => ['dubbelzijdig', 'enkelzijdig'], 'en' => ['recto & verso', 'recto only']][$l];
    $bits = [
        $pn[$l][$in['product']] ?? ($d['products'][$in['product']]['name'] ?? $in['product']),
        $size,
        $in['qty'] . ' ' . ($in['sides'] === 'double' ? $sd[0] : $sd[1]),
        trim(($pa['name (shown to customers)'] ?? $in['paper']) . ' ' . ($pa['gsm'] ?? '') . ' g/m²'),
    ];
    if (!empty($in['express']))            $bits[] = 'express';
    if (($in['delivery'] ?? '') === 'dpd') $bits[] = 'DPD';
    return implode(' · ', $bits);
}

/** Deep link into the shop; its URL prefill reads these (product, size, paper, fold, qty, sides, …). */
function shop_deep_link(array $in): string {
    $params = [
        'product'  => $in['product'],
        'size'     => $in['size'],
        'paper'    => $in['paper'],
        'qty'      => (int)$in['qty'],
        'sides'    => $in['sides'],
        'express'  => !empty($in['express']) ? '1' : '0',
        'delivery' => $in['delivery'] ?? 'pickup',
    ];
    if (!empty($in['fold'])) $params['fold'] = $in['fold'];
    if (!empty($in['pages'])) { $params['pages'] = (int)$in['pages']; $params['cover'] = $in['cover']; }
    $lang = $in['lang'] ?? '';
    if (in_array($lang, ['fr', 'nl', 'en'], true)) $params['lang'] = $lang;
    $contact_map = [
        'contact_name' => 'custName', 'contact_email' => 'custEmail', 'contact_phone' => 'custPhone',
        'contact_company' => 'custCompany', 'contact_vat' => 'vatNumber', 'contact_address' => 'custAddress',
        'contact_zip' => 'custZip', 'contact_city' => 'custCity',
    ];
    foreach ($contact_map as $botKey => $shopKey) {
        $val = trim((string)($in[$botKey] ?? ''));
        if ($val !== '') $params[$shopKey] = $val;
    }
    return SHOP_BASE_URL . '?' . http_build_query($params);
}

/**
 * Reply language, decided by the website — not guessed by the bot.
 * Rule (Kris, 16 Oct 2026): the page language (EN/FR/NL buttons, or the language of
 * the site page the visitor came from) is used. Only when the customer clearly writes
 * a sentence in another of the three languages do we switch, and the page follows.
 * A language the customer picked by hand (pinned) is never overruled.
 */
function chat_reply_lang(?string $page_lang, string $message, bool $pinned): string {
    $page = in_array($page_lang, ['fr', 'nl', 'en'], true) ? $page_lang : 'fr';
    if ($pinned) return $page;
    $words = preg_split('/[^\p{L}\']+/u', mb_strtolower($message), -1, PREG_SPLIT_NO_EMPTY);
    $sets = [
        'en' => ['i','we','you','the','and','would','like','need','want','please','can','could','for','my','our','with','is','are','have','how','much','what','do','does','this','that','some','get','hello','hi','thanks','price','cost','print','printed','looking','about','of','to','in','on','it','be','will','when','which','one','two','sided','also','pieces','copies','fold','folded','order','but','not','yes','no','tomorrow'],
        'fr' => ['je','j\'ai','j\'aimerais','nous','vous','voudrais','voudrions','besoin','des','pour','avec','est','sont','le','la','les','un','une','bonjour','merci','combien','plait','prix','coût','imprimer','cartes','affiches','dépliants','dépliant','recto','verso','et','du','de','au','aux','mon','ma','mes','notre','sur','quel','quelle','faire','pouvez','avez','c\'est','svp','visite','pli','plié','roulé','accordéon','aussi','exemplaires','commander','mais','pas','oui','non','demain','vite'],
        'nl' => ['ik','wij','we','jullie','je','graag','wil','willen','zou','nodig','voor','met','is','zijn','de','het','een','hallo','dag','dank','bedankt','hoeveel','prijs','kost','drukken','laten','kaarten','visitekaartjes','affiches','en','van','op','mijn','ons','onze','wat','welke','kunnen','kan','hebben','heb','dubbelzijdig','enkelzijdig','ook','stuks','stuk','rolvouw','wikkelvouw','zigzagvouw','vouw','gevouwen','bestellen','maar','niet','geen','ja','nee','zo','snel','morgen'],
    ];
    $score = [];
    foreach ($sets as $l => $list) {
        $flip = array_flip($list);
        $score[$l] = 0;
        foreach ($words as $w) if (isset($flip[$w])) $score[$l]++;
    }
    arsort($score);
    $best = array_key_first($score);
    $vals = array_values($score);
    // Switch only on a clear sentence: at least 2 language words, and at least twice the runner-up.
    if ($best !== $page && $vals[0] >= 2 && $vals[0] >= 2 * $vals[1]) return $best;
    return $page;
}
