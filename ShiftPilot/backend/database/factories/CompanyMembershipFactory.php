<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyMembership>
 */
class CompanyMembershipFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'role' => CompanyMembership::ROLE_EMPLOYEE,
            'status' => CompanyMembership::STATUS_ACTIVE,
            'joined_at' => now(),
            'primary_company_location_id' => null,
            'access_all_locations' => false,
            'all_regions' => false,
        ];
    }

    public function withAccessToAllLocations(): static
    {
        return $this->state(fn (): array => [
            'access_all_locations' => true,
        ]);
    }

    public function withAccessToAllRegions(): static
    {
        return $this->state(fn (): array => [
            'all_regions' => true,
        ]);
    }
}
