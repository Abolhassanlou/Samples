<?php

namespace Tests\Feature\Services;

use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\EmployeeQualification;
use App\Models\EmployeeRegion;
use App\Models\Qualification;
use App\Models\Region;
use App\Services\EmployeeEligibilityMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class EmployeeEligibilityMatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_match_all_requires_mathematics_and_english(): void
    {
        $company = Company::factory()->create();
        $region = $this->createRegion($company);

        $mathematics = $this->createQualification(
            $company,
            'Mathematics',
            'MATHEMATICS'
        );

        $english = $this->createQualification(
            $company,
            'English',
            'ENGLISH'
        );

        $both = $this->createEmployee(
            $company,
            $region,
            [$mathematics, $english]
        );

        $mathOnly = $this->createEmployee(
            $company,
            $region,
            [$mathematics]
        );

        $englishOnly = $this->createEmployee(
            $company,
            $region,
            [$english]
        );

        $result = app(EmployeeEligibilityMatcher::class)
            ->filterEligibleEmployees(
                collect([
                    $both,
                    $mathOnly,
                    $englishOnly,
                ]),
                $region,
                [$mathematics, $english],
                now(),
                EmployeeEligibilityMatcher::MATCH_ALL
            );

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is($both));
    }

    public function test_match_any_accepts_mathematics_or_english(): void
    {
        $company = Company::factory()->create();
        $region = $this->createRegion($company);

        $mathematics = $this->createQualification(
            $company,
            'Mathematics',
            'MATHEMATICS'
        );

        $english = $this->createQualification(
            $company,
            'English',
            'ENGLISH'
        );

        $both = $this->createEmployee(
            $company,
            $region,
            [$mathematics, $english]
        );

        $mathOnly = $this->createEmployee(
            $company,
            $region,
            [$mathematics]
        );

        $englishOnly = $this->createEmployee(
            $company,
            $region,
            [$english]
        );

        $result = app(EmployeeEligibilityMatcher::class)
            ->filterEligibleEmployees(
                collect([
                    $both,
                    $mathOnly,
                    $englishOnly,
                ]),
                $region,
                [$mathematics, $english],
                now(),
                EmployeeEligibilityMatcher::MATCH_ANY
            );

        $this->assertCount(3, $result);
    }

    public function test_expired_qualification_is_not_matched(): void
    {
        $company = Company::factory()->create();
        $region = $this->createRegion($company);

        $mathematics = $this->createQualification(
            $company,
            'Mathematics',
            'MATHEMATICS'
        );

        $employee = $this->createEmployee(
            $company,
            $region,
            []
        );

        EmployeeQualification::factory()
            ->for($employee)
            ->for($mathematics)
            ->expired()
            ->create();

        $result = app(EmployeeEligibilityMatcher::class)
            ->filterEligibleEmployees(
                collect([$employee]),
                $region,
                [$mathematics],
                now(),
                EmployeeEligibilityMatcher::MATCH_ALL
            );

        $this->assertCount(0, $result);
    }

    public function test_employee_outside_region_is_not_matched(): void
    {
        $company = Company::factory()->create();

        $graz = Region::factory()
            ->for($company)
            ->create([
                'name' => 'Graz',
                'code' => 'GRAZ',
            ]);

        $vienna = Region::factory()
            ->for($company)
            ->create([
                'name' => 'Vienna',
                'code' => 'VIENNA',
            ]);

        $mathematics = $this->createQualification(
            $company,
            'Mathematics',
            'MATHEMATICS'
        );

        $employee = $this->createEmployee(
            $company,
            $vienna,
            [$mathematics]
        );

        $result = app(EmployeeEligibilityMatcher::class)
            ->filterEligibleEmployees(
                collect([$employee]),
                $graz,
                [$mathematics],
                now()
            );

        $this->assertCount(0, $result);
    }

    public function test_dispatcher_is_not_returned_as_employee_candidate(): void
    {
        $company = Company::factory()->create();
        $region = $this->createRegion($company);

        $dispatcher = CompanyMembership::factory()
            ->for($company)
            ->withAccessToAllRegions()
            ->create([
                'role' => CompanyMembership::ROLE_DISPATCHER,
            ]);

        $result = app(EmployeeEligibilityMatcher::class)
            ->filterEligibleEmployees(
                collect([$dispatcher]),
                $region,
                [],
                now()
            );

        $this->assertCount(0, $result);
    }

    public function test_invalid_match_mode_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $membership =
            CompanyMembership::factory()->create();

        app(EmployeeEligibilityMatcher::class)
            ->matchesQualifications(
                $membership,
                [],
                now(),
                'invalid_mode'
            );
    }

    private function createRegion(
        Company $company
    ): Region {
        return Region::factory()
            ->for($company)
            ->create([
                'name' => 'Graz',
                'code' => 'GRAZ',
            ]);
    }

    private function createQualification(
        Company $company,
        string $name,
        string $code
    ): Qualification {
        return Qualification::factory()
            ->for($company)
            ->create([
                'name' => $name,
                'code' => $code,
            ]);
    }

    /**
     * @param  array<int, Qualification>  $qualifications
     */
    private function createEmployee(
        Company $company,
        Region $region,
        array $qualifications
    ): CompanyMembership {
        $membership = CompanyMembership::factory()
            ->for($company)
            ->create([
                'role' => CompanyMembership::ROLE_EMPLOYEE,
            ]);

        EmployeeRegion::factory()
            ->for($membership)
            ->for($region)
            ->create();

        foreach ($qualifications as $qualification) {
            EmployeeQualification::factory()
                ->for($membership)
                ->for($qualification)
                ->create();
        }

        return $membership;
    }
}
