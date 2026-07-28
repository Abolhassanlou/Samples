<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('limit_type'); // max_workers | max_dispatchers | max_admins | max_branches
            $table->unsignedInteger('max_value')->nullable(); // null = unlimited
            $table->string('enforcement_mode')->default('soft_warning'); // hard_block | soft_warning
            $table->timestamps();

            $table->unique(['plan_id', 'limit_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_limits');
    }
};
