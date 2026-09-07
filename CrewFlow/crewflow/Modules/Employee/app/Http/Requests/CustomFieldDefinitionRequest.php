<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomFieldDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCreating = $this->isMethod('POST');

        return [
            'category' => [$isCreating ? 'required' : 'sometimes', 'in:personal_info,skill'],
            'key' => [$isCreating ? 'required' : 'sometimes', 'string', 'max:100', 'alpha_dash', 'unique:custom_field_definitions,key,'.$this->route('customField')?->id],
            'label' => [$isCreating ? 'required' : 'sometimes', 'string', 'max:255'],
            'field_type' => [$isCreating ? 'required' : 'sometimes', 'in:text,number,boolean,select,date'],
            'options' => ['nullable', 'array'],
            'options.*' => ['string'],
            'is_required' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
