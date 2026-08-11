<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained('users')->cascadeOnDelete();

            $table->dateTime('expressed_at');
            $table->dateTime('withdrawn_at')->nullable();
            $table->string('status')->default('pending'); // pending | converted | withdrawn

            $table->timestamps();

            $table->unique(['shift_id', 'worker_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_interests');
    }
};
