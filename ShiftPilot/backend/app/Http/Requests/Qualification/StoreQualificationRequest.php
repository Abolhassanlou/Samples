<?php

namespace App\Http\Requests\Qualification;

use App\Models\Company;
use App\Models\Qualification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQualificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $company = $this->resolveCompany();
        $qualification =
            $this->route('qualification');

        $codeRule = Rule::unique(
            'qualifications',
            'code'
        )->where(
            fn ($query) => $query->where(
                'company_id',
                $company->id
            )
        );

        if ($qualification instanceof Qualification) {
            $codeRule->ignore($qualification);
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'type' => [
                'required',
                'string',
                Rule::in(Qualification::TYPES),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                $codeRule,
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'requires_verification' => [
                'sometimes',
                'boolean',
            ],
            'requires_expiry_date' => [
                'sometimes',
                'boolean',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    private function resolveCompany(): Company
    {
        $company = $this->route('company');

        if ($company instanceof Company) {
            return $company;
        }

        $qualification =
            $this->route('qualification');

        if ($qualification instanceof Qualification) {
            return $qualification->company;
        }

        abort(404);
    }
}
