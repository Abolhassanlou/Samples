<?php

namespace Modules\Employee\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyWorkerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'worker_id' => $this->worker_id,
            'employee_number' => $this->employee_number,
            'home_branch_id' => $this->home_branch_id,
            'home_branch_name' => $this->whenLoaded('homeBranch', fn () => $this->homeBranch?->name),
            'works_night_shifts' => $this->works_night_shifts,
            'status' => $this->status,
            'joined_at' => $this->joined_at,
            'left_at' => $this->left_at,
            'contracts' => EmploymentContractResource::collection($this->whenLoaded('contracts')),
            'created_at' => $this->created_at,
        ];
    }
}
