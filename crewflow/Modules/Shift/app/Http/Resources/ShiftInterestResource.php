<?php

namespace Modules\Shift\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftInterestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shift_id' => $this->shift_id,
            'worker_id' => $this->worker_id,
            'worker_name' => $this->whenLoaded('worker', fn () => $this->worker->name),
            'status' => $this->status,
            'expressed_at' => $this->expressed_at,
            'withdrawn_at' => $this->withdrawn_at,
        ];
    }
}
