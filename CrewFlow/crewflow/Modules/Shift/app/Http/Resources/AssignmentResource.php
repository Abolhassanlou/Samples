<?php

namespace Modules\Shift\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Shift\Services\ShiftVisibility;

class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shift_id' => $this->shift_id,
            'shift_position_id' => $this->shift_position_id,
            'role_name' => $this->whenLoaded('position', fn () => $this->position?->role?->name),
            'worker_id' => $this->worker_id,
            'worker_name' => $this->whenLoaded('worker', fn () => $this->worker->name),
            'assigned_by' => $this->assigned_by,
            'assigned_at' => $this->assigned_at,
            'transport_amount' => $this->transport_amount,
            'status' => $this->status,
            'confirmed_at' => $this->confirmed_at,
            // See the identical field on ShiftInterestResource — same
            // reasoning applies here.
            'qualification_warning' => $this->whenLoaded('shift', function () {
                if ($this->shift->qualification_policy !== 'warn') {
                    return false;
                }

                return ! ShiftVisibility::workerQualifies($this->shift, $this->worker_id);
            }),
        ];
    }
}
