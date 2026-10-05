<?php

namespace Modules\Employee\Contracts;

use Carbon\Carbon;

/**
 * "Which hours is this worker already booked for?" — the one fact the
 * availability editor needs from outside this module. An availability
 * slot a shift is booked into can't be removed (a worker can't un-offer
 * time they've been committed to), so WorkerAvailabilityController asks
 * for the reserved hours in a date range and refuses to drop them.
 *
 * Employee deliberately doesn't know what a "booking" is — that's the
 * Shift module's Assignment. Shift already depends on Employee
 * (WorkerEligibility reads Worker), so Employee importing Shift's models
 * would make the two depend on each other. Instead this contract lives
 * here and Shift implements and binds it (ShiftServiceProvider::
 * register()); NullReservedTimeProvider is the default when nothing else
 * is bound, so Employee still works standing alone.
 */
interface ReservedTimeProvider
{
    /**
     * Reserved stretches for one worker whose dates fall in [$from, $to]
     * (both inclusive, compared by calendar date). A booking that spans
     * midnight comes back as one segment per date. `end_time` is 'H:i',
     * with 23:59 standing in for "until midnight" (same convention as
     * availability rows — `H:i` has no 24:00).
     *
     * @return array<int, array{date: string, start_time: string, end_time: string, label: string}>
     */
    public function segments(int $workerId, Carbon $from, Carbon $to): array;
}
