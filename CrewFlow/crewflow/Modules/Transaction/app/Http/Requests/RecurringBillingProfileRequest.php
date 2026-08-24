<?php

namespace Modules\Transaction\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecurringBillingProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'cycle' => ['required', 'in:weekly,monthly'],
            'next_billing_date' => ['required', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
