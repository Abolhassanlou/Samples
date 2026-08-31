<?php

namespace Modules\Employee\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerDirectoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'personnel_number' => $this->user->personnel_number,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            'employment_type' => $this->employment_type,
            'home_branch_id' => $this->home_branch_id,
            'home_branch_name' => $this->homeBranch?->name,
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
