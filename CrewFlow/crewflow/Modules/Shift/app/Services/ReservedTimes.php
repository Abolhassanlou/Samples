<?php

namespace Modules\Shift\Services;

use Carbon\Carbon;
use Modules\Employee\Contracts\ReservedTimeProvider;
use Modules\Shift\Models\Assignment;

/**
 * Shift's answer to Employee's ReservedTimeProvider: the hours a worker
 * is already booked for. Bound in ShiftServiceProvider::register() —
 * Employee only knows the contract, never this class (Shift depends on
 * Employee, not the other way round).
 *
 * "Booked" means an assignment that is still live — pending the
 * worker's own confirmation, or confirmed — on a shift that hasn't been
 * cancelled. Both count: once a dispatcher has placed someone, the time
 * is spoken for whether or not they've tapped Confirm yet. A cancelled
 * assignment, or a cancelled shift, releases the hours the moment its
 * status changes — nothing is stored here, so there's no flag to forget
 * to clear. An interest (not yet an assignment) reserves nothing.
 *
 * Times are compared exactly as stored on the shift — the same wall-clock
 * values an admin typed — against the wall-clock hours of the
 * availability grid.
 */
class ReservedTimes implements ReservedTimeProvider
{
    public function segments(int $workerId, Carbon $from, Carbon $to): array
    {
        $rangeStart = $from->copy()->startOfDay();
        $rangeEnd = $to->copy()->addDay()->startOfDay(); // exclusive
        $fromDate = $rangeStart->toDateString();
        $toDate = $to->copy()->startOfDay()->toDateString();

        $assignments = Assignment::where('worker_id', $workerId)
            ->whereIn('status', ['pending_worker_confirmation', 'confirmed'])
            ->whereHas('shift', fn ($q) => $q
                ->where('status', '!=', 'cancelled')
                ->where('starts_at', '<', $rangeEnd)
                ->where('ends_at', '>', $rangeStart))
            ->with('shift')
            ->get();

        $segments = [];

        foreach ($assignments as $assignment) {
            $shift = $assignment->shift;

            // One segment per calendar date the shift touches — a 22:00–02:00
            // shift is 22:00–23:59 on its first date and 00:00–02:00 on the next.
            for ($day = $shift->starts_at->copy()->startOfDay(); $day->lt($shift->ends_at); $day->addDay()) {
                $dayEnd = $day->copy()->addDay();
                $segStart = $shift->starts_at->gt($day) ? $shift->starts_at : $day;
                $segEnd = $shift->ends_at->lt($dayEnd) ? $shift->ends_at : $dayEnd;

                $date = $day->toDateString();
                if ($segEnd->lte($segStart) || $date < $fromDate || $date > $toDate) {
                    continue;
                }

                $segments[] = [
                    'date' => $date,
                    'start_time' => $segStart->format('H:i'),
                    // Reaching midnight is stored as 23:59 — `H:i` has no 24:00.
                    'end_time' => $segEnd->eq($dayEnd) ? '23:59' : $segEnd->format('H:i'),
                    'label' => $shift->title,
                ];
            }
        }

        usort($segments, fn ($a, $b) => [$a['date'], $a['start_time']] <=> [$b['date'], $b['start_time']]);

        return $segments;
    }
}
