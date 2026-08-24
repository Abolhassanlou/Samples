<?php

namespace Modules\Shift\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Shift\Http\Requests\ShiftRoleRequest;
use Modules\Shift\Http\Resources\ShiftRoleResource;
use Modules\Shift\Models\ShiftRole;

/**
 * A dynamic, company-defined catalog — "Driver", "Coordinator", whatever
 * a specific company needs. Viewing is open; managing requires
 * shifts.create (route-level, see routes/api.php).
 */
class ShiftRoleController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success(ShiftRoleResource::collection(ShiftRole::orderBy('name')->get()));
    }

    public function store(ShiftRoleRequest $request)
    {
        $role = ShiftRole::create($request->validated());

        return $this->success(new ShiftRoleResource($role), 'Shift role created', 201);
    }

    public function update(ShiftRoleRequest $request, ShiftRole $shift_role)
    {
        $shift_role->update($request->validated());

        return $this->success(new ShiftRoleResource($shift_role), 'Shift role updated');
    }

    public function destroy(ShiftRole $shift_role)
    {
        $shift_role->delete();

        return $this->success(null, 'Shift role deleted');
    }
}
