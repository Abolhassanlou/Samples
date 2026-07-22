<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Feature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CompanyFeature>
 */
class CompanyFeatureFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'feature_id' => Feature::factory(),
            'is_enabled' => true,
            'configuration' => null,
            'enabled_at' => now(),
            'expires_at' => null,
            'enabled_by_user_id' => null,
        ];
    }
}