<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'in:pending,active,inactive,blocked'],
            'work_authorization_status' => ['sometimes', 'in:pending,valid,expired,not_required,rejected'],
            'work_authorization_type' => ['nullable', 'string', 'max:255'],
            'work_authorization_expiry_date' => ['nullable', 'date'],
        ];
    }
}
