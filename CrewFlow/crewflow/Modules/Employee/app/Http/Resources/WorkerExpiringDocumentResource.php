<?php

namespace Modules\Employee\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Deliberately separate from WorkerDirectoryResource — that one also
 * eager-loads/exposes qualifications and availability, neither of which
 * this dashboard list needs; reusing it here would mean either an N+1
 * per row or loading data nobody asked for. Just enough to show and act
 * on a worker whose work authorization needs attention.
 */
class WorkerExpiringDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'personnel_number' => $this->user->personnel_number,
            'name' => $this->user->name,
            'work_authorization_status' => $this->work_authorization_status,
            'work_authorization_type' => $this->work_authorization_type,
            'work_authorization_expiry_date' => $this->work_authorization_expiry_date,
            'is_expired' => $this->work_authorization_expiry_date?->isPast() ?? false,
        ];
    }
}
