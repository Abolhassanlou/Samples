<?php

namespace Modules\Shift\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Shift\Services\ShiftVisibility;

class ShiftInterestResource extends JsonResource
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
            'status' => $this->status,
            'expressed_at' => $this->expressed_at,
            'withdrawn_at' => $this->withdrawn_at,
            // Only meaningful (and only computed) when the shift itself
            // is eager-loaded and its policy is "warn" — see
            // ShiftVisibility::workerQualifies(). Never true for
            // strict/override shifts, since strict already hides this
            // interest from existing in the first place, and override
            // never warns by design.
            'qualification_warning' => $this->whenLoaded('shift', function () {
                if ($this->shift->qualification_policy !== 'warn') {
                    return false;
                }

                return ! ShiftVisibility::workerQualifies($this->shift, $this->worker_id);
            }),
        ];
    }
}
