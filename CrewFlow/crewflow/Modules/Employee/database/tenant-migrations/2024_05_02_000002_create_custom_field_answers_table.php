<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_field_definition_id')->constrained()->cascadeOnDelete();
            // worker_id points at users.id directly — same convention as
            // WorkerQualification/WorkerAvailability/WorkerDocument
            // throughout this module, not at workers.id.
            $table->foreignId('worker_id')->constrained('users')->cascadeOnDelete();

            // Everything stored as text regardless of field_type — a
            // boolean becomes "true"/"false", a number becomes its
            // string form, a select becomes the chosen option string.
            // Simpler than a differently-typed column per field_type,
            // and the frontend already knows how to parse/render each
            // type from the field definition.
            $table->text('value')->nullable();

            $table->timestamps();

            $table->unique(['custom_field_definition_id', 'worker_id'], 'custom_field_answers_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_answers');
    }
};
