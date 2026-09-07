<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomDocumentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCreating = $this->isMethod('POST');

        return [
            'category' => [$isCreating ? 'required' : 'sometimes', 'in:personal,work'],
            'key' => [$isCreating ? 'required' : 'sometimes', 'string', 'max:100', 'alpha_dash', 'unique:custom_document_types,key,'.$this->route('documentType')?->id],
            'label' => [$isCreating ? 'required' : 'sometimes', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
