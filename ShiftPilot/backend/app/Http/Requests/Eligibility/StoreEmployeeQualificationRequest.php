<?php

namespace App\Http\Requests\Eligibility;

use App\Models\EmployeeQualification;
use App\Models\Qualification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeQualificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $qualification =
            $this->route('qualification');

        $expiresAtRules = [
            'nullable',
            'date_format:Y-m-d',
            'after_or_equal:issued_at',
        ];

        if (
            $qualification instanceof Qualification
            && $qualification->requires_expiry_date
        ) {
            $expiresAtRules[0] = 'required';
        }

        return [
            'level' => [
                'nullable',
                'string',
                'max:50',
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in(EmployeeQualification::STATUSES),
            ],
            'issued_at' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'expires_at' => $expiresAtRules,
            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
