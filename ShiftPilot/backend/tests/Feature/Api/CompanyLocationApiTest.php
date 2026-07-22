<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\CompanyMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyLocationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_create_location(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->create();

        CompanyMembership::factory()
            ->for($company)
            ->for($admin)
            ->create([
                'role' => CompanyMembership::ROLE_COMPANY_ADMIN,
            ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson(
            "/api/v1/companies/{$company->id}/locations",
            $this->validPayload()
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.company_location.name',
                'Graz Branch'
            )
            ->assertJsonPath(
                'data.company_location.country_code',
                'AT'
            );

        $this->assertDatabaseHas('company_locations', [
            'company_id' => $company->id,
            'name' => 'Graz Branch',
            'code' => 'GRZ-01',
            'country_code' => 'AT',
        ]);
    }

    public function test_dispatcher_cannot_create_location(): void
    {
        $company = Company::factory()->create();
        $dispatcher = User::factory()->create();

        CompanyMembership::factory()
            ->for($company)
            ->for($dispatcher)
            ->create([
                'role' => CompanyMembership::ROLE_DISPATCHER,
            ]);

        Sanctum::actingAs($dispatcher);

        $this->postJson(
            "/api/v1/companies/{$company->id}/locations",
            $this->validPayload()
        )->assertForbidden();
    }

    public function test_dispatcher_only_lists_assigned_locations(): void
    {
        $company = Company::factory()->create();

        $assignedLocation = CompanyLocation::factory()
            ->for($company)
            ->create();

        CompanyLocation::factory()
            ->for($company)
            ->create();

        $dispatcher = User::factory()->create();

        $membership = CompanyMembership::factory()
            ->for($company)
            ->for($dispatcher)
            ->create([
                'role' => CompanyMembership::ROLE_DISPATCHER,
            ]);

        $membership->locations()->attach(
            $assignedLocation
        );

        Sanctum::actingAs($dispatcher);

        $this->getJson(
            "/api/v1/companies/{$company->id}/locations"
        )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.company_locations'
            )
            ->assertJsonPath(
                'data.company_locations.0.id',
                $assignedLocation->id
            );
    }

    public function test_dispatcher_with_all_access_lists_all_locations(): void
    {
        $company = Company::factory()->create();

        CompanyLocation::factory()
            ->count(2)
            ->for($company)
            ->create();

        $dispatcher = User::factory()->create();

        CompanyMembership::factory()
            ->for($company)
            ->for($dispatcher)
            ->withAccessToAllLocations()
            ->create([
                'role' => CompanyMembership::ROLE_DISPATCHER,
            ]);

        Sanctum::actingAs($dispatcher);

        $this->getJson(
            "/api/v1/companies/{$company->id}/locations"
        )
            ->assertOk()
            ->assertJsonCount(
                2,
                'data.company_locations'
            );
    }

    public function test_company_admin_can_update_and_delete_location(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->create();

        CompanyMembership::factory()
            ->for($company)
            ->for($admin)
            ->create([
                'role' => CompanyMembership::ROLE_COMPANY_ADMIN,
            ]);

        $location = CompanyLocation::factory()
            ->for($company)
            ->create();

        Sanctum::actingAs($admin);

        $payload = $this->validPayload();
        $payload['name'] = 'Updated Graz Branch';
        $payload['is_active'] = false;

        $this->putJson(
            "/api/v1/company-locations/{$location->id}",
            $payload
        )
            ->assertOk()
            ->assertJsonPath(
                'data.company_location.name',
                'Updated Graz Branch'
            )
            ->assertJsonPath(
                'data.company_location.is_active',
                false
            );

        $this->deleteJson(
            "/api/v1/company-locations/{$location->id}"
        )->assertNoContent();

        $this->assertDatabaseMissing(
            'company_locations',
            ['id' => $location->id]
        );
    }

    public function test_location_code_must_be_unique_inside_company(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->create();

        CompanyMembership::factory()
            ->for($company)
            ->for($admin)
            ->create([
                'role' => CompanyMembership::ROLE_COMPANY_ADMIN,
            ]);

        CompanyLocation::factory()
            ->for($company)
            ->create([
                'code' => 'GRZ-01',
            ]);

        Sanctum::actingAs($admin);

        $this->postJson(
            "/api/v1/companies/{$company->id}/locations",
            $this->validPayload()
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'code',
            ]);
    }

    public function test_admin_can_assign_multiple_dispatchers_to_location(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->create();

        CompanyMembership::factory()
            ->for($company)
            ->for($admin)
            ->create([
                'role' => CompanyMembership::ROLE_COMPANY_ADMIN,
            ]);

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

        Sanctum::actingAs($admin);

        $this->postJson(
            "/api/v1/company-locations/{$location->id}/memberships/{$firstDispatcher->id}"
        )->assertOk();

        $this->postJson(
            "/api/v1/company-locations/{$location->id}/memberships/{$secondDispatcher->id}"
        )->assertOk();

        $this->assertDatabaseCount(
            'company_membership_locations',
            2
        );
    }

    public function test_member_from_another_company_cannot_be_assigned(): void
    {
        $firstCompany = Company::factory()->create();
        $secondCompany = Company::factory()->create();

        $admin = User::factory()->create();

        CompanyMembership::factory()
            ->for($firstCompany)
            ->for($admin)
            ->create([
                'role' => CompanyMembership::ROLE_COMPANY_ADMIN,
            ]);

        $location = CompanyLocation::factory()
            ->for($firstCompany)
            ->create();

        $otherMembership = CompanyMembership::factory()
            ->for($secondCompany)
            ->create();

        Sanctum::actingAs($admin);

        $this->postJson(
            "/api/v1/company-locations/{$location->id}/memberships/{$otherMembership->id}"
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'company_membership_id',
            ]);
    }

    public function test_dispatcher_cannot_assign_members_to_location(): void
    {
        $company = Company::factory()->create();
        $dispatcher = User::factory()->create();

        CompanyMembership::factory()
            ->for($company)
            ->for($dispatcher)
            ->create([
                'role' => CompanyMembership::ROLE_DISPATCHER,
            ]);

        $location = CompanyLocation::factory()
            ->for($company)
            ->create();

        $employeeMembership =
            CompanyMembership::factory()
                ->for($company)
                ->create();

        Sanctum::actingAs($dispatcher);

        $this->postJson(
            "/api/v1/company-locations/{$location->id}/memberships/{$employeeMembership->id}"
        )->assertForbidden();
    }

    public function test_user_from_another_company_cannot_view_location(): void
    {
        $company = Company::factory()->create();

        $location = CompanyLocation::factory()
            ->for($company)
            ->create();

        $otherUser = User::factory()->create();

        CompanyMembership::factory()
            ->for($otherUser)
            ->create();

        Sanctum::actingAs($otherUser);

        $this->getJson(
            "/api/v1/company-locations/{$location->id}"
        )->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_locations(): void
    {
        $company = Company::factory()->create();

        $this->getJson(
            "/api/v1/companies/{$company->id}/locations"
        )->assertUnauthorized();
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Graz Branch',
            'type' => CompanyLocation::TYPE_BRANCH,
            'code' => 'GRZ-01',
            'email' => 'graz@example.com',
            'phone' => '+43 316 123456',
            'address_line_1' => 'Main Street 1',
            'address_line_2' => null,
            'postal_code' => '8010',
            'city' => 'Graz',
            'country_code' => 'at',
            'timezone' => 'Europe/Vienna',
            'is_active' => true,
        ];
    }
}
