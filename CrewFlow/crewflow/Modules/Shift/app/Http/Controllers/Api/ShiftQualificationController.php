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
            'shift_position_id' => $rq->shift_position_id,
            'qualification_id' => $rq->qualification_id,
            'qualification_name' => $rq->qualification->name,
        ]);

        return $this->success($required);
    }

    /**
     * `shift_position_id` is optional — omit it for a shift-wide
     * requirement (only meaningful for a Shift with no positions at
     * all); include it to scope the requirement to just that one role
     * (e.g. only the "Math teacher" position needs a Math qualification,
     * independent of whatever the shift's other positions need).
     */
    public function store(Request $request, Shift $shift)
    {
        $data = $request->validate([
            'qualification_id' => ['required', 'integer', 'exists:qualifications,id'],
            'shift_position_id' => ['nullable', 'integer', 'exists:shift_positions,id'],
        ]);

        if (! empty($data['shift_position_id'])) {
            $belongsToShift = $shift->positions()->where('id', $data['shift_position_id'])->exists();
            abort_unless($belongsToShift, 422, 'That position does not belong to this shift.');
        }

        $requirement = ShiftQualification::firstOrCreate([
            'shift_id' => $shift->id,
            'shift_position_id' => $data['shift_position_id'] ?? null,
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
