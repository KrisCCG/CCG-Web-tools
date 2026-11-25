<?php
/**
 * Shop — holiday closure (Kris, 26 Oct 2026).
 *
 * The team enters the closure in the Dashboard (calculator-v2 > Dashboard): FIRST CLOSED DAY and FIRST DAY BACK.
 * State: api/data/holiday.json  {"from":"2026-12-21","back":"2027-01-04","set_at":"…"}  (no file = no closure planned).
 * It lives in api/data/ (denied to the web, not touched by wiping api/orders/).
 *
 * What it does:
 *   - Every closed day (from … the day before "back") counts as a non-working day in workdays.php, so the ready date
 *     in the cart, on the order, in the mails and in the chat automatically falls after the closure. Always, not only
 *     during the notice period below.
 *   - HOLIDAY MODE runs from HOLIDAY_NOTICE_DAYS before the first closed day until HOLIDAY_RESUME_DAYS before the first
 *     day back: the shop shows a holiday banner (orders stay possible) and express is off (express.php).
 * Used by: workdays.php, express.php, calc.php (catalogue), api-dashboard.php (?action=holiday) and the chat (chat-api/lib/shop.php).
 */

declare(strict_types=1);

const HOLIDAY_FILE        = __DIR__ . '/../data/holiday.json';
const HOLIDAY_NOTICE_DAYS = 7;   // holiday mode starts this many days before the first closed day
const HOLIDAY_RESUME_DAYS = 2;   // … and ends this many days before the first day back (normal shop again)
const HOLIDAY_RESTART_DAYS = 2;  // first ready date = 2 working days after the first day back (Kris, 26 Oct 2026)

function holiday_tz(): DateTimeZone { return new DateTimeZone('Europe/Brussels'); }

/** @return array{from:string, back:string, set_at:?string}|null  the planned closure, or null */
function holiday_get(): ?array
{
    // Read once per request, but re-read when the file changed (the Dashboard saves and answers in one request).
    static $key = null, $cache = null;
    clearstatcache(true, HOLIDAY_FILE);
    $k = is_file(HOLIDAY_FILE) ? (string)@filemtime(HOLIDAY_FILE) . ':' . (string)@filesize(HOLIDAY_FILE) . ':' . ($GLOBALS['holiday_rev'] ?? 0) : 'none';
    if ($k === $key) return $cache;
    $key = $k; $cache = null;
    if ($k === 'none') return null;
    $j = json_decode((string)@file_get_contents(HOLIDAY_FILE), true);
    if (!is_array($j)) return null;
    $from = (string)($j['from'] ?? ''); $back = (string)($j['back'] ?? '');
    if (!holiday_valid_date($from) || !holiday_valid_date($back) || $from >= $back) return null;
    return $cache = ['from' => $from, 'back' => $back, 'set_at' => $j['set_at'] ?? null];
}

function holiday_valid_date(string $ymd): bool
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) return false;
    [$y, $m, $d] = array_map('intval', explode('-', $ymd));
    return checkdate($m, $d, $y);
}

/** Is this day (Y-m-d) inside the closure? (first closed day … the day before the first day back) */
function holiday_is_closed_day(string $ymd): bool
{
    $h = holiday_get();
    return $h !== null && $ymd >= $h['from'] && $ymd < $h['back'];
}

/** Everything the Dashboard, the shop and the chat need to know, for "now" (Brussels). */
function holiday_status(?DateTimeImmutable $now = null): array
{
    $h = holiday_get();
    if ($h === null) return ['planned' => false, 'active' => false];
    $tz    = holiday_tz();
    $today = ($now ? $now->setTimezone($tz) : new DateTimeImmutable('now', $tz))->format('Y-m-d');
    $from  = new DateTimeImmutable($h['from'], $tz);
    $back  = new DateTimeImmutable($h['back'], $tz);
    $start = $from->modify('-' . HOLIDAY_NOTICE_DAYS . ' days')->format('Y-m-d');   // holiday mode on
    $end   = $back->modify('-' . HOLIDAY_RESUME_DAYS . ' days')->format('Y-m-d');   // holiday mode off again (normal shop)
    return [
        'planned'     => true,
        'from'        => $h['from'],                                    // first closed day
        'last_closed' => $back->modify('-1 day')->format('Y-m-d'),     // last closed day
        'back'        => $h['back'],                                    // first day back at work
        'mode_from'   => $start,
        'mode_until'  => $back->modify('-' . (HOLIDAY_RESUME_DAYS + 1) . ' days')->format('Y-m-d'),   // last day of holiday mode
        'active'      => $today >= $start && $today < $end,
        'past'        => $today >= $h['back'],
        'set_at'      => $h['set_at'],
    ];
}

function holiday_active(): bool { return holiday_status()['active']; }

/** Save a closure. Returns an error text, or null when saved. */
function holiday_set(string $from, string $back): ?string
{
    if (!holiday_valid_date($from) || !holiday_valid_date($back)) return 'Please enter both dates.';
    if ($from >= $back) return 'The first day back must be after the first closed day.';
    if ((new DateTimeImmutable($back))->diff(new DateTimeImmutable($from))->days > 120) return 'That closure is longer than 4 months — please check the dates.';
    $ok = @file_put_contents(HOLIDAY_FILE, json_encode(['from' => $from, 'back' => $back, 'set_at' => date('c')]), LOCK_EX) !== false;
    clearstatcache(true, HOLIDAY_FILE);
    $GLOBALS['holiday_rev'] = ($GLOBALS['holiday_rev'] ?? 0) + 1;   // a save in the same second can keep mtime and size
    return $ok ? null : 'Could not save (server).';
}

function holiday_clear(): bool { $ok = !is_file(HOLIDAY_FILE) || @unlink(HOLIDAY_FILE); clearstatcache(true, HOLIDAY_FILE); return $ok; }
