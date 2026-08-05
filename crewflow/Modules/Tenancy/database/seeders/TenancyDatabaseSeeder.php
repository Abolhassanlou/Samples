<?php

namespace Modules\Tenancy\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\Role;
use Modules\Tenancy\Models\Plan;
use Spatie\Permission\Models\Permission;

/**
 * A starting set of plans AND platform-level roles/permissions, run once
 * against the Central database with:
 *   php artisan db:seed --class="Modules\Tenancy\Database\Seeders\TenancyDatabaseSeeder"
 * Feel free to edit prices/limits — these are just a reasonable starting point.
 */
class TenancyDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPlans();
        $this->seedPlatformRoles();
    }

    private function seedPlatformRoles(): void
    {
        $permissions = [
            'platform.companies.view',
            'platform.companies.manage', // suspend/unsuspend
            'platform.plans.manage',
            'platform.subscriptions.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'central']);
        }

        $superAdmin = Role::firstOrCreate(
            ['name' => 'Super Admin', 'guard_name' => 'central'],
            ['is_system' => true]
        );
        $superAdmin->syncPermissions($permissions);

        $supportAgent = Role::firstOrCreate(
            ['name' => 'Support Agent', 'guard_name' => 'central'],
            ['is_system' => true]
        );
        // Support Agent is deliberately view-only — no manage/suspend/billing power.
        $supportAgent->syncPermissions(['platform.companies.view']);
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
