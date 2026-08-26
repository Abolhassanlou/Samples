<?php

namespace Modules\Shift\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CancellationRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'worker_id' => $this->worker_id,
            'worker_name' => $this->whenLoaded('worker', fn () => $this->worker->name),
            'reason' => $this->reason,
            'is_urgent' => $this->is_urgent,
            'status' => $this->status,
            'processed_by' => $this->processed_by,
            'processed_at' => $this->processed_at,
            'created_at' => $this->created_at,
        ];
    }
}
