<?php

namespace Modules\Shift\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShiftRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('shift_role')?->id;

        return [
            'name' => ['required', 'string', 'max:255', 'unique:shift_roles,name,'.$roleId],
            'description' => ['nullable', 'string'],
        ];
    }
}
