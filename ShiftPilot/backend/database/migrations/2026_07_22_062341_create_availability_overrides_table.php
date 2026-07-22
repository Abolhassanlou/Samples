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
        Schema::create('availability_overrides', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_membership_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('date');

            // Null times mean the status applies to the whole day.
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            // available, preferred, unavailable
            $table->string('status', 30);

            $table->string('timezone', 64)->default('Europe/Vienna');
            $table->string('note', 500)->nullable();

            $table->timestamps();

            $table->index([
                'company_membership_id',
                'date',
            ], 'availability_overrides_lookup_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availability_overrides');
    }
};
