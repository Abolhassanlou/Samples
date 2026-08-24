<?php

namespace Modules\Shift\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Shift\Http\Requests\ShiftRequest;
use Modules\Shift\Http\Resources\ShiftResource;
use Modules\Shift\Models\Shift;

/**
 * Authorization for every mutating action here is handled entirely at
 * the route level (permission:shifts.create in routes/api.php). Viewing
 * the list/detail only requires being an authenticated user of the company.
 *
 * MVP note: this does not yet filter the list by worker qualification or
 * branch visibility (CompanySettings.shift_visibility_mode) — every
 * authenticated user currently sees every shift. That filtering is
 * deferred to when the Employee module (Qualification) exists.
 */
class ShiftController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success(ShiftResource::collection(Shift::with('positions.role')->orderByDesc('starts_at')->get()));
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
        ]);

        return $this->success(new ShiftResource($shift), 'Shift created', 201);
    }

    public function show(Shift $shift)
    {
        return $this->success(new ShiftResource($shift->load('positions.role')));
    }

    public function update(ShiftRequest $request, Shift $shift)
    {
        $shift->update($request->validated());

        return $this->success(new ShiftResource($shift), 'Shift updated');
    }
}
