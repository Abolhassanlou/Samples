<?php

namespace Modules\Shift\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Shift\Http\Resources\ShiftInterestResource;
use Modules\Shift\Models\Shift;
use Modules\Shift\Models\ShiftInterest;

class ShiftInterestController extends Controller
{
    use ApiResponse;

    /**
     * Dispatcher's view of who's interested in a shift, before deciding
     * who to assign. Requires shifts.dispatch (route-level).
     */
    public function index(Shift $shift)
    {
        $interests = $shift->interests()
            ->with('worker')
            ->where('status', 'pending')
            ->get();

        return $this->success(ShiftInterestResource::collection($interests));
    }

    /**
     * A worker expresses interest. Free to do any time before the shift
     * is full — no permission required beyond being an authenticated
     * company user.
     */
    public function store(Request $request, Shift $shift)
    {
        if ($shift->isFull()) {
            return $this->error('This shift is already full.', 422);
        }

        $interest = ShiftInterest::firstOrCreate(
            ['shift_id' => $shift->id, 'worker_id' => $request->user()->id],
            ['expressed_at' => now(), 'status' => 'pending']
        );

        if ($interest->status === 'withdrawn') {
            $interest->update(['status' => 'pending', 'expressed_at' => now(), 'withdrawn_at' => null]);
        }

        return $this->success(new ShiftInterestResource($interest), 'Interest expressed', 201);
    }

    /**
     * Free withdrawal, any time before a dispatcher converts this interest
     * into an Assignment (see business-model doc, rule 9 — full
     * CancellationRequest flow after assignment is a later pass).
     */
    public function destroy(Request $request, Shift $shift)
    {
        $interest = ShiftInterest::where('shift_id', $shift->id)
            ->where('worker_id', $request->user()->id)
            ->where('status', 'pending')
            ->first();

        if (! $interest) {
            return $this->error('No active interest found to withdraw.', 404);
        }

        $interest->update(['status' => 'withdrawn', 'withdrawn_at' => now()]);

        return $this->success(null, 'Interest withdrawn');
    }
}
