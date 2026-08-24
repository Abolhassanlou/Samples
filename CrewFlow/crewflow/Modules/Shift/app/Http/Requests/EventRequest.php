<?php

namespace Modules\Shift\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventRequest extends FormRequest
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
            'location_address' => ['nullable', 'string', 'max:255'],
            'location_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'location_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'starts_at' => [$isCreating ? 'required' : 'sometimes', 'date'],
            'ends_at' => [$isCreating ? 'required' : 'sometimes', 'date', 'after:starts_at'],
        ];
    }
}
