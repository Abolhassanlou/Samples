<?php

namespace Modules\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_phone' => ['nullable', 'string', 'max:20'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
