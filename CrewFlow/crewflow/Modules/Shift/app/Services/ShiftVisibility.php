<?php

namespace Modules\Shift\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Authentication\Models\User;
use Modules\Employee\Models\CompanyWorker;
use Modules\Employee\Models\Worker;
use Modules\Employee\Models\WorkerQualification;
use Modules\Shift\Models\EventWorkerAccess;

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
 * 2. QUALIFICATION — the worker holds EVERY qualification the Shift
 *    requires (ShiftQualification), UNLESS the Shift itself has
 *    `qualification_override` set — a deliberate escape hatch for
 *    staffing shortages (e.g. an unpopular night shift), which skips
 *    this check entirely regardless of what's actually required. A
 *    Shift with no requirements at all is visible to anyone who passes
 *    the access check either way.
 *
 * Failing either check means the shift is hidden entirely — never shown
 * disabled/greyed out, per this project's explicit design choice.
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
                $q->where('qualification_override', true)
                    ->orWhereDoesntHave('requiredQualifications')
                    ->orWhereDoesntHave('requiredQualifications', function (Builder $sub) use ($heldQualificationIds) {
                        $sub->whereNotIn('qualification_id', $heldQualificationIds);
                    });
            });
    }
}
