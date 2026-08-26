<?php

namespace Modules\Shift\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransportGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'driver_assignment_id' => $this->driver_assignment_id,
            'driver_name' => $this->whenLoaded('driverAssignment', fn () => $this->driverAssignment?->worker?->name),
            'vehicle_description' => $this->vehicle_description,
            'notes' => $this->notes,
            'passengers' => $this->whenLoaded('passengerAssignments', fn () => $this->passengerAssignments->map(fn ($a) => [
                'assignment_id' => $a->id,
                'worker_name' => $a->worker?->name,
            ])),
            'created_at' => $this->created_at,
        ];
    }
}
