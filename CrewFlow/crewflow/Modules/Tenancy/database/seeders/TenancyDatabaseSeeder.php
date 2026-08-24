<?php

namespace Modules\Tenancy\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Tenancy\Models\Plan;

/**
 * A starting set of plans, run once against the Central database with:
 *   php artisan db:seed --class="Modules\Tenancy\Database\Seeders\TenancyDatabaseSeeder"
 * Feel free to edit prices/limits — these are just a reasonable starting point.
 *
 * Note: this used to also seed platform-level roles/permissions
 * (guard=central). That's gone — PlatformUser and all platform-admin RBAC
 * now live entirely in the separate Platform project, not here.
 */
class TenancyDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPlans();
    }

    private function seedPlans(): void
    {
        $demo = Plan::firstOrCreate(
            ['name' => 'Demo'],
            ['price' => 0, 'billing_cycle' => 'monthly']
        );
        $demo->limits()->firstOrCreate(
            ['limit_type' => 'max_workers'],
            ['max_value' => 5, 'enforcement_mode' => 'soft_warning']
        );
        $demo->limits()->firstOrCreate(
            ['limit_type' => 'max_branches'],
            ['max_value' => 1, 'enforcement_mode' => 'soft_warning']
        );

        $starter = Plan::firstOrCreate(
            ['name' => 'Starter'],
            ['price' => 29, 'billing_cycle' => 'monthly']
        );
        $starter->limits()->firstOrCreate(
            ['limit_type' => 'max_workers'],
            ['max_value' => 50, 'enforcement_mode' => 'hard_block']
        );
        $starter->limits()->firstOrCreate(
            ['limit_type' => 'max_branches'],
            ['max_value' => 3, 'enforcement_mode' => 'soft_warning']
        );

        Plan::firstOrCreate(
            ['name' => 'Enterprise'],
            ['price' => 199, 'billing_cycle' => 'monthly']
        );
        // Enterprise deliberately has no limit rows at all -> unlimited everything.
    }
}
