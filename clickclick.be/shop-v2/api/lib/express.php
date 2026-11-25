<?php
/**
 * Shop — express ("urgent") on/off switch.
 * The team switches express off in the Dashboard (api-dashboard.php?action=express) when there is too much work.
 * State: a small file api/data/express_off.json (exists = express is OFF; delete or switch on = ON).
 * It lives in api/data/ (denied to the web, and not touched by wiping api/orders/).
 * Used by calc.php (catalogue + price + refusal), pay.php (refusal), the shop page (hides the option)
 * and the chat (calls express_available() through chat-api/lib/shop.php).
 * Since 26 Oct 2026 the holiday mode (holiday.php) also switches express off, separately from the button.
 */

declare(strict_types=1);

const EXPRESS_FLAG_FILE = __DIR__ . '/../data/express_off.json';
if (is_file(__DIR__ . '/holiday.php')) require_once __DIR__ . '/holiday.php';

/** Switched off by the team (the button)? */
function express_switched_off(): bool { return is_file(EXPRESS_FLAG_FILE); }

/** Holiday mode (holiday.php) also switches express off, by itself, for the notice period before a closure (26 Oct 2026). */
function express_holiday_off(): bool { return function_exists('holiday_active') && holiday_active(); }

function express_available(): bool
{
    return !express_switched_off() && !express_holiday_off();
}

/** @return array{available:bool, since:?string, switched_off:bool, holiday:bool} */
function express_status(): array
{
    $since = null;
    if (express_switched_off()) {
        $j = json_decode((string)@file_get_contents(EXPRESS_FLAG_FILE), true);
        $since = is_array($j) ? ($j['since'] ?? null) : null;
    }
    return ['available' => express_available(), 'since' => $since, 'switched_off' => express_switched_off(), 'holiday' => express_holiday_off()];
}

function express_set(bool $available): bool
{
    if ($available) return !is_file(EXPRESS_FLAG_FILE) || @unlink(EXPRESS_FLAG_FILE);
    return @file_put_contents(EXPRESS_FLAG_FILE, json_encode(['since' => date('c')]), LOCK_EX) !== false;
}
