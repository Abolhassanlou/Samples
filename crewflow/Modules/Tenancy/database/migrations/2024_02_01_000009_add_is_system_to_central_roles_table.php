<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors Modules/Core/database/tenant-migrations/..._add_is_system_to_roles_table.php,
 * but applied to the CENTRAL copy of the `roles` table (created by the
 * spatie/laravel-permission migration you must publish separately — see
 * this module's README). Needed because PlatformUser reuses Core's exact
 * Role model class, which expects an `is_system` column to exist wherever
 * its underlying table lives (Central or any tenant database).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('guard_name');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
