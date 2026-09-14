<?php

namespace Modules\Employee\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmploymentContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_worker_id' => $this->company_worker_id,
            'event_id' => $this->event_id,
            'contract_number' => $this->contract_number,
            'contract_type' => $this->contract_type,
            'work_time_model' => $this->work_time_model,
            'is_marginal' => $this->is_marginal,
            'weekly_hours' => $this->weekly_hours,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            // Derived, not stored — see EmploymentContract::isPermanent().
            'is_permanent' => $this->isPermanent(),
            'status' => $this->status,
            'has_file' => $this->hasFile(),
            // Exposed so the frontend can pull the real file extension
            // (e.g. "employment-contracts/xJ3ha82.jpg") when naming a
            // downloaded copy — without this, a download's filename has
            // no extension at all and the OS can't tell what kind of
            // file it actually is, even though the content is fine.
            'file_path' => $this->file_path,
            'signed_at' => $this->signed_at,
            'is_signed' => $this->isSigned(),
            'termination_date' => $this->termination_date,
            'termination_reason' => $this->termination_reason,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
