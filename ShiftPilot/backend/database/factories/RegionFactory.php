<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Region>
 */
class RegionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'parent_id' => null,
            'name' => fake()->city(),
            'type' => Region::TYPE_CITY,
            'code' => strtoupper(
                fake()->unique()->bothify('REG-###')
            ),
            'country_code' => 'AT',
            'timezone' => 'Europe/Vienna',
            'is_active' => true,
        ];
    }

    public function custom(): static
    {
        return $this->state(fn (): array => [
            'type' => Region::TYPE_CUSTOM,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
