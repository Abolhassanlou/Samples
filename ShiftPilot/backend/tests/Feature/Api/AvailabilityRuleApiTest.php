<?php

namespace Tests\Feature\Api;

use App\Models\AvailabilityRule;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AvailabilityRuleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_create_own_availability_rule(): void
    {
        $user = User::factory()->create();

        $membership = CompanyMembership::factory()
            ->for($user)
            ->create();

        Sanctum::actingAs($user);

        $response = $this->postJson(
            "/api/v1/company-memberships/{$membership->id}/availability-rules",
            $this->validPayload()
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.availability_rule.weekday',
                1
            )
            ->assertJsonPath(
                'data.availability_rule.status',
                AvailabilityRule::STATUS_AVAILABLE
            );

        $this->assertDatabaseHas('availability_rules', [
            'company_membership_id' => $membership->id,
            'weekday' => 1,
            'status' => AvailabilityRule::STATUS_AVAILABLE,
        ]);
    }

    public function test_employee_can_list_own_availability_rules(): void
    {
        $user = User::factory()->create();

        $membership = CompanyMembership::factory()
            ->for($user)
            ->create();

        AvailabilityRule::factory()
            ->count(2)
            ->for($membership)
            ->create();

        Sanctum::actingAs($user);

        $this->getJson(
            "/api/v1/company-memberships/{$membership->id}/availability-rules"
        )
            ->assertOk()
            ->assertJsonCount(
                2,
                'data.availability_rules'
            );
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $user = User::factory()->create();

        $membership = CompanyMembership::factory()
            ->for($user)
            ->create();

        Sanctum::actingAs($user);

        $payload = $this->validPayload();
        $payload['start_time'] = '16:00';
        $payload['end_time'] = '08:00';

        $this->postJson(
            "/api/v1/company-memberships/{$membership->id}/availability-rules",
            $payload
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'end_time',
            ]);
    }

    public function test_user_from_another_company_cannot_view_rule(): void
    {
        $owner = User::factory()->create();

        $membership = CompanyMembership::factory()
            ->for($owner)
            ->create();

        $availabilityRule = AvailabilityRule::factory()
            ->for($membership)
            ->create();

        $otherUser = User::factory()->create();

        CompanyMembership::factory()
            ->for($otherUser)
            ->create();

        Sanctum::actingAs($otherUser);

        $this->getJson(
            "/api/v1/availability-rules/{$availabilityRule->id}"
        )->assertForbidden();
    }

    public function test_dispatcher_can_view_but_cannot_update_rule(): void
    {
        $company = Company::factory()->create();

        $employee = User::factory()->create();

        $employeeMembership = CompanyMembership::factory()
            ->for($company)
            ->for($employee)
            ->create();

        $availabilityRule = AvailabilityRule::factory()
            ->for($employeeMembership)
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
            "/api/v1/availability-rules/{$availabilityRule->id}"
        )->assertOk();

        $this->putJson(
            "/api/v1/availability-rules/{$availabilityRule->id}",
            $this->validPayload()
        )->assertForbidden();
    }

    public function test_company_admin_can_update_rule(): void
    {
        $company = Company::factory()->create();

        $employee = User::factory()->create();

        $employeeMembership = CompanyMembership::factory()
            ->for($company)
            ->for($employee)
            ->create();

        $availabilityRule = AvailabilityRule::factory()
            ->for($employeeMembership)
            ->create();

        $admin = User::factory()->create();

        CompanyMembership::factory()
            ->for($company)
            ->for($admin)
            ->create([
                'role' => CompanyMembership::ROLE_COMPANY_ADMIN,
            ]);

        Sanctum::actingAs($admin);

        $payload = $this->validPayload();
        $payload['status'] = AvailabilityRule::STATUS_PREFERRED;

        $this->putJson(
            "/api/v1/availability-rules/{$availabilityRule->id}",
            $payload
        )
            ->assertOk()
            ->assertJsonPath(
                'data.availability_rule.status',
                AvailabilityRule::STATUS_PREFERRED
            );
    }

    public function test_unauthenticated_user_cannot_access_rules(): void
    {
        $availabilityRule = AvailabilityRule::factory()->create();

        $this->getJson(
            "/api/v1/availability-rules/{$availabilityRule->id}"
        )->assertUnauthorized();
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'weekday' => 1,
            'start_time' => '08:00',
            'end_time' => '16:00',
            'status' => AvailabilityRule::STATUS_AVAILABLE,
            'valid_from' => '2026-07-27',
            'valid_until' => '2026-12-31',
            'timezone' => 'Europe/Vienna',
            'is_active' => true,
        ];
    }
}
