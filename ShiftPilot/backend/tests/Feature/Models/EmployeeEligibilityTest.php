<?php

namespace Tests\Feature\Models;

use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\EmployeeQualification;
use App\Models\EmployeeRegion;
use App\Models\Qualification;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_has_regions_and_qualifications(): void
    {
        $company = Company::factory()->create();

        Region::factory()
            ->count(2)
            ->for($company)
            ->create();

        Qualification::factory()
            ->count(3)
            ->for($company)
            ->create();

        $this->assertCount(2, $company->regions);
        $this->assertCount(3, $company->qualifications);
    }

    public function test_approved_region_is_usable_for_employee(): void
    {
        $company = Company::factory()->create();

        $membership = CompanyMembership::factory()
            ->for($company)
            ->create();

        $region = Region::factory()
            ->for($company)
            ->create();

        EmployeeRegion::factory()
            ->for($membership)
            ->for($region)
            ->create();

        $this->assertTrue(
            $membership->canWorkInRegion($region)
        );
    }

    public function test_pending_region_is_not_usable(): void
    {
        $company = Company::factory()->create();

        $membership = CompanyMembership::factory()
            ->for($company)
            ->create();

        $region = Region::factory()
            ->for($company)
            ->create();

        EmployeeRegion::factory()
            ->for($membership)
            ->for($region)
            ->pending()
            ->create();

        $this->assertFalse(
            $membership->canWorkInRegion($region)
        );
    }

    public function test_all_regions_opens_every_active_company_region(): void
    {
        $company = Company::factory()->create();

        $membership = CompanyMembership::factory()
            ->for($company)
            ->withAccessToAllRegions()
            ->create();

        $region = Region::factory()
            ->for($company)
            ->create();

        $this->assertTrue(
            $membership->canWorkInRegion($region)
        );
    }

    public function test_region_from_another_company_is_not_usable(): void
    {
        $membership = CompanyMembership::factory()
            ->create();

        $region = Region::factory()->create();

        $this->assertFalse(
            $membership->canWorkInRegion($region)
        );
    }

    public function test_verified_qualification_is_usable_on_shift_date(): void
    {
        $company = Company::factory()->create();

        $membership = CompanyMembership::factory()
            ->for($company)
            ->create();

        $qualification = Qualification::factory()
            ->for($company)
            ->create();

        EmployeeQualification::factory()
            ->for($membership)
            ->for($qualification)
            ->create([
                'expires_at' => '2027-12-31',
            ]);

        $this->assertTrue(
            $membership->hasUsableQualification(
                $qualification,
                '2027-01-01'
            )
        );
    }

    public function test_expired_qualification_is_not_usable(): void
    {
        $company = Company::factory()->create();

        $membership = CompanyMembership::factory()
            ->for($company)
            ->create();

        $qualification = Qualification::factory()
            ->for($company)
            ->create();

        EmployeeQualification::factory()
            ->for($membership)
            ->for($qualification)
            ->expired()
            ->create();

        $this->assertFalse(
            $membership->hasUsableQualification(
                $qualification,
                now()
            )
        );
    }

    public function test_required_expiry_date_cannot_be_missing(): void
    {
        $company = Company::factory()->create();

        $membership = CompanyMembership::factory()
            ->for($company)
            ->create();

        $qualification = Qualification::factory()
            ->for($company)
            ->certificate()
            ->create();

        EmployeeQualification::factory()
            ->for($membership)
            ->for($qualification)
            ->withoutExpiry()
            ->create();

        $this->assertFalse(
            $membership->hasUsableQualification(
                $qualification,
                now()
            )
        );
    }

    public function test_qualification_from_another_company_is_not_usable(): void
    {
        $membership = CompanyMembership::factory()
            ->create();

        $qualification =
            Qualification::factory()->create();

        $this->assertFalse(
            $membership->hasUsableQualification(
                $qualification,
                now()
            )
        );
    }

    public function test_employee_can_have_mathematics_and_english(): void
    {
        $company = Company::factory()->create();

        $membership = CompanyMembership::factory()
            ->for($company)
            ->create();

        $mathematics = Qualification::factory()
            ->for($company)
            ->subject()
            ->create([
                'name' => 'Mathematics',
                'code' => 'MATHEMATICS',
            ]);

        $english = Qualification::factory()
            ->for($company)
            ->language()
            ->create([
                'name' => 'English',
                'code' => 'ENGLISH',
            ]);

        EmployeeQualification::factory()
            ->for($membership)
            ->for($mathematics)
            ->create([
                'level' => 'advanced',
            ]);

        EmployeeQualification::factory()
            ->for($membership)
            ->for($english)
            ->create([
                'level' => 'B2',
            ]);

        $this->assertCount(
            2,
            $membership->qualifications
        );

        $this->assertTrue(
            $membership->hasUsableQualification(
                $mathematics,
                now()
            )
        );

        $this->assertTrue(
            $membership->hasUsableQualification(
                $english,
                now()
            )
        );
    }
}
