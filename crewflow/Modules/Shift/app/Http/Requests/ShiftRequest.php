<?php

namespace Modules\Shift\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCreating = $this->isMethod('POST');

        return [
            'branch_id' => [$isCreating ? 'required' : 'sometimes', 'integer', 'exists:branches,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'title' => [$isCreating ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            'location_type' => ['sometimes', 'in:on_site,online'],
            'location_address' => ['nullable', 'string', 'max:255'],
            'location_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'location_lng' => ['nullable', 'numeric', 'between:-180,180'],

            'client_contact_name' => ['nullable', 'string', 'max:255'],
            'client_contact_phone' => ['nullable', 'string', 'max:20'],
            'internal_contact_name' => ['nullable', 'string', 'max:255'],
            'internal_contact_phone' => ['nullable', 'string', 'max:20'],

            'quantity_needed' => ['sometimes', 'integer', 'min:1'],

            'rate_type' => ['sometimes', 'in:hourly,fixed'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'fixed_amount' => ['nullable', 'numeric', 'min:0'],
            'client_billing_rate' => ['nullable', 'numeric', 'min:0'],

            'starts_at' => [$isCreating ? 'required' : 'sometimes', 'date'],
            'ends_at' => [$isCreating ? 'required' : 'sometimes', 'date', 'after:starts_at'],
        ];
    }
}
