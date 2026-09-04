<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmploymentContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCreating = $this->isMethod('POST');

        return [
            'contract_number' => ['nullable', 'string', 'max:255'],
            'contract_type' => [$isCreating ? 'required' : 'sometimes', 'in:employment_contract,free_service_contract,work_contract'],
            'work_time_model' => [$isCreating ? 'required' : 'sometimes', 'in:full_time,part_time,casual'],
            'is_marginal' => ['sometimes', 'boolean'],
            'weekly_hours' => ['nullable', 'numeric', 'min:0'],
            'start_date' => [$isCreating ? 'required' : 'sometimes', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'status' => ['sometimes', 'in:draft,pending_signature,active,expired,terminated,cancelled'],
            'termination_date' => ['nullable', 'date'],
            'termination_reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
