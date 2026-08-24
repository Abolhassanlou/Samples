<?php

namespace Modules\Shift\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Shift\Http\Requests\ShiftPositionRequest;
use Modules\Shift\Http\Resources\ShiftPositionResource;
use Modules\Shift\Models\Shift;
use Modules\Shift\Models\ShiftPosition;

/**
 * Authorization for mutating actions handled at the route level
 * (permission:shifts.create). Once a Shift has at least one position,
 * Shift::isFull() switches from the plain quantity_needed count to
 * "every position full" — see that model's docblock.
 */
class ShiftPositionController extends Controller
{
    use ApiResponse;

    public function index(Shift $shift)
    {
        return $this->success(ShiftPositionResource::collection($shift->positions()->with('role')->get()));
    }

    public function store(ShiftPositionRequest $request, Shift $shift)
    {
        $position = $shift->positions()->create($request->validated());

        return $this->success(new ShiftPositionResource($position->load('role')), 'Position added', 201);
    }

    public function update(ShiftPositionRequest $request, Shift $shift, ShiftPosition $position)
    {
        abort_unless($position->shift_id === $shift->id, 404);

        $position->update($request->validated());

        return $this->success(new ShiftPositionResource($position->load('role')), 'Position updated');
    }

    public function destroy(Shift $shift, ShiftPosition $position)
    {
        abort_unless($position->shift_id === $shift->id, 404);

        $position->delete();

        return $this->success(null, 'Position removed');
    }
}
