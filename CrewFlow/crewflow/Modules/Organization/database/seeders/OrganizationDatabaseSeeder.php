<?php

namespace Modules\Organization\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Organization\Models\Branch;

/**
 * Creates the default "Main" branch every new company starts with. This is
 * what lets a single-location company work without ever having to think
 * about branches — and lets them add more later without any migration.
 */
class OrganizationDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Branch::firstOrCreate(
            ['is_main' => true],
            ['name' => 'Main', 'is_active' => true]
        );
    }
}
