<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_group_passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['transport_group_id', 'assignment_id'], 'transport_group_passengers_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_group_passengers');
    }
};
