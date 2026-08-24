<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employment_type' => ['sometimes', 'in:hourly,part_time'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'home_branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ];
    }
}
