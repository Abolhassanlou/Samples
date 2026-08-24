<?php

namespace Modules\Shift\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftPositionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shift_id' => $this->shift_id,
            'shift_role_id' => $this->shift_role_id,
            'role_name' => $this->whenLoaded('role', fn () => $this->role?->name),
            'quantity_needed' => $this->quantity_needed,
            'confirmed_count' => $this->confirmedAssignmentsCount(),
            'hourly_rate' => $this->hourly_rate,
        ];
    }
}
