<?php

namespace Modules\Employee\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerQualificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'worker_id' => $this->worker_id,
            'qualification_id' => $this->qualification_id,
            'qualification_name' => $this->whenLoaded('qualification', fn () => $this->qualification->name),
            'source' => $this->source,
            'supporting_document_id' => $this->supporting_document_id,
            'granted_by' => $this->granted_by,
            'granted_at' => $this->granted_at,
        ];
    }
}
