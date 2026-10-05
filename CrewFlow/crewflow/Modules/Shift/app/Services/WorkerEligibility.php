<?php

namespace Modules\Shift\Services;

use Modules\Employee\Models\Worker;

/**
 * The actual gate on Shift assignment (see AssignmentController::store).
 * A worker is only assignable if ALL of these hold:
 *
 * 1. Worker.status is "active" (not pending/inactive/blocked)
 * 2. work_authorization_status is "valid" or "not_required"
 * 3. work_authorization_expiry_date, if set, has NOT already passed —
 *    checked independently of #2 as a safety net: an admin can forget
 *    to flip status to "expired" the day a document actually lapses,
 *    and this catches that regardless of what status still says. Also
 *    applies when status is "not_required" — a passport/ID still
 *    expires even for an EU/EEA/Swiss/Austrian citizen who needs no
 *    visa (see PersonalDetailsForm.vue in worker-portal), so an expiry
 *    date on file always matters once it's in the past, whatever the
 *    reason a date was recorded in the first place.
 * 4. their CompanyWorker.status is "active"
 * 5. they have at least one EmploymentContract that is currently active
 *    (status=active AND not past its end_date) — see
 *    EmploymentContract::isCurrentlyActive()
 *
 * This is deliberately strict — no fallback for a worker with no Worker/
 * CompanyWorker/contract record at all; they're simply not assignable
 * until an admin sets them up properly.
 */
class WorkerEligibility
{
    public static function isAssignable(int $userId): bool
    {
        $worker = Worker::with('companyWorker.contracts')->where('user_id', $userId)->first();

        if (! $worker || $worker->status !== 'active') {
            return false;
        }

        if (! in_array($worker->work_authorization_status, ['valid', 'not_required'], true)) {
            return false;
        }

        if ($worker->work_authorization_expiry_date && $worker->work_authorization_expiry_date->isPast()) {
            return false;
        }

        $companyWorker = $worker->companyWorker;

        if (! $companyWorker || $companyWorker->status !== 'active') {
            return false;
        }

        return $companyWorker->contracts->contains(fn ($contract) => $contract->isCurrentlyActive());
    }
}
