<?php

namespace App\Http\Requests\Eligibility;

use App\Models\EmployeeRegion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRegionRequest extends FormRequest
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
        return [
            'status' => [
                'sometimes',
                'string',
                Rule::in(EmployeeRegion::STATUSES),
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
