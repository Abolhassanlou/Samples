<?php

namespace App\Http\Requests\Availability;

use App\Models\AvailabilityRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAvailabilityRuleRequest extends FormRequest
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
            'weekday' => [
                'required',
                'integer',
                'between:1,7',
            ],
            'start_time' => [
                'required',
                'date_format:H:i',
            ],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
            ],
            'status' => [
                'required',
                'string',
                Rule::in(AvailabilityRule::STATUSES),
            ],
            'valid_from' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'valid_until' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:valid_from',
            ],
            'timezone' => [
                'sometimes',
                'string',
                'timezone:all',
                'max:64',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
