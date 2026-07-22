<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name)
                .'-'
                .fake()->unique()->numberBetween(1000, 9999),

            'company_code' => strtoupper(
                fake()->unique()->bothify('CMP-####??')
            ),

            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'timezone' => 'Europe/Vienna',
            'locale' => 'de',
            'is_active' => true,
            'workforce_visibility_policy' => Company::VISIBILITY_BRANCH_ONLY,
        ];
    }
}
