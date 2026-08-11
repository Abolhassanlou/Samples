<?php

namespace Modules\Setting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'default_recurrence_mode' => ['sometimes', 'in:auto_continue,reconfirm_each_time'],
            'shift_completion_mode' => ['sometimes', 'in:document_signature,button_confirm'],
            'shift_visibility_mode' => ['sometimes', 'in:hide_unqualified,show_disabled'],
            'warning_hour_threshold' => ['nullable', 'integer', 'min:0'],
            'warning_income_threshold' => ['nullable', 'numeric', 'min:0'],
            'gps_checkin_required' => ['sometimes', 'boolean'],
        ];
    }
}
