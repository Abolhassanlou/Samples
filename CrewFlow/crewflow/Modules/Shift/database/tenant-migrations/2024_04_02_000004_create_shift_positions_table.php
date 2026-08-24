<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_role_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity_needed')->default(1);
            $table->decimal('hourly_rate', 10, 2)->nullable(); // overrides the parent Shift's rate for this position, if set
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_positions');
    }
};
