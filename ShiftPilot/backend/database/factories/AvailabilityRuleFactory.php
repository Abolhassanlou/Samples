<?php

namespace Database\Factories;

use App\Models\AvailabilityRule;
use App\Models\CompanyMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AvailabilityRule>
 */
class AvailabilityRuleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_membership_id' => CompanyMembership::factory(),
            'weekday' => fake()->numberBetween(1, 7),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'status' => AvailabilityRule::STATUS_AVAILABLE,
            'valid_from' => now()->startOfWeek()->toDateString(),
            'valid_until' => now()->addMonths(3)->toDateString(),
            'timezone' => 'Europe/Vienna',
            'is_active' => true,
        ];
    }

    public function preferred(): static
    {
        return $this->state(fn (): array => [
            'status' => AvailabilityRule::STATUS_PREFERRED,
        ]);
    }

    public function unavailable(): static
    {
        return $this->state(fn (): array => [
            'status' => AvailabilityRule::STATUS_UNAVAILABLE,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }

    public function withoutEndDate(): static
    {
        return $this->state(fn (): array => [
            'valid_until' => null,
        ]);
    }
}