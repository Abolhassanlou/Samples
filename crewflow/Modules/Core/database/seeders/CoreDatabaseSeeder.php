<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Default roles and permissions for every new company (tenant).
 *
 * Note: per the business model, these are only the initial seed —
 * each Company Admin can later create additional custom roles.
 * Only the three roles below (is_system = true) cannot be deleted.
 */
class CoreDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'shifts.create',
            'shifts.dispatch',
            'shifts.view',
            'documents.review',
            'cancellations.process',
            'settings.manage',
            'branches.manage',
            'clients.manage',
            'qualifications.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        $admin = Role::firstOrCreate(
            ['name' => 'Company Admin', 'guard_name' => 'api'],
            ['is_system' => true]
        );
        $admin->syncPermissions($permissions);

        $dispatcher = Role::firstOrCreate(
            ['name' => 'Dispatcher', 'guard_name' => 'api'],
            ['is_system' => true]
        );
        $dispatcher->syncPermissions([
            'shifts.create', 'shifts.dispatch', 'shifts.view', 'cancellations.process',
        ]);

        Role::firstOrCreate(
            ['name' => 'Worker', 'guard_name' => 'api'],
            ['is_system' => true]
        );
        // Workers have no management permissions by default; their access
        // (expressing interest, confirming, uploading documents) is controlled
        // by endpoint-level logic, not by general permissions.
    }
}
