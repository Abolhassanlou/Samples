<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_qualifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('qualification_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['shift_id', 'qualification_id'], 'shift_qualifications_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_qualifications');
    }
};
