<?php

namespace Modules\Shift\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShiftPositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shift_role_id' => ['nullable', 'integer', 'exists:shift_roles,id'],
            'quantity_needed' => ['required', 'integer', 'min:1'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
