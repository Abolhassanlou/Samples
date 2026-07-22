<?php

namespace Database\Factories;

use App\Models\CompanyMembership;
use App\Models\EmployeeQualification;
use App\Models\Qualification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeQualification>
 */
class EmployeeQualificationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_membership_id' => CompanyMembership::factory(),
            'qualification_id' => Qualification::factory(),
            'level' => 'intermediate',
            'status' => EmployeeQualification::STATUS_VERIFIED,
            'issued_at' => now()->subYear()->toDateString(),
            'expires_at' => now()->addYear()->toDateString(),
            'verified_by_user_id' => null,
            'verified_at' => now(),
            'notes' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => EmployeeQualification::STATUS_PENDING,
            'verified_at' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => EmployeeQualification::STATUS_REJECTED,
            'verified_at' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'status' => EmployeeQualification::STATUS_EXPIRED,
            'expires_at' => now()->subDay()->toDateString(),
        ]);
    }

    public function withoutExpiry(): static
    {
        return $this->state(fn (): array => [
            'expires_at' => null,
        ]);
    }
}
