<?php

namespace Database\Factories;

use App\Models\CompanyMembership;
use App\Models\EmployeeRegion;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeRegion>
 */
class EmployeeRegionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_membership_id' => CompanyMembership::factory(),
            'region_id' => Region::factory(),
            'status' => EmployeeRegion::STATUS_APPROVED,
            'approved_by_user_id' => null,
            'approved_at' => now(),
            'is_active' => true,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => EmployeeRegion::STATUS_PENDING,
            'approved_at' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => EmployeeRegion::STATUS_REJECTED,
            'approved_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
