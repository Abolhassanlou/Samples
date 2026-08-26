<?php

namespace Modules\Shift\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Shift\Http\Resources\CancellationRequestResource;
use Modules\Shift\Models\Assignment;
use Modules\Shift\Models\CancellationRequest;

class CancellationRequestController extends Controller
{
    use ApiResponse;

    /**
     * The dispatcher's queue of pending cancellation requests to review.
     * Requires shifts.dispatch (route-level).
     */
    public function index()
    {
        $requests = CancellationRequest::where('status', 'pending')
            ->with(['worker', 'assignment.shift'])
            ->orderBy('is_urgent', 'desc')
            ->orderBy('created_at')
            ->get();

        return $this->success(CancellationRequestResource::collection($requests));
    }

    /**
     * A worker formally requesting to cancel their own assignment. Only
     * the assigned worker may request this — not a dispatcher on their
     * behalf, since this specifically represents the WORKER backing out.
     * Urgent (< 24h before the shift starts) requires a reason.
     */
    public function store(Request $request, Assignment $assignment)
    {
        if ($assignment->worker_id !== $request->user()->id) {
            return $this->error('You can only request cancellation of your own assignment.', 403);
        }

        if (! in_array($assignment->status, ['pending_worker_confirmation', 'confirmed'])) {
            return $this->error('This assignment cannot be cancelled from its current status.', 422);
        }

        $existing = CancellationRequest::where('assignment_id', $assignment->id)->where('status', 'pending')->first();
        if ($existing) {
            return $this->error('A cancellation request for this assignment is already pending.', 422);
        }

        $hoursBeforeShift = now()->diffInHours($assignment->shift->starts_at, false);
        $isUrgent = $hoursBeforeShift < 24;

        $data = $request->validate([
            'reason' => [$isUrgent ? 'required' : 'nullable', 'string', 'max:500'],
        ]);

        $cancellationRequest = CancellationRequest::create([
            'assignment_id' => $assignment->id,
            'worker_id' => $request->user()->id,
            'reason' => $data['reason'] ?? null,
            'is_urgent' => $isUrgent,
            'status' => 'pending',
        ]);

        return $this->success(new CancellationRequestResource($cancellationRequest), 'Cancellation request submitted', 201);
    }

    /**
     * Dispatcher approves: the Assignment is cancelled and its slot
     * (Shift or ShiftPosition, whichever applies) automatically reopens
     * — Shift::isFull()/ShiftPosition::isFull() only count "confirmed"
     * assignments, so cancelling one is enough; we just also correct the
     * Shift's own status label if it was "filled".
     */
    public function approve(Request $request, CancellationRequest $cancellationRequest)
    {
        if ($cancellationRequest->status !== 'pending') {
            return $this->error('This request has already been processed.', 422);
        }

        $assignment = $cancellationRequest->assignment;
        $assignment->update(['status' => 'cancelled']);

        $cancellationRequest->update([
            'status' => 'approved',
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);

        $shift = $assignment->shift;
        if (! $shift->isFull()) {
            $shift->update(['status' => $shift->confirmedAssignmentsCount() > 0 ? 'partially_filled' : 'open']);
        }

        return $this->success(new CancellationRequestResource($cancellationRequest), 'Cancellation approved, slot reopened');
    }

    /**
     * Dispatcher rejects: the Assignment is untouched, worker is still
     * expected to show up.
     */
    public function reject(Request $request, CancellationRequest $cancellationRequest)
    {
        if ($cancellationRequest->status !== 'pending') {
            return $this->error('This request has already been processed.', 422);
        }

        $cancellationRequest->update([
            'status' => 'rejected',
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);

        return $this->success(new CancellationRequestResource($cancellationRequest), 'Cancellation rejected');
    }
}
