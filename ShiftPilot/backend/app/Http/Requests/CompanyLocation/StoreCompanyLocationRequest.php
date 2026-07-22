<?php

namespace App\Http\Requests\CompanyLocation;

use App\Models\Company;
use App\Models\CompanyLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyLocationRequest extends FormRequest
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
        $companyLocation =
            $this->route('companyLocation');

        $codeRule = Rule::unique(
            'company_locations',
            'code'
        )->where(
            fn ($query) => $query->where(
                'company_id',
                $company->id
            )
        );

        if ($companyLocation instanceof CompanyLocation) {
            $codeRule->ignore($companyLocation);
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
                Rule::in(CompanyLocation::TYPES),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                $codeRule,
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],
            'address_line_1' => [
                'nullable',
                'string',
                'max:255',
            ],
            'address_line_2' => [
                'nullable',
                'string',
                'max:255',
            ],
            'postal_code' => [
                'nullable',
                'string',
                'max:30',
            ],
            'city' => [
                'nullable',
                'string',
                'max:255',
            ],
            'country_code' => [
                'nullable',
                'string',
                'size:2',
            ],
            'timezone' => [
                'sometimes',
                'string',
                'timezone:all',
                'max:64',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('country_code')) {
            $this->merge([
                'country_code' => strtoupper(
                    $this->string('country_code')->toString()
                ),
            ]);
        }
    }

    private function resolveCompany(): Company
    {
        $company = $this->route('company');

        if ($company instanceof Company) {
            return $company;
        }

        $companyLocation =
            $this->route('companyLocation');

        if ($companyLocation instanceof CompanyLocation) {
            return $companyLocation->company;
        }

        abort(404);
    }
}
