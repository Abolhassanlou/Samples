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
        Schema::create('employee_regions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_membership_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('region_id')
                ->constrained()
                ->cascadeOnDelete();

            // pending, approved, rejected
            $table->string('status', 30)
                ->default('approved');

            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                [
                    'company_membership_id',
                    'region_id',
                ],
                'employee_region_unique'
            );

            $table->index(
                [
                    'region_id',
                    'status',
                    'is_active',
                ],
                'employee_regions_lookup_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_regions');
    }
};
