<?php

namespace Modules\Shift\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Employee\Models\CompanyWorker;
use Modules\Employee\Models\Worker as EmployeeWorker;
use Modules\Shift\Http\Resources\AssignmentResource;
use Modules\Shift\Models\Assignment;
use Modules\Shift\Models\Event;
use Modules\Shift\Models\Shift;
use Modules\Shift\Models\ShiftInterest;
use Modules\Shift\Models\ShiftPosition;
use Modules\Shift\Services\WorkerEligibility;

class AssignmentController extends Controller
{
    use ApiResponse;

    public function index(Shift $shift)
    {
        $assignments = $shift->assignments()->with(['worker', 'position.role'])->get();
        $assignments->each(fn ($a) => $a->setRelation('shift', $shift));

        return $this->success(AssignmentResource::collection($assignments));
    }

    /**
     * A worker's own assignments across every shift — what the Jobs tab
     * uses to show "your upcoming work" alongside the browsable list of
     * open shifts. Self-scoped, no permission required beyond being
     * authenticated (this is never someone else's data). Shift is eager-
     * loaded (with its own event) since the frontend needs title/dates/
     * location to actually display anything useful, not just an id.
     */
    public function mine(Request $request)
    {
        $assignments = Assignment::where('worker_id', $request->user()->id)
            ->whereIn('status', ['pending_worker_confirmation', 'confirmed'])
            ->with(['shift', 'position.role'])
            ->orderBy('assigned_at', 'desc')
            ->get();

        return $this->success(AssignmentResource::collection($assignments));
    }

    /**
     * This is the endpoint that answers "can a Company Admin/Dispatcher
     * assign work to a worker" — yes: requires shifts.dispatch (route-level).
     * A worker doesn't need to have expressed interest first — a dispatcher
     * can assign directly — but if a pending interest exists, it's marked
     * "converted" so it stops showing up as still-pending.
     *
     * If the Shift has role-specific positions (see ShiftPosition), the
     * request must say which one this assignment fills. If it doesn't
     * have any positions, works exactly as before (plain headcount).
     */
    public function store(Request $request, Shift $shift)
    {
        $data = $request->validate([
            'worker_id' => ['required', 'integer', 'exists:users,id'],
            'shift_position_id' => ['nullable', 'integer', 'exists:shift_positions,id'],
            'transport_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (! WorkerEligibility::isAssignable($data['worker_id'])) {
            return $this->error('This worker is not currently eligible for assignment — check their status, work authorization, and whether they have an active contract.', 422);
        }

        $position = null;

        if ($shift->hasPositions()) {
            if (empty($data['shift_position_id'])) {
                return $this->error('This shift has specific roles — shift_position_id is required.', 422);
            }

            $position = ShiftPosition::where('id', $data['shift_position_id'])->where('shift_id', $shift->id)->first();

            if (! $position) {
                return $this->error('That position does not belong to this shift.', 422);
            }

            if ($position->isFull()) {
                return $this->error('This position is already full.', 422);
            }
        } elseif ($shift->isFull()) {
            return $this->error('This shift already has enough confirmed workers.', 422);
        }

        $existing = Assignment::where('shift_id', $shift->id)
            ->where('worker_id', $data['worker_id'])
            ->whereIn('status', ['pending_worker_confirmation', 'confirmed'])
            ->first();

        if ($existing) {
            return $this->error('This worker is already assigned to this shift.', 422);
        }

        $assignment = Assignment::create([
            'shift_id' => $shift->id,
            'shift_position_id' => $position?->id,
            'worker_id' => $data['worker_id'],
            'assigned_by' => $request->user()->id,
            'assigned_at' => now(),
            'transport_amount' => $data['transport_amount'] ?? null,
            'status' => 'pending_worker_confirmation',
        ]);

        ShiftInterest::where('shift_id', $shift->id)
            ->where('worker_id', $data['worker_id'])
            ->where('status', 'pending')
            ->update(['status' => 'converted']);

        if ($shift->status === 'open') {
            $shift->update(['status' => 'partially_filled']);
        }

        if ($shift->event_id && $shift->event?->requires_contract) {
            $this->ensureAssignmentNotice($shift->event, $data['worker_id']);
        }

        return $this->success(new AssignmentResource($assignment->load('position.role')), 'Worker assigned', 201);
    }

    /**
     * The Employee module's per-placement Überlassungsmitteilung — auto-
     * created (once per worker per event, never duplicated) whenever a
     * worker is assigned to a Shift under an Event with
     * requires_contract=true. This does NOT gate the assignment itself
     * (WorkerEligibility, checked above, already required an active
     * *general* contract before we even got here) — it's an additional
     * per-event document the worker signs separately. Starts with no
     * file attached; an admin/dispatcher should attach the actual
     * notification document shortly after, before expecting a signature.
     * See the Employee module's README for the full rationale.
     */
    private function ensureAssignmentNotice(Event $event, int $workerId): void
    {
        $worker = EmployeeWorker::firstOrCreate(['user_id' => $workerId]);
        $companyWorker = CompanyWorker::firstOrCreate(['worker_id' => $worker->id]);

        $companyWorker->contracts()->firstOrCreate(
            ['event_id' => $event->id, 'contract_type' => 'assignment_notice'],
            [
                'work_time_model' => 'casual',
                'start_date' => $event->starts_at->toDateString(),
                'end_date' => $event->ends_at->toDateString(),
                'status' => 'pending_signature',
            ]
        );
    }

    /**
     * The worker's own final confirmation of an assignment made for them.
     */
    public function confirm(Request $request, Assignment $assignment)
    {
        if ($assignment->worker_id !== $request->user()->id) {
            return $this->error('You can only confirm your own assignments.', 403);
        }

        if ($assignment->status !== 'pending_worker_confirmation') {
            return $this->error('This assignment is not awaiting confirmation.', 422);
        }

        // change_note (if set by a shift edit that required
        // re-confirmation — see ShiftController::update()) is cleared
        // here too, so it doesn't linger and show stale "what changed"
        // info once the worker has actually looked and re-confirmed.
        $assignment->update(['status' => 'confirmed', 'confirmed_at' => now(), 'change_note' => null]);

        $shift = $assignment->shift;
        if ($shift->isFull()) {
            $shift->update(['status' => 'filled']);
        }

        return $this->success(new AssignmentResource($assignment), 'Assignment confirmed');
    }

    /**
     * A dispatcher/admin removing an assignment directly — independent
     * of the worker-initiated cancellation-request flow (see
     * CancellationRequestController), which needs separate approval.
     * This is immediate, no approval step, since the dispatcher is the
     * one doing it. Soft — sets status to "cancelled" rather than a
     * real row delete, so it stays visible in history (matches the
     * "cancelled" value Assignment.status already supports). If the
     * shift had been marked "filled", it reopens.
     */
    public function destroy(Request $request, Assignment $assignment)
    {
        if (! in_array($assignment->status, ['pending_worker_confirmation', 'confirmed'])) {
            return $this->error('This assignment is not currently active.', 422);
        }

        $assignment->update(['status' => 'cancelled']);

        $shift = $assignment->shift;
        if ($shift->status === 'filled') {
            $shift->update(['status' => 'partially_filled']);
        }

        return $this->success(null, 'Assignment cancelled');
    }
}
