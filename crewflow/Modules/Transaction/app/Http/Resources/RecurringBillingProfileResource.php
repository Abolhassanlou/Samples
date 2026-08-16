<?php

namespace Modules\Transaction\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecurringBillingProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'amount' => $this->amount,
            'cycle' => $this->cycle,
            'next_billing_date' => $this->next_billing_date,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}
