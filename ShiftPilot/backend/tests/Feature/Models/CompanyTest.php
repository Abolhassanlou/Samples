<?php

namespace Tests\Feature\Models;

use App\Models\Company;
use App\Models\CompanyFeature;
use App\Models\CompanyMembership;
use App\Models\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_belong_to_multiple_companies(): void
    {
        $user = User::factory()->create();

        $firstCompany = Company::factory()->create();
        $secondCompany = Company::factory()->create();

        CompanyMembership::factory()
            ->for($firstCompany)
            ->for($user)
            ->create([
                'role' => CompanyMembership::ROLE_DISPATCHER,
            ]);

        CompanyMembership::factory()
            ->for($secondCompany)
            ->for($user)
            ->create([
                'role' => CompanyMembership::ROLE_EMPLOYEE,
            ]);

        $this->assertCount(
            2,
            $user->companyMemberships
        );

        $this->assertCount(
            2,
            $user->companies
        );

        $this->assertDatabaseHas(
            'company_memberships',
            [
                'company_id' => $firstCompany->id,
                'user_id' => $user->id,
                'role' => CompanyMembership::ROLE_DISPATCHER,
            ]
        );

        $this->assertDatabaseHas(
            'company_memberships',
            [
                'company_id' => $secondCompany->id,
                'user_id' => $user->id,
                'role' => CompanyMembership::ROLE_EMPLOYEE,
            ]
        );
    }

    public function test_company_has_users_through_memberships(): void
    {
        $company = Company::factory()->create();

        $admin = User::factory()->create();
        $employee = User::factory()->create();

        CompanyMembership::factory()
            ->for($company)
            ->for($admin)
            ->create([
                'role' => CompanyMembership::ROLE_COMPANY_ADMIN,
            ]);

        CompanyMembership::factory()
            ->for($company)
            ->for($employee)
            ->create([
                'role' => CompanyMembership::ROLE_EMPLOYEE,
            ]);

        $this->assertCount(2, $company->users);

        $this->assertTrue(
            $company->users->contains($admin)
        );

        $this->assertTrue(
            $company->users->contains($employee)
        );
    }

    public function test_default_enabled_feature_is_available(): void
    {
        $company = Company::factory()->create();

        Feature::factory()->create([
            'key' => 'calendar',
            'default_enabled' => true,
            'is_active' => true,
        ]);

        $this->assertTrue(
            $company->hasFeature('calendar')
        );
    }

    public function test_default_disabled_feature_is_not_available(): void
    {
        $company = Company::factory()->create();

        Feature::factory()->create([
            'key' => 'group_transport',
            'default_enabled' => false,
            'is_active' => true,
        ]);

        $this->assertFalse(
            $company->hasFeature('group_transport')
        );
    }

    public function test_feature_can_be_enabled_for_a_company(): void
    {
        $company = Company::factory()->create();

        $feature = Feature::factory()->create([
            'key' => 'group_transport',
            'default_enabled' => false,
            'is_active' => true,
        ]);

        CompanyFeature::factory()
            ->for($company)
            ->for($feature)
            ->create([
                'is_enabled' => true,
                'enabled_at' => now()->subMinute(),
                'expires_at' => null,
            ]);

        $this->assertTrue(
            $company->hasFeature('group_transport')
        );
    }

    public function test_feature_can_be_disabled_for_a_company(): void
    {
        $company = Company::factory()->create();

        $feature = Feature::factory()->create([
            'key' => 'calendar',
            'default_enabled' => true,
            'is_active' => true,
        ]);

        CompanyFeature::factory()
            ->for($company)
            ->for($feature)
            ->create([
                'is_enabled' => false,
            ]);

        $this->assertFalse(
            $company->hasFeature('calendar')
        );
    }

    public function test_expired_feature_is_not_available(): void
    {
        $company = Company::factory()->create();

        $feature = Feature::factory()->create([
            'key' => 'whatsapp_notifications',
            'default_enabled' => false,
            'is_active' => true,
        ]);

        CompanyFeature::factory()
            ->for($company)
            ->for($feature)
            ->create([
                'is_enabled' => true,
                'enabled_at' => now()->subMonth(),
                'expires_at' => now()->subMinute(),
            ]);

        $this->assertFalse(
            $company->hasFeature('whatsapp_notifications')
        );
    }

    public function test_future_feature_is_not_available_yet(): void
    {
        $company = Company::factory()->create();

        $feature = Feature::factory()->create([
            'key' => 'advanced_reporting',
            'default_enabled' => false,
            'is_active' => true,
        ]);

        CompanyFeature::factory()
            ->for($company)
            ->for($feature)
            ->create([
                'is_enabled' => true,
                'enabled_at' => now()->addDay(),
                'expires_at' => null,
            ]);

        $this->assertFalse(
            $company->hasFeature('advanced_reporting')
        );
    }

    public function test_globally_inactive_feature_is_not_available(): void
    {
        $company = Company::factory()->create();

        $feature = Feature::factory()->create([
            'key' => 'realtime_chat',
            'default_enabled' => true,
            'is_active' => false,
        ]);

        CompanyFeature::factory()
            ->for($company)
            ->for($feature)
            ->create([
                'is_enabled' => true,
            ]);

        $this->assertFalse(
            $company->hasFeature('realtime_chat')
        );
    }

    public function test_feature_configuration_is_cast_to_array(): void
    {
        $companyFeature = CompanyFeature::factory()->create([
            'configuration' => [
                'expiry_warning_days' => [
                    90,
                    30,
                    7,
                ],
            ],
        ]);

        $this->assertIsArray(
            $companyFeature->configuration
        );

        $this->assertSame(
            [90, 30, 7],
            $companyFeature
                ->configuration['expiry_warning_days']
        );
    }
}