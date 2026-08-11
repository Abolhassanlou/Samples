<?php

namespace Modules\Setting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanySettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'default_recurrence_mode' => $this->default_recurrence_mode,
            'shift_completion_mode' => $this->shift_completion_mode,
            'shift_visibility_mode' => $this->shift_visibility_mode,
            'warning_hour_threshold' => $this->warning_hour_threshold,
            'warning_income_threshold' => $this->warning_income_threshold,
            'gps_checkin_required' => $this->gps_checkin_required,
            'updated_at' => $this->updated_at,
        ];
    }
}
