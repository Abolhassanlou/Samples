<?php

namespace Modules\Employee\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'worker_id' => $this->worker_id,
            'document_type' => $this->document_type,
            'file_path' => $this->file_path,
            'visa_type' => $this->visa_type,
            'visa_expiry_date' => $this->visa_expiry_date,
            'review_status' => $this->review_status,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at,
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at,
        ];
    }
}
