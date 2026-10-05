<?php

namespace Modules\Employee\Services;

use Carbon\Carbon;

/**
 * Pure date math for "repeat this week's availability" — kept separate
 * from the controller/request so the same rule is used for both
 * validating the span and actually writing rows, and so worker-portal's
 * preview ("Sets availability from … to …") can mirror it exactly (see
 * src/utils/availabilityDates.js there — keep the two in step).
 *
 * Returns the LAST date (inclusive) the pattern should cover, starting
 * from the Monday of the week the worker actually edited:
 *
 *   none   → that same week's Sunday
 *   weeks  → N whole weeks, so Monday + N*7 − 1 days
 *   months → N calendar months later, minus a day (no day-of-month
 *            overflow: Jan 31 + 1 month is Feb 28/29, not early March)
 *   until  → exactly the date given — may land mid-week, and the last
 *            week is then only partly written, not rounded up
 */
class AvailabilityRepeat
{
    /** The longest span (in days, inclusive) one save may cover. */
    public const MAX_SPAN_DAYS = 366;

    public static function endDate(Carbon $weekStart, array $repeat): Carbon
    {
        $start = $weekStart->copy()->startOfDay();

        return match ($repeat['mode'] ?? null) {
            'weeks' => $start->addWeeks((int) $repeat['count'])->subDay(),
            'months' => $start->addMonthsNoOverflow((int) $repeat['count'])->subDay(),
            'until' => Carbon::createFromFormat('Y-m-d', $repeat['until'])->startOfDay(),
            default => $start->addDays(6), // 'none': just this week
        };
    }
}
