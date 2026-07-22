<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'company_features',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('company_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('feature_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->boolean('is_enabled')
                    ->default(true);

                $table->json('configuration')
                    ->nullable();

                $table->timestamp('enabled_at')
                    ->nullable();

                $table->timestamp('expires_at')
                    ->nullable();

                $table->foreignId('enabled_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->unique([
                    'company_id',
                    'feature_id',
                ]);

                $table->index([
                    'company_id',
                    'is_enabled',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('company_features');
    }
};