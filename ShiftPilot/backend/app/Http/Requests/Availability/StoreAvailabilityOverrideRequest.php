<?php

namespace App\Http\Requests\Availability;

use App\Models\AvailabilityOverride;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAvailabilityOverrideRequest extends FormRequest
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
            'date' => [
                'required',
                'date_format:Y-m-d',
            ],
            'start_time' => [
                'nullable',
                'required_with:end_time',
                'date_format:H:i',
            ],
            'end_time' => [
                'nullable',
                'required_with:start_time',
                'date_format:H:i',
                'after:start_time',
            ],
            'status' => [
                'required',
                'string',
                Rule::in(AvailabilityOverride::STATUSES),
            ],
            'timezone' => [
                'sometimes',
                'string',
                'timezone:all',
                'max:64',
            ],
            'note' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }
}
