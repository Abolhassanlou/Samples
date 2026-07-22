<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\EmployeeRegion;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RegionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_create_region(): void
    {
        $company = Company::factory()->create();
        $admin = $this->createAdmin($company);

        Sanctum::actingAs($admin);

        $this->postJson(
            "/api/v1/companies/{$company->id}/regions",
            $this->validPayload()
        )
            ->assertCreated()
            ->assertJsonPath(
                'data.region.name',
                'Graz'
            )
            ->assertJsonPath(
                'data.region.country_code',
                'AT'
            );

        $this->assertDatabaseHas('regions', [
            'company_id' => $company->id,
            'name' => 'Graz',
            'code' => 'GRAZ',
        ]);
    }

    public function test_dispatcher_can_list_company_regions(): void
    {
        $company = Company::factory()->create();

        Region::factory()
            ->count(2)
            ->for($company)
            ->create();

        $dispatcher = User::factory()->create();

        CompanyMembership::factory()
            ->for($company)
            ->for($dispatcher)
            ->create([
                'role' => CompanyMembership::ROLE_DISPATCHER,
            ]);

        Sanctum::actingAs($dispatcher);

        $this->getJson(
            "/api/v1/companies/{$company->id}/regions"
        )
            ->assertOk()
            ->assertJsonCount(2, 'data.regions');
    }

    public function test_employee_cannot_create_region(): void
    {
        $company = Company::factory()->create();
        $employee = User::factory()->create();

        CompanyMembership::factory()
            ->for($company)
            ->for($employee)
            ->create();

        Sanctum::actingAs($employee);

        $this->postJson(
            "/api/v1/companies/{$company->id}/regions",
            $this->validPayload()
        )->assertForbidden();
    }

    public function test_user_from_another_company_cannot_view_region(): void
    {
        $company = Company::factory()->create();

        $region = Region::factory()
            ->for($company)
            ->create();

        $otherUser = User::factory()->create();

        CompanyMembership::factory()
            ->for($otherUser)
            ->create();

        Sanctum::actingAs($otherUser);

        $this->getJson(
            "/api/v1/regions/{$region->id}"
        )->assertForbidden();
    }

    public function test_parent_region_must_belong_to_same_company(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();

        $otherRegion = Region::factory()
            ->for($otherCompany)
            ->create();

        $admin = $this->createAdmin($company);

        Sanctum::actingAs($admin);

        $payload = $this->validPayload();
        $payload['parent_id'] = $otherRegion->id;

        $this->postJson(
            "/api/v1/companies/{$company->id}/regions",
            $payload
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'parent_id',
            ]);
    }

    public function test_region_code_is_unique_inside_company(): void
    {
        $company = Company::factory()->create();

        Region::factory()
            ->for($company)
            ->create([
                'code' => 'GRAZ',
            ]);

        $admin = $this->createAdmin($company);

        Sanctum::actingAs($admin);

        $this->postJson(
            "/api/v1/companies/{$company->id}/regions",
            $this->validPayload()
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'code',
            ]);
    }

    public function test_admin_can_assign_approved_region_to_employee(): void
    {
        $company = Company::factory()->create();
        $admin = $this->createAdmin($company);

        $membership = CompanyMembership::factory()
            ->for($company)
            ->create();

        $region = Region::factory()
            ->for($company)
            ->create();

        Sanctum::actingAs($admin);

        $this->putJson(
            "/api/v1/company-memberships/{$membership->id}/regions/{$region->id}",
            [
                'status' => EmployeeRegion::STATUS_APPROVED,
                'is_active' => true,
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.employee_region.status',
                EmployeeRegion::STATUS_APPROVED
            );

        $this->assertTrue(
            $membership->canWorkInRegion($region)
        );
    }

    public function test_region_from_another_company_cannot_be_assigned(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();

        $admin = $this->createAdmin($company);

        $membership = CompanyMembership::factory()
            ->for($company)
            ->create();

        $otherRegion = Region::factory()
            ->for($otherCompany)
            ->create();

        Sanctum::actingAs($admin);

        $this->putJson(
            "/api/v1/company-memberships/{$membership->id}/regions/{$otherRegion->id}",
            [
                'status' => EmployeeRegion::STATUS_APPROVED,
            ]
        )
            ->assertForbidden();
    }

    public function test_pending_region_does_not_allow_work(): void
    {
        $company = Company::factory()->create();
        $admin = $this->createAdmin($company);

        $membership = CompanyMembership::factory()
            ->for($company)
            ->create();

        $region = Region::factory()
            ->for($company)
            ->create();

        Sanctum::actingAs($admin);

        $this->putJson(
            "/api/v1/company-memberships/{$membership->id}/regions/{$region->id}",
            [
                'status' => EmployeeRegion::STATUS_PENDING,
            ]
        )->assertOk();

        $this->assertFalse(
            $membership->canWorkInRegion($region)
        );
    }

    public function test_unauthenticated_user_cannot_access_regions(): void
    {
        $company = Company::factory()->create();

        $this->getJson(
            "/api/v1/companies/{$company->id}/regions"
        )->assertUnauthorized();
    }

    private function createAdmin(
        Company $company
    ): User {
        $admin = User::factory()->create();

        CompanyMembership::factory()
            ->for($company)
            ->for($admin)
            ->create([
                'role' => CompanyMembership::ROLE_COMPANY_ADMIN,
            ]);

        return $admin;
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'parent_id' => null,
            'name' => 'Graz',
            'type' => Region::TYPE_CITY,
            'code' => 'GRAZ',
            'country_code' => 'at',
            'timezone' => 'Europe/Vienna',
            'is_active' => true,
        ];
    }
}
