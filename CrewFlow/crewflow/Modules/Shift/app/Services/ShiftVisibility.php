<?php

namespace Modules\Shift\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Authentication\Models\User;
use Modules\Employee\Models\CompanyWorker;
use Modules\Employee\Models\Worker;
use Modules\Employee\Models\WorkerQualification;
use Modules\Shift\Models\EventWorkerAccess;
use Modules\Shift\Models\Shift;

/**
 * Applies the project's shift-visibility rule for a Worker (anyone
 * without shifts.dispatch — dispatchers/admins always see everything,
 * unfiltered, since they need full visibility to manage). A Shift is
 * visible only if BOTH of these hold:
 *
 * 1. ACCESS — either:
 *    a) the Shift's own branch is the worker's home branch (default), OR
 *    b) the worker has been explicitly activated for that Shift's Event
 *       via EventWorkerAccess (only possible after that worker's branch
 *       was granted EventBranchAccess in the first place — enforced at
 *       write-time in EventWorkerAccessController, not re-checked here).
 *
 * 2. QUALIFICATION — one of:
 *    - the Shift's `qualification_policy` is `override` or `warn` (both
 *      bypass this check entirely for visibility purposes — they only
 *      differ in what happens next: `warn` still flags the mismatch for
 *      a dispatcher later, via `workerQualifies()` below; `override`
 *      never flags anything), OR
 *    - (policy is `strict`, the default) the Shift has no positions at
 *      all (plain `quantity_needed`) and the worker holds every
 *      shift-wide requirement (or there are none), OR
 *    - (policy is `strict`) the Shift HAS positions, and the worker
 *      holds every requirement for AT LEAST ONE of them. Different
 *      positions on the same shift can need completely independent,
 *      unrelated qualifications (e.g. a combined "Math & English" class
 *      needs Math for one role and English for the other) — qualifying
 *      for just one role is enough to see the shift at all; nothing
 *      requires satisfying every role. Once a Shift has positions,
 *      shift-wide requirements (if any still exist from before
 *      positions were added) are ignored — define requirements
 *      per-position instead.
 *
 * Failing either check means the shift is hidden entirely — never shown
 * disabled/greyed out, per this project's explicit design choice. This
 * only applies under `strict`; `override`/`warn` are opt-in exceptions
 * an admin sets per-shift.
 */
class ShiftVisibility
{
    public static function scopeFor(Builder $query, User $worker): Builder
    {
        // Worker's home branch now lives on CompanyWorker (the employment
        // relationship), not directly on Worker (personal facts) — see
        // the Employee module's README for the full worker/company_worker/
        // employment_contract split.
        $workerRecord = Worker::where('user_id', $worker->id)->first();
        $homeBranchId = $workerRecord
            ? CompanyWorker::where('worker_id', $workerRecord->id)->value('home_branch_id')
            : null;

        $accessibleEventIds = EventWorkerAccess::where('worker_id', $worker->id)->pluck('event_id');

        $heldQualificationIds = WorkerQualification::where('worker_id', $worker->id)->pluck('qualification_id');

        return $query
            ->where(function (Builder $q) use ($homeBranchId, $accessibleEventIds) {
                $q->where('branch_id', $homeBranchId);

                if ($accessibleEventIds->isNotEmpty()) {
                    $q->orWhereIn('event_id', $accessibleEventIds);
                }
            })
            ->where(function (Builder $q) use ($heldQualificationIds) {
                $q->whereIn('qualification_policy', ['override', 'warn'])
                    // No positions at all: shift-wide requirements gate
                    // it (or there are none).
                    ->orWhere(function (Builder $noPositions) use ($heldQualificationIds) {
                        $noPositions->whereDoesntHave('positions')
                            ->where(function (Builder $shiftLevel) use ($heldQualificationIds) {
                                $shiftLevel->whereDoesntHave(
                                    'requiredQualifications',
                                    fn (Builder $r) => $r->whereNull('shift_position_id')
                                )->orWhereDoesntHave('requiredQualifications', function (Builder $r) use ($heldQualificationIds) {
                                    $r->whereNull('shift_position_id')->whereNotIn('qualification_id', $heldQualificationIds);
                                });
                            });
                    })
                    // Has positions: satisfying just ONE role's full
                    // requirement set is enough.
                    ->orWhereHas('positions', function (Builder $pos) use ($heldQualificationIds) {
                        $pos->whereDoesntHave('requiredQualifications', function (Builder $r) use ($heldQualificationIds) {
                            $r->whereNotIn('qualification_id', $heldQualificationIds);
                        });
                    });
            });
    }

    /**
     * A single worker/shift boolean check — NOT a query scope — used to
     * flag a dispatcher-facing warning on a `warn`-policy Shift's
     * interests/assignments ("this worker doesn't actually hold the
     * qualification this position needs"). Same underlying rule as
     * scopeFor()'s qualification branch, just evaluated for one worker
     * against one already-loaded Shift instead of filtering a query.
     * Always returns true for `strict`/`override` shifts — the warning
     * only means something on a `warn` shift, where an otherwise-hidden
     * worker was deliberately let through.
     */
    public static function workerQualifies(Shift $shift, int $workerId): bool
    {
        $heldQualificationIds = WorkerQualification::where('worker_id', $workerId)->pluck('qualification_id');

        $positions = $shift->positions()->with('requiredQualifications')->get();

        if ($positions->isEmpty()) {
            $required = $shift->shiftLevelQualifications()->pluck('qualification_id');

            return $required->diff($heldQualificationIds)->isEmpty();
        }

        foreach ($positions as $position) {
            $required = $position->requiredQualifications->pluck('qualification_id');

            if ($required->diff($heldQualificationIds)->isEmpty()) {
                return true;
            }
        }

        return false;
    }
}
