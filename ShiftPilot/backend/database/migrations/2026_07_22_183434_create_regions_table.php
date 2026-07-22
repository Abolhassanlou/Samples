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
        Schema::create('regions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('regions')
                ->nullOnDelete();

            $table->string('name');

            // city, district, state, country, custom
            $table->string('type', 30)->default('city');

            $table->string('code', 50)->nullable();
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
            ], 'regions_lookup_index');

            $table->index([
                'company_id',
                'parent_id',
            ], 'regions_parent_lookup_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
