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
        Schema::create('company_locations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            // branch, office, department
            $table->string('type', 30)->default('branch');

            $table->string('code', 50)->nullable();

            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();

            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('postal_code', 30)->nullable();
            $table->string('city')->nullable();
            $table->string('country_code', 2)->nullable();

            $table->string('timezone', 64)
                ->default('Europe/Vienna');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique([
                'company_id',
                'code',
            ]);

            $table->index([
                'company_id',
                'type',
                'is_active',
            ], 'company_locations_lookup_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_locations');
    }
};
