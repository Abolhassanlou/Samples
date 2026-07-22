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
        Schema::create('qualifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            // skill, subject, language, certificate, training
            $table->string('type', 30)->default('skill');

            $table->string('code', 50)->nullable();
            $table->text('description')->nullable();

            $table->boolean('requires_verification')
                ->default(false);

            $table->boolean('requires_expiry_date')
                ->default(false);

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
            ], 'qualifications_lookup_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qualifications');
    }
};
