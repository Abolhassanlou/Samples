<?php

namespace Modules\Employee\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Models\User;
use Modules\Core\Traits\ApiResponse;
use Modules\Employee\Contracts\ReservedTimeProvider;
use Modules\Employee\Http\Requests\WorkerAvailabilityRequest;
use Modules\Employee\Http\Requests\WorkerAvailabilityWeekRequest;
use Modules\Employee\Http\Resources\WorkerAvailabilityResource;
use Modules\Employee\Models\WorkerAvailability;
use Modules\Employee\Services\AvailabilityIntervals;
use Modules\Employee\Services\AvailabilityRepeat;

/**
 * Two kinds of availability row share worker_availabilities (see the
 * add_date migration): weekly template rows (`date` NULL — "every
 * Tuesday 18:00–22:00", written by sync()), and dated rows (`date` set —
 * "on 2026-10-06, 18:00–22:00", written by syncWeeks()). Each endpoint
 * only ever touches its own kind, so neither can wipe the other.
 */
class WorkerAvailabilityController extends Controller
{
    use ApiResponse;

    /**
     * Without `from`, returns only the weekly template rows — exactly
     * what this endpoint returned before dated rows existed, so existing
     * callers are unaffected. With `from` (and optionally `to`, defaults
     * to the same day), returns the dated rows in that inclusive range —
     * what worker-portal's weekly grid loads for the week on screen.
     */
    public function index(Request $request, User $user)
    {
        $query = WorkerAvailability::where('worker_id', $user->id);

        if ($request->filled('from')) {
            $request->validate([
                'from' => ['date_format:Y-m-d'],
                'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            ]);

            $from = $request->query('from');
            $query->whereBetween('date', [$from, $request->query('to', $from)]);
        } else {
            $query->whereNull('date');
        }

        $slots = $query->orderBy('date')->orderBy('day_of_week')->orderBy('start_time')->get();

        return $this->success(WorkerAvailabilityResource::collection($slots));
    }

    /**
     * Full replace of the worker's WEEKLY TEMPLATE only: a worker declares
     * their whole standing weekly availability in one request rather than
     * adding/removing slots one at a time. Dated rows (see syncWeeks) are
     * deliberately left alone — before they existed this wiped every row
     * this worker had, which would now silently erase weeks of dated
     * availability the next time anything posted a weekly template.
     */
    public function sync(WorkerAvailabilityRequest $request, User $user)
    {
        abort_unless(
            $request->user()->id === $user->id || $request->user()->can('users.manage'),
            403
        );

        WorkerAvailability::where('worker_id', $user->id)->whereNull('date')->delete();

        $slots = collect($request->validated('slots'))->map(fn ($slot) => WorkerAvailability::create([
            'worker_id' => $user->id,
            'day_of_week' => $slot['day_of_week'],
            'start_time' => $slot['start_time'],
            'end_time' => $slot['end_time'],
        ]));

        return $this->success(WorkerAvailabilityResource::collection($slots), 'Availability updated');
    }

    /**
     * Saves ONE week's pattern and, per `repeat`, copies it forward — this
     * is what worker-portal's "Save → repeat for 4 weeks / 4 months /
     * until a date?" prompt calls. The pattern (`slots`, for the week
     * starting `week_start`) is expanded into one dated row per matching
     * day across the whole span, and every dated row this worker already
     * had inside that span is deleted first — so a repeat REPLACES those
     * dates rather than stacking on top of them, and an empty pattern
     * clears them (how a worker marks time off). Dates outside the span
     * and the weekly template rows are never touched. All-or-nothing in
     * one transaction.
     *
     * Hours a shift is already booked into (ReservedTimeProvider — in
     * practice an active assignment) can never be dropped, even by a
     * pattern that leaves them out or a repeat that sweeps over them:
     * they're merged back in as available. The editor locks those cells
     * so this is normally a no-op, but a stale screen (a booking made
     * after it loaded) or a repeat across weeks the worker isn't looking
     * at would otherwise silently un-offer committed time. `reserved_kept`
     * in the response counts the bookings that needed this.
     *
     * The span's last date comes from AvailabilityRepeat::endDate(); the
     * request has already checked the week starts on a Monday, isn't in
     * the past, and the span fits within a year.
     */
    public function syncWeeks(WorkerAvailabilityWeekRequest $request, User $user)
    {
        abort_unless(
            $request->user()->id === $user->id || $request->user()->can('users.manage'),
            403
        );

        $weekStart = Carbon::createFromFormat('Y-m-d', $request->validated('week_start'))->startOfDay();
        $end = AvailabilityRepeat::endDate($weekStart, $request->validated('repeat'));
        $slotsByDay = collect($request->validated('slots'))->groupBy('day_of_week');

        $reservedByDate = collect(app(ReservedTimeProvider::class)->segments($user->id, $weekStart, $end))
            ->groupBy('date');

        $now = now();
        $rows = [];
        $reservedKept = 0;

        for ($date = $weekStart->copy(); $date->lte($end); $date->addDay()) {
            $requested = [];
            foreach ($slotsByDay->get($date->dayOfWeek, []) as $slot) {
                $requested[] = [
                    AvailabilityIntervals::toMinutes($slot['start_time']),
                    AvailabilityIntervals::toMinutes($slot['end_time'], true),
                ];
            }

            $reserved = [];
            foreach ($reservedByDate->get($date->toDateString(), []) as $segment) {
                $reserved[] = [
                    AvailabilityIntervals::toMinutes($segment['start_time']),
                    AvailabilityIntervals::toMinutes($segment['end_time'], true),
                ];
            }

            // A booking the request already covers needs no rescuing; count
            // only the ones this save would otherwise have removed.
            $mergedRequest = AvailabilityIntervals::merge($requested);
            foreach ($reserved as $booking) {
                if (! AvailabilityIntervals::covers($mergedRequest, $booking)) {
                    $reservedKept++;
                }
            }

            foreach (AvailabilityIntervals::merge([...$requested, ...$reserved]) as [$start, $finish]) {
                $rows[] = [
                    'worker_id' => $user->id,
                    'date' => $date->toDateString(),
                    'day_of_week' => $date->dayOfWeek,
                    'start_time' => AvailabilityIntervals::format($start),
                    'end_time' => AvailabilityIntervals::format($finish, true),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::transaction(function () use ($user, $weekStart, $end, $rows) {
            WorkerAvailability::where('worker_id', $user->id)
                ->whereBetween('date', [$weekStart->toDateString(), $end->toDateString()])
                ->delete();

            foreach (array_chunk($rows, 500) as $chunk) {
                WorkerAvailability::insert($chunk);
            }
        });

        return $this->success([
            'from' => $weekStart->toDateString(),
            'to' => $end->toDateString(),
            'slots_created' => count($rows),
            'reserved_kept' => $reservedKept,
        ], 'Availability updated');
    }

    /**
     * The worker's booked hours in an inclusive date range — what the
     * availability editor locks. Self or users.manage, like writing.
     */
    public function reserved(Request $request, User $user)
    {
        abort_unless(
            $request->user()->id === $user->id || $request->user()->can('users.manage'),
            403
        );

        $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = Carbon::createFromFormat('Y-m-d', $request->query('from'))->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $request->query('to', $request->query('from')))->startOfDay();

        return $this->success(app(ReservedTimeProvider::class)->segments($user->id, $from, $to));
    }
}
