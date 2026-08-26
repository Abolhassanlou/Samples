<?php

namespace Modules\Shift\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Shift\Http\Requests\ShiftRequest;
use Modules\Shift\Http\Resources\ShiftResource;
use Modules\Shift\Models\Shift;
use Modules\Shift\Services\ShiftVisibility;

/**
 * Authorization for every mutating action here is handled entirely at
 * the route level (permission:shifts.create in routes/api.php).
 *
 * Viewing: a Dispatcher/Admin (shifts.dispatch) always sees every shift,
 * unfiltered — they need full visibility to manage. A plain Worker only
 * sees shifts they have both ACCESS to (their own branch, or an Event
 * they've been explicitly activated for) AND every required qualification
 * for (see ShiftVisibility for the full rule). Hidden, not just disabled.
 */
class ShiftController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Shift::with('positions.role')->orderByDesc('starts_at');

        if (! $request->user()->can('shifts.dispatch')) {
            $query = ShiftVisibility::scopeFor($query, $request->user());
        }

        return $this->success(ShiftResource::collection($query->get()));
    }

    public function store(ShiftRequest $request)
    {
        $shift = Shift::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'status' => 'open',
            'location_type' => $request->validated('location_type', 'on_site'),
            'quantity_needed' => $request->validated('quantity_needed', 1),
            'rate_type' => $request->validated('rate_type', 'hourly'),
            'qualification_override' => $request->validated('qualification_override', false),
        ]);

        return $this->success(new ShiftResource($shift), 'Shift created', 201);
    }

    /**
     * A worker hitting this directly for a shift outside their visibility
     * gets a 404, not a 403 — consistent with "hidden", not "disabled".
     */
    public function show(Request $request, Shift $shift)
    {
        if (! $request->user()->can('shifts.dispatch')) {
            $visible = ShiftVisibility::scopeFor(Shift::where('id', $shift->id), $request->user())->exists();
            abort_unless($visible, 404);
        }

        return $this->success(new ShiftResource($shift->load('positions.role')));
    }

    public function update(ShiftRequest $request, Shift $shift)
    {
        $shift->update($request->validated());

        return $this->success(new ShiftResource($shift), 'Shift updated');
    }
}
