<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyLocation>
 */
class CompanyLocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->city().' Branch',
            'type' => CompanyLocation::TYPE_BRANCH,
            'code' => strtoupper(
                fake()->unique()->bothify('LOC-###')
            ),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => null,
            'postal_code' => fake()->postcode(),
            'city' => fake()->city(),
            'country_code' => 'AT',
            'timezone' => 'Europe/Vienna',
            'is_active' => true,
        ];
    }

    public function office(): static
    {
        return $this->state(fn (): array => [
            'type' => CompanyLocation::TYPE_OFFICE,
        ]);
    }

    public function department(): static
    {
        return $this->state(fn (): array => [
            'type' => CompanyLocation::TYPE_DEPARTMENT,
            'address_line_1' => null,
            'postal_code' => null,
            'city' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
