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
            'employee_qualifications',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('company_membership_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('qualification_id')
                    ->constrained()
                    ->cascadeOnDelete();

                // beginner, intermediate, advanced, expert
                // or A1, A2, B1, B2, C1, C2
                $table->string('level', 50)->nullable();

                // pending, verified, rejected, expired
                $table->string('status', 30)
                    ->default('pending');

                $table->date('issued_at')->nullable();
                $table->date('expires_at')->nullable();

                $table->foreignId('verified_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp('verified_at')->nullable();

                $table->text('notes')->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'company_membership_id',
                        'qualification_id',
                    ],
                    'employee_qualification_unique'
                );

                $table->index(
                    [
                        'qualification_id',
                        'status',
                        'expires_at',
                    ],
                    'employee_qualifications_lookup_index'
                );
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_qualifications');
    }
};
