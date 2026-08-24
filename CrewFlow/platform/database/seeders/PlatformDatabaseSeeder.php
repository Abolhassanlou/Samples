<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PlatformDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'companies.view',
            'companies.manage', // suspend/unsuspend
            'plans.manage',
            'subscriptions.manage',
            'users.manage', // platform users themselves + role assignment
            'roles.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'api']);
        $superAdmin->syncPermissions($permissions);

        $supportAgent = Role::firstOrCreate(['name' => 'Support Agent', 'guard_name' => 'api']);
        // Support Agent is deliberately view-only — no manage/suspend/billing power.
        $supportAgent->syncPermissions(['companies.view']);
    }
}
