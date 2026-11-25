<?php
/**
 * Working-day maths for production deadlines.
 *
 * Single source of truth for "when is this job due". Used by:
 *   - calc.php    → returns the deadline so the shop UI shows the same date
 *   - pay.php     → stores it on the order at creation
 *   - webhook.php → prints it on the internal order email
 *
 * Rules:
 *   - Counts Mon–Fri only.
 *   - Excludes Belgian public holidays (the frontend's own JS never did — that
 *     is why this lives server-side and the UI is told the answer).
 *   - Always evaluated in Europe/Brussels, never the visitor's timezone.
 *   - Orders placed after the cut-off start counting from the next working day.
 */

const WD_TIMEZONE   = 'Europe/Brussels';
const WD_CUTOFF_H   = 15;   // an order at 15:00+ starts counting tomorrow
const WD_STANDARD   = 5;    // standard production, working days
const WD_EXPRESS    = 2;    // express (48 h in practice)

/**
 * Belgian public holidays for a given year, as Y-m-d strings.
 * Easter is computed with the anonymous Gregorian algorithm so we don't depend
 * on the calendar extension (not always compiled in on shared hosting).
 */
function wd_belgian_holidays(int $year): array {
    $a = $year % 19;
    $b = intdiv($year, 100);
    $c = $year % 100;
    $d = intdiv($b, 4);
    $e = $b % 4;
    $f = intdiv($b + 8, 25);
    $g = intdiv($b - $f + 1, 3);
    $h = (19 * $a + $b - $d - $g + 15) % 30;
    $i = intdiv($c, 4);
    $k = $c % 4;
    $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
    $m = intdiv($a + 11 * $h + 22 * $l, 451);
    $month = intdiv($h + $l - 7 * $m + 114, 31);
    $day   = (($h + $l - 7 * $m + 114) % 31) + 1;

    $easter = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day), new DateTimeZone(WD_TIMEZONE));

    return [
        sprintf('%04d-01-01', $year),                    // Nieuwjaar
        $easter->modify('+1 day')->format('Y-m-d'),      // Paasmaandag
        sprintf('%04d-05-01', $year),                    // Feest van de Arbeid
        $easter->modify('+39 days')->format('Y-m-d'),    // O.L.H. Hemelvaart
        $easter->modify('+50 days')->format('Y-m-d'),    // Pinkstermaandag
        sprintf('%04d-07-21', $year),                    // Nationale feestdag
        sprintf('%04d-08-15', $year),                    // O.L.V. Hemelvaart
        sprintf('%04d-11-01', $year),                    // Allerheiligen
        sprintf('%04d-11-11', $year),                    // Wapenstilstand
        sprintf('%04d-12-25', $year),                    // Kerstmis
    ];
}

function wd_is_working_day(DateTimeImmutable $d, array &$cache = []): bool {
    $dow = (int)$d->format('N');          // 1 = Mon … 7 = Sun
    if ($dow >= 6) return false;
    $year = (int)$d->format('Y');
    if (!isset($cache[$year])) $cache[$year] = wd_belgian_holidays($year);
    return !in_array($d->format('Y-m-d'), $cache[$year], true);
}

/**
 * The last possible production deadline for an order placed now.
 *
 * @param bool                   $express  express job (2 working days) or standard (5)
 * @param DateTimeImmutable|null $from     override "now" (tests)
 * @return array{date:string, days:int, express:bool}  date is Y-m-d
 */
function wd_production_deadline(bool $express = false, ?DateTimeImmutable $from = null): array {
    $tz  = new DateTimeZone(WD_TIMEZONE);
    $now = $from ? $from->setTimezone($tz) : new DateTimeImmutable('now', $tz);

    $days  = $express ? WD_EXPRESS : WD_STANDARD;
    $cache = [];

    // Past the cut-off, or on a non-working day, production starts later.
    $cursor = $now->setTime(0, 0);
    if ((int)$now->format('G') >= WD_CUTOFF_H) {
        $cursor = $cursor->modify('+1 day');
    }

    $counted = 0;
    // Walk forward until we have counted the required working days. The bound is
    // a safety net: 10 holidays a year can never push 5 working days past ~30.
    for ($guard = 0; $counted < $days && $guard < 60; $guard++) {
        $cursor = $cursor->modify('+1 day');
        if (wd_is_working_day($cursor, $cache)) $counted++;
    }

    return [
        'date'    => $cursor->format('Y-m-d'),
        'days'    => $days,
        'express' => $express,
    ];
}
