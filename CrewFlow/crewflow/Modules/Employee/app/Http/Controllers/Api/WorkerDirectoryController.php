<?php

namespace Modules\Employee\Http\Controllers\Api;

use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Employee\Http\Resources\WorkerDirectoryResource;
use Modules\Employee\Http\Resources\WorkerExpiringDocumentResource;
use Modules\Employee\Models\Worker;

/**
 * A dispatcher-facing search/filter directory — deliberately distinct
 * from Authentication's Users listing (an access-control concern gated
 * by users.manage, usually admin-only). This is about finding the right
 * worker to staff a shift: by qualification, home branch, contract
 * terms, and availability at a specific day/time. Gated by
 * shifts.dispatch, so both Company Admin and Dispatcher can use it —
 * see routes/api.php.
 */
class WorkerDirectoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Worker::query()
            ->with(['user', 'companyWorker.homeBranch', 'companyWorker.contracts', 'qualifications.qualification', 'availability']);

        if ($request->filled('branch_id')) {
            $query->whereHas('companyWorker', function ($q) use ($request) {
                $q->where('home_branch_id', $request->integer('branch_id'));
            });
        }

        if ($request->boolean('night_shift')) {
            $query->whereHas('companyWorker', function ($q) {
                $q->where('works_night_shifts', true);
            });
        }

        // Filter by work_authorization_status directly — e.g. ?work_
        // authorization_status=expired surfaces exactly the workers
        // ExpireWorkAuthorizations just flipped, right alongside every
        // other search/filter this directory already supports (branch,
        // qualification, availability, ...), rather than only being
        // visible on the separate expiring-documents dashboard list.
        if ($request->filled('work_authorization_status')) {
            $query->where('work_authorization_status', $request->query('work_authorization_status'));
        }

        // NOTE: $request->string() returns a Stringable OBJECT, not a
        // plain string — using it as an Eloquent where() value can fail
        // to bind/match correctly. $request->query() returns the raw
        // string instead (same fix as WorkerDocumentController::types(),
        // CustomFieldDefinitionController, and CustomDocumentTypeController).
        if ($request->filled('contract_type') || $request->filled('work_time_model')) {
            $query->whereHas('companyWorker.contracts', function ($q) use ($request) {
                $q->where('status', 'active');
                if ($request->filled('contract_type')) {
                    $q->where('contract_type', $request->query('contract_type'));
                }
                if ($request->filled('work_time_model')) {
                    $q->where('work_time_model', $request->query('work_time_model'));
                }
            });
        }

        // Only workers who are actually assignable right now: active
        // employment relationship, an active (non-expired) contract,
        // valid (or not-required) work authorization, AND — if a
        // work_authorization_expiry_date is on file — that it hasn't
        // already passed. Mirrors WorkerEligibility::isAssignable()
        // exactly (see that class's docblock for why the expiry check
        // is independent of the status check).
        if ($request->boolean('eligible')) {
            $query->where('status', 'active')
                ->whereIn('work_authorization_status', ['valid', 'not_required'])
                ->where(function ($w) {
                    $w->whereNull('work_authorization_expiry_date')
                        ->orWhereDate('work_authorization_expiry_date', '>=', now()->toDateString());
                })
                ->whereHas('companyWorker', function ($q) {
                    $q->where('status', 'active')
                        ->whereHas('contracts', function ($c) {
                            $c->where('status', 'active')
                                ->where(function ($d) {
                                    $d->whereNull('end_date')->orWhereDate('end_date', '>=', now()->toDateString());
                                });
                        });
                });
        }

        if ($request->filled('qualification_id')) {
            $query->whereHas('qualifications', function ($q) use ($request) {
                $q->where('qualification_id', $request->integer('qualification_id'));
            });
        }

        // Both day_of_week (0=Sunday..6=Saturday) and time (HH:MM) must be
        // given together — a worker matches if they have an availability
        // slot on that day spanning that time.
        if ($request->filled('day_of_week') && $request->filled('time')) {
            $query->whereHas('availability', function ($q) use ($request) {
                $q->where('day_of_week', $request->integer('day_of_week'))
                    ->where('start_time', '<=', $request->query('time'))
                    ->where('end_time', '>=', $request->query('time'));
            });
        }

        if ($request->filled('search')) {
            // Safe as-is, unlike the where() calls above — string
            // interpolation ("%{$search}%") calls Stringable's own
            // __toString() automatically, so this never hits the same
            // binding issue.
            $search = $request->string('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('personnel_number', 'like', "%{$search}%");
            })->orWhereHas('companyWorker', function ($q) use ($search) {
                $q->where('employee_number', 'like', "%{$search}%");
            });
        }

        return $this->success(WorkerDirectoryResource::collection($query->get()));
    }

    /**
     * Powers the admin dashboard's "expiring/expired documents" list —
     * every worker with a work_authorization_expiry_date already past,
     * OR due within the next 30 days, oldest/most-urgent first. This is
     * deliberately a plain list an admin sees on every login, not an
     * email — see the Authentication module if that's ever wanted later
     * (nothing here sends anything; it's a pull, not a push).
     *
     * 30 days is a fixed threshold for now, not a per-company setting.
     */
    public function expiringDocuments(Request $request)
    {
        $threshold = now()->addDays(30)->toDateString();

        $workers = Worker::with('user')
            ->whereNotNull('work_authorization_expiry_date')
            ->whereDate('work_authorization_expiry_date', '<=', $threshold)
            ->orderBy('work_authorization_expiry_date')
            ->get();

        return $this->success(WorkerExpiringDocumentResource::collection($workers));
    }
}
