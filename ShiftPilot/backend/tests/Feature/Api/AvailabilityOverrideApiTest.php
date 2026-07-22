<?php

namespace Tests\Feature\Api;

use App\Models\AvailabilityOverride;
use App\Models\AvailabilityRule;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AvailabilityOverrideApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Feature::factory()->create([
            'key' => Feature::KEY_DATE_SPECIFIC_AVAILABILITY,
            'default_enabled' => true,
            'is_active' => true,
        ]);
    }

    public function test_employee_can_create_whole_day_override(): void
    {
        $user = User::factory()->create();

        $membership = CompanyMembership::factory()
            ->for($user)
            ->create();

        Sanctum::actingAs($user);

        $response = $this->postJson(
            "/api/v1/company-memberships/{$membership->id}/availability-overrides",
            [
                'date' => '2026-08-01',
                'status' => AvailabilityOverride::STATUS_UNAVAILABLE,
                'timezone' => 'Europe/Vienna',
                'note' => 'Personal appointment',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.availability_override.date',
                '2026-08-01T00:00:00.000000Z'
            )
            ->assertJsonPath(
                'data.availability_override.status',
                AvailabilityOverride::STATUS_UNAVAILABLE
            )
            ->assertJsonPath(
                'data.availability_override.start_time',
                null
            )
            ->assertJsonPath(
                'data.availability_override.end_time',
                null
            );

        $this->assertDatabaseHas('availability_overrides', [
            'company_membership_id' => $membership->id,
            'date' => '2026-08-01',
            'status' => AvailabilityOverride::STATUS_UNAVAILABLE,
        ]);
    }

    public function test_employee_can_create_timed_override(): void
    {
        $user = User::factory()->create();

        $membership = CompanyMembership::factory()
            ->for($user)
            ->create();

        Sanctum::actingAs($user);

        $this->postJson(
            "/api/v1/company-memberships/{$membership->id}/availability-overrides",
            $this->validTimedPayload()
        )
            ->assertCreated()
            ->assertJsonPath(
                'data.availability_override.start_time',
                '09:00'
            )
            ->assertJsonPath(
                'data.availability_override.end_time',
                '13:00'
            );
    }

    public function test_start_and_end_time_must_be_provided_together(): void
    {
        $user = User::factory()->create();

        $membership = CompanyMembership::factory()
            ->for($user)
            ->create();

        Sanctum::actingAs($user);

        $this->postJson(
            "/api/v1/company-memberships/{$membership->id}/availability-overrides",
            [
                'date' => '2026-08-01',
                'start_time' => '09:00',
                'status' => AvailabilityOverride::STATUS_AVAILABLE,
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'end_time',
            ]);
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $user = User::factory()->create();

        $membership = CompanyMembership::factory()
            ->for($user)
            ->create();

        Sanctum::actingAs($user);

        $payload = $this->validTimedPayload();
        $payload['start_time'] = '17:00';
        $payload['end_time'] = '09:00';

        $this->postJson(
            "/api/v1/company-memberships/{$membership->id}/availability-overrides",
            $payload
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'end_time',
            ]);
    }

    public function test_user_from_another_company_cannot_view_override(): void
    {
        $owner = User::factory()->create();

        $membership = CompanyMembership::factory()
            ->for($owner)
            ->create();

        $availabilityOverride = AvailabilityOverride::factory()
            ->for($membership)
            ->create();

        $otherUser = User::factory()->create();

        CompanyMembership::factory()
            ->for($otherUser)
            ->create();

        Sanctum::actingAs($otherUser);

        $this->getJson(
            "/api/v1/availability-overrides/{$availabilityOverride->id}"
        )->assertForbidden();
    }

    public function test_dispatcher_can_view_but_cannot_update_override(): void
    {
        $company = Company::factory()->create();

        $employee = User::factory()->create();

        $employeeMembership = CompanyMembership::factory()
            ->for($company)
            ->for($employee)
            ->create();

        $availabilityOverride = AvailabilityOverride::factory()
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
            "/api/v1/availability-overrides/{$availabilityOverride->id}"
        )->assertOk();

        $this->putJson(
            "/api/v1/availability-overrides/{$availabilityOverride->id}",
            $this->validTimedPayload()
        )->assertForbidden();
    }

    public function test_company_admin_can_update_override(): void
    {
        $company = Company::factory()->create();

        $employee = User::factory()->create();

        $employeeMembership = CompanyMembership::factory()
            ->for($company)
            ->for($employee)
            ->create();

        $availabilityOverride = AvailabilityOverride::factory()
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

        $payload = $this->validTimedPayload();
        $payload['status'] =
            AvailabilityOverride::STATUS_PREFERRED;

        $this->putJson(
            "/api/v1/availability-overrides/{$availabilityOverride->id}",
            $payload
        )
            ->assertOk()
            ->assertJsonPath(
                'data.availability_override.status',
                AvailabilityOverride::STATUS_PREFERRED
            );
    }

    public function test_unauthenticated_user_cannot_access_overrides(): void
    {
        $availabilityOverride =
            AvailabilityOverride::factory()->create();

        $this->getJson(
            "/api/v1/availability-overrides/{$availabilityOverride->id}"
        )->assertUnauthorized();
    }

    /**
     * @return array<string, mixed>
     */
    public function test_company_cannot_use_overrides_when_feature_is_disabled(): void
    {
        Feature::query()
            ->where(
                'key',
                Feature::KEY_DATE_SPECIFIC_AVAILABILITY
            )
            ->update([
                'default_enabled' => false,
            ]);

        $user = User::factory()->create();

        $membership = CompanyMembership::factory()
            ->for($user)
            ->create();

        Sanctum::actingAs($user);

        $this->postJson(
            "/api/v1/company-memberships/{$membership->id}/availability-overrides",
            [
                'date' => '2026-08-01',
                'status' => AvailabilityOverride::STATUS_UNAVAILABLE,
            ]
        )->assertForbidden();
    }

    public function test_company_can_use_both_availability_features(): void
    {
        Feature::factory()->create([
            'key' => Feature::KEY_RECURRING_AVAILABILITY,
            'default_enabled' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $membership = CompanyMembership::factory()
            ->for($user)
            ->create();

        Sanctum::actingAs($user);

        $this->postJson(
            "/api/v1/company-memberships/{$membership->id}/availability-rules",
            [
                'weekday' => 1,
                'start_time' => '08:00',
                'end_time' => '16:00',
                'status' => AvailabilityRule::STATUS_AVAILABLE,
                'timezone' => 'Europe/Vienna',
                'is_active' => true,
            ]
        )->assertCreated();

        $this->postJson(
            "/api/v1/company-memberships/{$membership->id}/availability-overrides",
            [
                'date' => '2026-08-01',
                'status' => AvailabilityOverride::STATUS_UNAVAILABLE,
                'timezone' => 'Europe/Vienna',
            ]
        )->assertCreated();

        $this->assertDatabaseHas('availability_rules', [
            'company_membership_id' => $membership->id,
        ]);

        $this->assertDatabaseHas('availability_overrides', [
            'company_membership_id' => $membership->id,
            'date' => '2026-08-01',
        ]);
    }

    private function validTimedPayload(): array
    {
        return [
            'date' => '2026-08-01',
            'start_time' => '09:00',
            'end_time' => '13:00',
            'status' => AvailabilityOverride::STATUS_AVAILABLE,
            'timezone' => 'Europe/Vienna',
            'note' => 'Available before afternoon',
        ];
    }
}
