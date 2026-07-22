<?php

namespace Tests\Feature\Services;

use App\Models\AvailabilityOverride;
use App\Models\AvailabilityRule;
use App\Models\CompanyMembership;
use App\Services\AvailabilityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityResolverTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(AvailabilityResolver::class);
    }

    public function test_status_is_unknown_when_no_availability_exists(): void
    {
        $membership = CompanyMembership::factory()->create();

        $result = $this->resolver->resolve(
            $membership,
            '2026-07-20',
            '09:00',
            '12:00'
        );

        $this->assertSame(
            AvailabilityResolver::STATUS_UNKNOWN,
            $result
        );
    }

    public function test_weekly_rule_can_make_employee_available(): void
    {
        $membership = CompanyMembership::factory()->create();

        AvailabilityRule::factory()
            ->for($membership)
            ->create([
                'weekday' => 1,
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'status' => AvailabilityRule::STATUS_AVAILABLE,
                'valid_from' => '2026-07-01',
                'valid_until' => '2026-07-31',
            ]);

        $result = $this->resolver->resolve(
            $membership,
            '2026-07-20', // Monday
            '09:00',
            '12:00'
        );

        $this->assertSame(
            AvailabilityResolver::STATUS_AVAILABLE,
            $result
        );
    }

    public function test_preferred_weekly_rule_is_preserved(): void
    {
        $membership = CompanyMembership::factory()->create();

        AvailabilityRule::factory()
            ->for($membership)
            ->preferred()
            ->create([
                'weekday' => 1,
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'valid_from' => null,
                'valid_until' => null,
            ]);

        $result = $this->resolver->resolve(
            $membership,
            '2026-07-20',
            '10:00',
            '12:00'
        );

        $this->assertSame(
            AvailabilityResolver::STATUS_PREFERRED,
            $result
        );
    }

    public function test_rule_for_different_weekday_is_ignored(): void
    {
        $membership = CompanyMembership::factory()->create();

        AvailabilityRule::factory()
            ->for($membership)
            ->create([
                'weekday' => 2,
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'valid_from' => null,
                'valid_until' => null,
            ]);

        $result = $this->resolver->resolve(
            $membership,
            '2026-07-20', // Monday
            '09:00',
            '12:00'
        );

        $this->assertSame(
            AvailabilityResolver::STATUS_UNKNOWN,
            $result
        );
    }

    public function test_weekly_rule_can_partially_cover_shift(): void
    {
        $membership = CompanyMembership::factory()->create();

        AvailabilityRule::factory()
            ->for($membership)
            ->create([
                'weekday' => 1,
                'start_time' => '08:00:00',
                'end_time' => '11:00:00',
                'valid_from' => null,
                'valid_until' => null,
            ]);

        $result = $this->resolver->resolve(
            $membership,
            '2026-07-20',
            '09:00',
            '13:00'
        );

        $this->assertSame(
            AvailabilityResolver::STATUS_PARTIALLY_AVAILABLE,
            $result
        );
    }

    public function test_whole_day_override_has_priority_over_weekly_rule(): void
    {
        $membership = CompanyMembership::factory()->create();

        AvailabilityRule::factory()
            ->for($membership)
            ->create([
                'weekday' => 1,
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'valid_from' => null,
                'valid_until' => null,
            ]);

        AvailabilityOverride::factory()
            ->for($membership)
            ->wholeDayUnavailable()
            ->create([
                'date' => '2026-07-20',
            ]);

        $result = $this->resolver->resolve(
            $membership,
            '2026-07-20',
            '09:00',
            '12:00'
        );

        $this->assertSame(
            AvailabilityResolver::STATUS_UNAVAILABLE,
            $result
        );
    }

    public function test_date_override_can_make_employee_available(): void
    {
        $membership = CompanyMembership::factory()->create();

        AvailabilityOverride::factory()
            ->for($membership)
            ->create([
                'date' => '2026-07-20',
                'start_time' => '10:00:00',
                'end_time' => '18:00:00',
                'status' => AvailabilityOverride::STATUS_AVAILABLE,
            ]);

        $result = $this->resolver->resolve(
            $membership,
            '2026-07-20',
            '12:00',
            '16:00'
        );

        $this->assertSame(
            AvailabilityResolver::STATUS_AVAILABLE,
            $result
        );
    }

    public function test_date_override_can_partially_cover_shift(): void
    {
        $membership = CompanyMembership::factory()->create();

        AvailabilityOverride::factory()
            ->for($membership)
            ->create([
                'date' => '2026-07-20',
                'start_time' => '10:00:00',
                'end_time' => '13:00:00',
                'status' => AvailabilityOverride::STATUS_AVAILABLE,
            ]);

        $result = $this->resolver->resolve(
            $membership,
            '2026-07-20',
            '12:00',
            '16:00'
        );

        $this->assertSame(
            AvailabilityResolver::STATUS_PARTIALLY_AVAILABLE,
            $result
        );
    }

    public function test_explicit_date_schedule_returns_unavailable_outside_its_ranges(): void
    {
        $membership = CompanyMembership::factory()->create();

        AvailabilityOverride::factory()
            ->for($membership)
            ->create([
                'date' => '2026-07-20',
                'start_time' => '08:00:00',
                'end_time' => '10:00:00',
                'status' => AvailabilityOverride::STATUS_AVAILABLE,
            ]);

        $result = $this->resolver->resolve(
            $membership,
            '2026-07-20',
            '14:00',
            '16:00'
        );

        $this->assertSame(
            AvailabilityResolver::STATUS_UNAVAILABLE,
            $result
        );
    }
}