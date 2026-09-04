<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyWorkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_number' => ['nullable', 'string', 'max:255'],
            'home_branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'works_night_shifts' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'in:invited,pending,active,inactive,blocked'],
            'joined_at' => ['nullable', 'date'],
            'left_at' => ['nullable', 'date'],
        ];
    }
}
