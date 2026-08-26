<?php

namespace Modules\Shift\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Shift\Http\Resources\ShiftInterestResource;
use Modules\Shift\Models\Shift;
use Modules\Shift\Models\ShiftInterest;
use Modules\Shift\Models\ShiftPosition;

class ShiftInterestController extends Controller
{
    use ApiResponse;

    /**
     * Dispatcher's view of who's interested (pending) or waiting
     * (waitlisted) for a shift, before deciding who to assign. Requires
     * shifts.dispatch (route-level).
     */
    public function index(Shift $shift)
    {
        $interests = $shift->interests()
            ->with(['worker', 'position.role'])
            ->whereIn('status', ['pending', 'waitlisted'])
            ->get();

        return $this->success(ShiftInterestResource::collection($interests));
    }

    /**
     * A worker expresses interest — optionally in a specific role, if the
     * shift has positions. No permission required beyond being an
     * authenticated company user. If the shift/position is already full,
     * this still records the interest, just as "waitlisted" instead of
     * outright rejecting it — the worker is queued in case a spot opens
     * up (e.g. someone else's assignment gets cancelled).
     */
    public function store(Request $request, Shift $shift)
    {
        $data = $request->validate([
            'shift_position_id' => ['nullable', 'integer', 'exists:shift_positions,id'],
        ]);

        $position = null;
        $isFull = false;

        if ($shift->hasPositions()) {
            if (empty($data['shift_position_id'])) {
                return $this->error('This shift has specific roles — shift_position_id is required.', 422);
            }

            $position = ShiftPosition::where('id', $data['shift_position_id'])->where('shift_id', $shift->id)->first();

            if (! $position) {
                return $this->error('That position does not belong to this shift.', 422);
            }

            $isFull = $position->isFull();
        } else {
            $isFull = $shift->isFull();
        }

        $status = $isFull ? 'waitlisted' : 'pending';

        $interest = ShiftInterest::where('shift_id', $shift->id)
            ->where('worker_id', $request->user()->id)
            ->where('shift_position_id', $position?->id)
            ->first();

        if ($interest) {
            $interest->update(['status' => $status, 'expressed_at' => now(), 'withdrawn_at' => null]);
        } else {
            $interest = ShiftInterest::create([
                'shift_id' => $shift->id,
                'shift_position_id' => $position?->id,
                'worker_id' => $request->user()->id,
                'expressed_at' => now(),
                'status' => $status,
            ]);
        }

        $message = $isFull ? 'Added to the waitlist — this shift/position is currently full.' : 'Interest expressed';

        return $this->success(new ShiftInterestResource($interest->load('position.role')), $message, 201);
    }

    /**
     * Free withdrawal, any time before a dispatcher converts this interest
     * into an Assignment. Works whether the interest is "pending" or
     * "waitlisted".
     */
    public function destroy(Request $request, Shift $shift)
    {
        $interest = ShiftInterest::where('shift_id', $shift->id)
            ->where('worker_id', $request->user()->id)
            ->whereIn('status', ['pending', 'waitlisted'])
            ->first();

        if (! $interest) {
            return $this->error('No active interest found to withdraw.', 404);
        }

        $interest->update(['status' => 'withdrawn', 'withdrawn_at' => now()]);

        return $this->success(null, 'Interest withdrawn');
    }
}
