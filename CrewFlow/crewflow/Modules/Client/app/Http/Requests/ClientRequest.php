<?php

namespace Modules\Client\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['sometimes', 'in:company,individual'],
            'default_contact_name' => ['nullable', 'string', 'max:255'],
            'default_contact_phone' => ['nullable', 'string', 'max:20'],
            'default_address' => ['nullable', 'string', 'max:500'],
        ];
    }
}
