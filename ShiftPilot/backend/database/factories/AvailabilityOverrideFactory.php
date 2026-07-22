<?php

namespace Database\Factories;

use App\Models\AvailabilityOverride;
use App\Models\CompanyMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AvailabilityOverride>
 */
class AvailabilityOverrideFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_membership_id' => CompanyMembership::factory(),
            'date' => now()
                ->addDays(fake()->numberBetween(1, 30))
                ->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'status' => AvailabilityOverride::STATUS_AVAILABLE,
            'timezone' => 'Europe/Vienna',
            'note' => null,
        ];
    }

    public function preferred(): static
    {
        return $this->state(fn (): array => [
            'status' => AvailabilityOverride::STATUS_PREFERRED,
        ]);
    }

    public function unavailable(): static
    {
        return $this->state(fn (): array => [
            'status' => AvailabilityOverride::STATUS_UNAVAILABLE,
        ]);
    }

    public function wholeDayUnavailable(): static
    {
        return $this->state(fn (): array => [
            'start_time' => null,
            'end_time' => null,
            'status' => AvailabilityOverride::STATUS_UNAVAILABLE,
        ]);
    }
}