<?php

namespace App\Http\Requests\Region;

use App\Models\Company;
use App\Models\Region;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegionRequest extends FormRequest
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
        $region = $this->route('region');

        $codeRule = Rule::unique(
            'regions',
            'code'
        )->where(
            fn ($query) => $query->where(
                'company_id',
                $company->id
            )
        );

        if ($region instanceof Region) {
            $codeRule->ignore($region);
        }

        $parentRules = [
            'nullable',
            'integer',
            Rule::exists('regions', 'id')->where(
                fn ($query) => $query->where(
                    'company_id',
                    $company->id
                )
            ),
        ];

        if ($region instanceof Region) {
            $parentRules[] = Rule::notIn([
                $region->id,
            ]);
        }

        return [
            'parent_id' => $parentRules,
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'type' => [
                'required',
                'string',
                Rule::in(Region::TYPES),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                $codeRule,
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

        $region = $this->route('region');

        if ($region instanceof Region) {
            return $region->company;
        }

        abort(404);
    }
}
