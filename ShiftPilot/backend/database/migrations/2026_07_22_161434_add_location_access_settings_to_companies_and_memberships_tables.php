<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // branch_only, company_wide
            $table->string(
                'workforce_visibility_policy',
                30
            )->default('branch_only');
        });

        Schema::table(
            'company_memberships',
            function (Blueprint $table) {
                $table->foreignId('primary_company_location_id')
                    ->nullable()
                    ->constrained('company_locations')
                    ->nullOnDelete();

                $table->boolean('access_all_locations')
                    ->default(false);

                $table->boolean('all_regions')
                    ->default(false);
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(
            'company_memberships',
            function (Blueprint $table) {
                $table->dropConstrainedForeignId(
                    'primary_company_location_id'
                );

                $table->dropColumn([
                    'access_all_locations',
                    'all_regions',
                ]);
            }
        );

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(
                'workforce_visibility_policy'
            );
        });
    }
};
