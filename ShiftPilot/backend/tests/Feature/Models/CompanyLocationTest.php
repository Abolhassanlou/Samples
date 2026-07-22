<?php

namespace Tests\Feature\Models;

use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\CompanyMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_have_multiple_locations(): void
    {
        $company = Company::factory()->create();

        CompanyLocation::factory()
            ->count(3)
            ->for($company)
            ->create();

        $this->assertCount(3, $company->locations);
    }

    public function test_location_can_have_multiple_dispatchers(): void
    {
        $company = Company::factory()->create();

        $location = CompanyLocation::factory()
            ->for($company)
            ->create();

        $firstDispatcher = CompanyMembership::factory()
            ->for($company)
            ->create([
                'role' => CompanyMembership::ROLE_DISPATCHER,
            ]);

        $secondDispatcher = CompanyMembership::factory()
            ->for($company)
            ->create([
                'role' => CompanyMembership::ROLE_DISPATCHER,
            ]);

        $location->memberships()->attach([
            $firstDispatcher->id,
            $secondDispatcher->id,
        ]);

        $this->assertCount(
            2,
            $location->memberships
        );
    }

    public function test_dispatcher_can_access_multiple_locations(): void
    {
        $company = Company::factory()->create();

        $locations = CompanyLocation::factory()
            ->count(2)
            ->for($company)
            ->create();

        $dispatcher = CompanyMembership::factory()
            ->for($company)
            ->create([
                'role' => CompanyMembership::ROLE_DISPATCHER,
            ]);

        $dispatcher->locations()->attach(
            $locations->modelKeys()
        );

        $this->assertCount(2, $dispatcher->locations);

        $this->assertTrue(
            $dispatcher->canAccessLocation($locations[0])
        );

        $this->assertTrue(
            $dispatcher->canAccessLocation($locations[1])
        );
    }

    public function test_dispatcher_cannot_access_unassigned_location(): void
    {
        $company = Company::factory()->create();

        $assignedLocation = CompanyLocation::factory()
            ->for($company)
            ->create();

        $unassignedLocation = CompanyLocation::factory()
            ->for($company)
            ->create();

        $dispatcher = CompanyMembership::factory()
            ->for($company)
            ->create([
                'role' => CompanyMembership::ROLE_DISPATCHER,
            ]);

        $dispatcher->locations()->attach(
            $assignedLocation
        );

        $this->assertTrue(
            $dispatcher->canAccessLocation($assignedLocation)
        );

        $this->assertFalse(
            $dispatcher->canAccessLocation($unassignedLocation)
        );
    }

    public function test_company_admin_can_access_all_company_locations(): void
    {
        $company = Company::factory()->create();

        $location = CompanyLocation::factory()
            ->for($company)
            ->create();

        $admin = CompanyMembership::factory()
            ->for($company)
            ->create([
                'role' => CompanyMembership::ROLE_COMPANY_ADMIN,
            ]);

        $this->assertTrue(
            $admin->canAccessLocation($location)
        );
    }

    public function test_member_with_all_location_access_can_access_any_location(): void
    {
        $company = Company::factory()->create();

        $location = CompanyLocation::factory()
            ->for($company)
            ->create();

        $dispatcher = CompanyMembership::factory()
            ->for($company)
            ->withAccessToAllLocations()
            ->create([
                'role' => CompanyMembership::ROLE_DISPATCHER,
            ]);

        $this->assertTrue(
            $dispatcher->canAccessLocation($location)
        );
    }

    public function test_member_cannot_access_location_from_another_company(): void
    {
        $firstCompany = Company::factory()->create();
        $secondCompany = Company::factory()->create();

        $location = CompanyLocation::factory()
            ->for($secondCompany)
            ->create();

        $admin = CompanyMembership::factory()
            ->for($firstCompany)
            ->withAccessToAllLocations()
            ->create([
                'role' => CompanyMembership::ROLE_COMPANY_ADMIN,
            ]);

        $this->assertFalse(
            $admin->canAccessLocation($location)
        );
    }

    public function test_inactive_location_cannot_be_accessed(): void
    {
        $company = Company::factory()->create();

        $location = CompanyLocation::factory()
            ->for($company)
            ->inactive()
            ->create();

        $admin = CompanyMembership::factory()
            ->for($company)
            ->create([
                'role' => CompanyMembership::ROLE_COMPANY_ADMIN,
            ]);

        $this->assertFalse(
            $admin->canAccessLocation($location)
        );
    }

    public function test_membership_can_have_primary_location(): void
    {
        $company = Company::factory()->create();

        $location = CompanyLocation::factory()
            ->for($company)
            ->create();

        $membership = CompanyMembership::factory()
            ->for($company)
            ->create([
                'primary_company_location_id' => $location->id,
            ]);

        $this->assertTrue(
            $membership->primaryLocation->is($location)
        );
    }

    public function test_company_visibility_policy_is_detected(): void
    {
        $branchOnlyCompany = Company::factory()->create([
            'workforce_visibility_policy' => Company::VISIBILITY_BRANCH_ONLY,
        ]);

        $companyWide = Company::factory()->create([
            'workforce_visibility_policy' => Company::VISIBILITY_COMPANY_WIDE,
        ]);

        $this->assertFalse(
            $branchOnlyCompany->usesCompanyWideWorkforce()
        );

        $this->assertTrue(
            $companyWide->usesCompanyWideWorkforce()
        );
    }
}
