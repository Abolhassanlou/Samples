<?php

namespace Modules\Shift\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Shift\Models\Shift;
use Modules\Shift\Models\ShiftQualification;

/**
 * Authorization handled at the route level (permission:shifts.create —
 * defining what a shift requires is a shift-authoring concern, same
 * permission as creating the shift itself).
 */
class ShiftQualificationController extends Controller
{
    use ApiResponse;

    public function index(Shift $shift)
    {
        $required = $shift->requiredQualifications()->with('qualification')->get()->map(fn ($rq) => [
            'id' => $rq->id,
            'qualification_id' => $rq->qualification_id,
            'qualification_name' => $rq->qualification->name,
        ]);

        return $this->success($required);
    }

    public function store(Request $request, Shift $shift)
    {
        $data = $request->validate([
            'qualification_id' => ['required', 'integer', 'exists:qualifications,id'],
        ]);

        $requirement = ShiftQualification::firstOrCreate([
            'shift_id' => $shift->id,
            'qualification_id' => $data['qualification_id'],
        ]);

        return $this->success($requirement, 'Qualification requirement added', 201);
    }

    public function destroy(Shift $shift, ShiftQualification $qualification)
    {
        abort_unless($qualification->shift_id === $shift->id, 404);

        $qualification->delete();

        return $this->success(null, 'Qualification requirement removed');
    }
}
