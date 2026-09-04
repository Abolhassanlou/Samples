<?php

namespace Modules\Shift\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Shift\Http\Resources\AssignmentResource;
use Modules\Shift\Models\Assignment;
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

        return $this->success(new AssignmentResource($assignment->load('position.role')), 'Worker assigned', 201);
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

        $assignment->update(['status' => 'confirmed', 'confirmed_at' => now()]);

        $shift = $assignment->shift;
        if ($shift->isFull()) {
            $shift->update(['status' => 'filled']);
        }

        return $this->success(new AssignmentResource($assignment), 'Assignment confirmed');
    }
}
