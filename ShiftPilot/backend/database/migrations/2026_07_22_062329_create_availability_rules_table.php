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
    Schema::create('availability_rules', function (Blueprint $table) {
        $table->id();

        $table->foreignId('company_membership_id')
            ->constrained()
            ->cascadeOnDelete();

        // 1 = Monday, 7 = Sunday
        $table->unsignedTinyInteger('weekday');

        $table->time('start_time');
        $table->time('end_time');

        // available, preferred, unavailable
        $table->string('status', 30)->default('available');

        $table->date('valid_from')->nullable();
        $table->date('valid_until')->nullable();

        $table->string('timezone', 64)->default('Europe/Vienna');
        $table->boolean('is_active')->default(true);

        $table->timestamps();

        $table->index([
            'company_membership_id',
            'weekday',
            'is_active',
        ], 'availability_rules_lookup_index');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availability_rules');
    }
};
