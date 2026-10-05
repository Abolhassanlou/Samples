<?php

namespace Modules\Employee\Services;

/**
 * Minute-based interval math for one day's availability. Times are
 * 'H:i' strings in and out; inside, minutes since midnight, with
 * 23:59 as an END meaning 1440 ("until midnight") — a time column has
 * no 24:00, so that's how a slot reaching the end of the day is stored,
 * and it has to compare equal to a booking that really runs to 24:00.
 */
class AvailabilityIntervals
{
    public const END_OF_DAY = 1440;

    public static function toMinutes(string $time, bool $isEnd = false): int
    {
        [$h, $m] = array_map('intval', explode(':', substr($time, 0, 5)));
        $minutes = $h * 60 + $m;

        return ($isEnd && $minutes === 23 * 60 + 59) ? self::END_OF_DAY : $minutes;
    }

    public static function format(int $minutes, bool $isEnd = false): string
    {
        if ($isEnd && $minutes >= self::END_OF_DAY) {
            return '23:59';
        }

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    /**
     * Sorts and merges overlapping or touching [start, end] intervals —
     * so a requested slot and a booking that overlaps it become one row,
     * and two slots that meet end-to-start become one range.
     *
     * @param  array<int, array{0: int, 1: int}>  $intervals
     * @return array<int, array{0: int, 1: int}>
     */
    public static function merge(array $intervals): array
    {
        usort($intervals, fn ($a, $b) => $a[0] <=> $b[0]);

        $merged = [];
        foreach ($intervals as [$start, $end]) {
            $last = count($merged) - 1;
            if ($last >= 0 && $start <= $merged[$last][1]) {
                $merged[$last][1] = max($merged[$last][1], $end);
            } else {
                $merged[] = [$start, $end];
            }
        }

        return $merged;
    }

    /** Is [start, end] entirely inside one of these (already merged) intervals? */
    public static function covers(array $merged, array $interval): bool
    {
        foreach ($merged as [$start, $end]) {
            if ($start <= $interval[0] && $end >= $interval[1]) {
                return true;
            }
        }

        return false;
    }
}
