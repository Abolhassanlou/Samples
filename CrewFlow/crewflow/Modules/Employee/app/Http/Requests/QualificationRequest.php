<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QualificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $qualificationId = $this->route('qualification')?->id;

        return [
            'name' => ['required', 'string', 'max:255', 'unique:qualifications,name,'.$qualificationId],
            'description' => ['nullable', 'string'],
        ];
    }
}
