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
        Schema::create(
            'company_membership_locations',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('company_membership_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('company_location_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->timestamps();

                $table->unique(
                    [
                        'company_membership_id',
                        'company_location_id',
                    ],
                    'membership_location_unique'
                );

                $table->index(
                    [
                        'company_location_id',
                        'company_membership_id',
                    ],
                    'location_membership_lookup_index'
                );
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_membership_locations');
    }
};
