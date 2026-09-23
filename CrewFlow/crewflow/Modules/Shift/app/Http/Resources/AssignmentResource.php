<?php

namespace Modules\Shift\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Shift\Services\ShiftVisibility;

class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shift_id' => $this->shift_id,
            'shift_position_id' => $this->shift_position_id,
            'role_name' => $this->whenLoaded('position', fn () => $this->position?->role?->name),
            'worker_id' => $this->worker_id,
            'worker_name' => $this->whenLoaded('worker', fn () => $this->worker->name),
            'assigned_by' => $this->assigned_by,
            'assigned_at' => $this->assigned_at,
            'transport_amount' => $this->transport_amount,
            'status' => $this->status,
            'confirmed_at' => $this->confirmed_at,
            // Set only when a shift edit forced this assignment back to
            // pending_worker_confirmation after it had already been
            // confirmed — a plain-language summary of exactly what
            // changed (see ShiftController::update()). Cleared once the
            // worker re-confirms.
            'change_note' => $this->change_note,
            // Full shift details (title, dates, location, rate) — used
            // by the worker-facing Jobs tab so "my assignments" can be
            // rendered without a second request per row.
            'shift' => $this->whenLoaded('shift', fn () => new ShiftResource($this->shift)),
            // See the identical field on ShiftInterestResource — same
            // reasoning applies here.
            'qualification_warning' => $this->whenLoaded('shift', function () {
                if ($this->shift->qualification_policy !== 'warn') {
                    return false;
                }

                return ! ShiftVisibility::workerQualifies($this->shift, $this->worker_id);
            }),
        ];
    }
}
