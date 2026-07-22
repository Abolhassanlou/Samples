<?php

namespace Tests\Feature\Models;

use App\Models\AvailabilityOverride;
use App\Models\AvailabilityRule;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_membership_has_weekly_availability_rules(): void
    {
        $membership = CompanyMembership::factory()->create();

        AvailabilityRule::factory()
            ->count(2)
            ->for($membership)
            ->create();

        $this->assertCount(
            2,
            $membership->availabilityRules
        );
    }

    public function test_membership_has_date_specific_overrides(): void
    {
        $membership = CompanyMembership::factory()->create();

        AvailabilityOverride::factory()
            ->count(2)
            ->for($membership)
            ->create();

        $this->assertCount(
            2,
            $membership->availabilityOverrides
        );
    }

    public function test_availability_rule_casts_values_correctly(): void
    {
        $rule = AvailabilityRule::factory()->create([
            'weekday' => 1,
            'valid_from' => '2026-07-01',
            'valid_until' => '2026-09-30',
            'is_active' => true,
        ]);

        $this->assertSame(1, $rule->weekday);
        $this->assertTrue($rule->is_active);
        $this->assertSame(
            '2026-07-01',
            $rule->valid_from->toDateString()
        );
        $this->assertSame(
            '2026-09-30',
            $rule->valid_until->toDateString()
        );
    }

    public function test_only_active_rules_effective_on_date_are_returned(): void
    {
        $membership = CompanyMembership::factory()->create();

        $validRule = AvailabilityRule::factory()
            ->for($membership)
            ->create([
                'valid_from' => '2026-07-01',
                'valid_until' => '2026-07-31',
                'is_active' => true,
            ]);

        AvailabilityRule::factory()
            ->for($membership)
            ->inactive()
            ->create([
                'valid_from' => '2026-07-01',
                'valid_until' => '2026-07-31',
            ]);

        AvailabilityRule::factory()
            ->for($membership)
            ->create([
                'valid_from' => '2026-08-01',
                'valid_until' => '2026-08-31',
                'is_active' => true,
            ]);

        AvailabilityRule::factory()
            ->for($membership)
            ->create([
                'valid_from' => '2026-06-01',
                'valid_until' => '2026-06-30',
                'is_active' => true,
            ]);

        $rules = $membership
            ->availabilityRules()
            ->active()
            ->effectiveOn('2026-07-15')
            ->get();

        $this->assertCount(1, $rules);
        $this->assertTrue($rules->first()->is($validRule));
    }

    public function test_rule_without_validity_boundaries_is_effective(): void
    {
        $rule = AvailabilityRule::factory()->create([
            'valid_from' => null,
            'valid_until' => null,
            'is_active' => true,
        ]);

        $rules = AvailabilityRule::query()
            ->active()
            ->effectiveOn('2030-01-01')
            ->get();

        $this->assertTrue($rules->contains($rule));
    }

    public function test_override_can_be_found_for_specific_date(): void
    {
        $membership = CompanyMembership::factory()->create();

        $expectedOverride = AvailabilityOverride::factory()
            ->for($membership)
            ->create([
                'date' => '2026-07-25',
            ]);

        AvailabilityOverride::factory()
            ->for($membership)
            ->create([
                'date' => '2026-07-26',
            ]);

        $overrides = $membership
            ->availabilityOverrides()
            ->forDate('2026-07-25')
            ->get();

        $this->assertCount(1, $overrides);
        $this->assertTrue(
            $overrides->first()->is($expectedOverride)
        );
    }

    public function test_override_can_apply_to_whole_day(): void
    {
        $override = AvailabilityOverride::factory()
            ->wholeDayUnavailable()
            ->create();

        $this->assertTrue($override->appliesToWholeDay());
        $this->assertSame(
            AvailabilityOverride::STATUS_UNAVAILABLE,
            $override->status
        );
    }

    public function test_deleting_membership_deletes_its_availability(): void
    {
        $membership = CompanyMembership::factory()->create();

        $rule = AvailabilityRule::factory()
            ->for($membership)
            ->create();

        $override = AvailabilityOverride::factory()
            ->for($membership)
            ->create();

        $membership->delete();

        $this->assertDatabaseMissing('availability_rules', [
            'id' => $rule->id,
        ]);

        $this->assertDatabaseMissing('availability_overrides', [
            'id' => $override->id,
        ]);
    }

    public function test_company_only_accesses_availability_through_its_membership(): void
    {
        $user = User::factory()->create();

        $firstCompany = Company::factory()->create();
        $secondCompany = Company::factory()->create();

        $firstMembership = CompanyMembership::factory()
            ->for($user)
            ->for($firstCompany)
            ->create();

        $secondMembership = CompanyMembership::factory()
            ->for($user)
            ->for($secondCompany)
            ->create();

        $firstRule = AvailabilityRule::factory()
            ->for($firstMembership)
            ->create();

        $secondRule = AvailabilityRule::factory()
            ->for($secondMembership)
            ->create();

        $this->assertTrue(
            $firstMembership->availabilityRules->contains($firstRule)
        );

        $this->assertFalse(
            $firstMembership->availabilityRules->contains($secondRule)
        );

        $this->assertTrue(
            $secondMembership->availabilityRules->contains($secondRule)
        );

        $this->assertFalse(
            $secondMembership->availabilityRules->contains($firstRule)
        );
    }
}