<?php

namespace Modules\Shift\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Shift\Http\Resources\AssignmentResource;
use Modules\Shift\Models\Assignment;
use Modules\Shift\Models\Shift;
use Modules\Shift\Models\ShiftInterest;

class AssignmentController extends Controller
{
    use ApiResponse;

    public function index(Shift $shift)
    {
        $assignments = $shift->assignments()->with('worker')->get();

        return $this->success(AssignmentResource::collection($assignments));
    }

    /**
     * This is the endpoint that answers "can a Company Admin/Dispatcher
     * assign work to a worker" — yes: requires shifts.dispatch (route-level).
     * A worker doesn't need to have expressed interest first — a dispatcher
     * can assign directly — but if a pending interest exists, it's marked
     * "converted" so it stops showing up as still-pending.
     */
    public function store(Request $request, Shift $shift)
    {
        if ($shift->isFull()) {
            return $this->error('This shift already has enough confirmed workers.', 422);
        }

        $data = $request->validate([
            'worker_id' => ['required', 'integer', 'exists:users,id'],
            'transport_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $existing = Assignment::where('shift_id', $shift->id)
            ->where('worker_id', $data['worker_id'])
            ->whereIn('status', ['pending_worker_confirmation', 'confirmed'])
            ->first();

        if ($existing) {
            return $this->error('This worker is already assigned to this shift.', 422);
        }

        $assignment = Assignment::create([
            'shift_id' => $shift->id,
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

        return $this->success(new AssignmentResource($assignment), 'Worker assigned', 201);
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
