<?php

namespace Modules\Shift\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'client_id' => $this->client_id,
            'title' => $this->title,
            'description' => $this->description,
            'location_address' => $this->location_address,
            'location_lat' => $this->location_lat,
            'location_lng' => $this->location_lng,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'shifts_count' => $this->whenCounted('shifts'),
            'created_at' => $this->created_at,
        ];
    }
}
