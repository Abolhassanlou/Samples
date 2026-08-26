<?php

namespace Modules\Shift\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransportGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'driver_assignment_id' => ['required', 'integer', 'exists:assignments,id'],
            'vehicle_description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'passenger_assignment_ids' => ['sometimes', 'array'],
            'passenger_assignment_ids.*' => ['integer', 'exists:assignments,id'],
        ];
    }
}
