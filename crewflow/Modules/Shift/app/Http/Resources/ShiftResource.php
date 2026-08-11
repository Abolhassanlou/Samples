<?php

namespace Modules\Shift\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'client_id' => $this->client_id,
            'title' => $this->title,
            'description' => $this->description,
            'location_type' => $this->location_type,
            'location_address' => $this->location_address,
            'location_lat' => $this->location_lat,
            'location_lng' => $this->location_lng,
            'client_contact_name' => $this->client_contact_name,
            'client_contact_phone' => $this->client_contact_phone,
            'internal_contact_name' => $this->internal_contact_name,
            'internal_contact_phone' => $this->internal_contact_phone,
            'quantity_needed' => $this->quantity_needed,
            'confirmed_count' => $this->confirmedAssignmentsCount(),
            'rate_type' => $this->rate_type,
            'hourly_rate' => $this->hourly_rate,
            'fixed_amount' => $this->fixed_amount,
            // client_billing_rate is deliberately hidden from anyone without
            // shifts.dispatch (i.e. hidden from Workers, visible to
            // Dispatcher/Company Admin) — see the business-model doc, rule 6:
            // worker rate and client rate must never be visible to the same audience.
            'client_billing_rate' => $request->user()?->can('shifts.dispatch')
                ? $this->client_billing_rate
                : null,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
