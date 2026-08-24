<?php

namespace Modules\Payment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'worker_id' => $this->worker_id,
            'shift_id' => $this->shift_id,
            'hours_worked' => $this->hours_worked,
            'base_amount' => $this->base_amount,
            'transport_amount' => $this->transport_amount,
            'total_amount' => $this->total_amount,
            'work_date' => $this->work_date,
            'created_at' => $this->created_at,
        ];
    }
}
