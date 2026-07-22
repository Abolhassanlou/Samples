<?php

namespace Database\Factories;

use App\Models\CompanyLocation;
use App\Models\CompanyMembership;
use App\Models\CompanyMembershipLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyMembershipLocation>
 */
class CompanyMembershipLocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_membership_id' => CompanyMembership::factory(),
            'company_location_id' => CompanyLocation::factory(),
        ];
    }
}
