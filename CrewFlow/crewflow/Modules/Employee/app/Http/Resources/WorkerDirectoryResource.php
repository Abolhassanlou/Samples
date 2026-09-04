<?php

namespace Modules\Employee\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerDirectoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $companyWorker = $this->companyWorker;
        $activeContract = $companyWorker?->contracts->firstWhere('status', 'active');

        return [
            'user_id' => $this->user_id,
            'worker_id' => $this->id,
            'personnel_number' => $this->user->personnel_number,
            'employee_number' => $companyWorker?->employee_number,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            'status' => $this->status,
            'work_authorization_status' => $this->work_authorization_status,
            'home_branch_id' => $companyWorker?->home_branch_id,
            'home_branch_name' => $companyWorker?->homeBranch?->name,
            'works_night_shifts' => (bool) $companyWorker?->works_night_shifts,
            'active_contract' => $activeContract ? [
                'contract_type' => $activeContract->contract_type,
                'work_time_model' => $activeContract->work_time_model,
                'is_marginal' => $activeContract->is_marginal,
                'weekly_hours' => $activeContract->weekly_hours,
                'is_permanent' => $activeContract->isPermanent(),
                'end_date' => $activeContract->end_date,
            ] : null,
            'qualifications' => $this->qualifications->map(fn ($wq) => [
                'id' => $wq->qualification_id,
                'name' => $wq->qualification?->name,
            ]),
            'availability' => $this->availability->map(fn ($a) => [
                'day_of_week' => $a->day_of_week,
                'start_time' => $a->start_time,
                'end_time' => $a->end_time,
            ]),
        ];
    }
}
