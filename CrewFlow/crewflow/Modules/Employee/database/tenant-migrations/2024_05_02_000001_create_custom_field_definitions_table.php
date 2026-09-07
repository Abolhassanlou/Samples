<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_definitions', function (Blueprint $table) {
            $table->id();

            // personal_info | skill — which accordion section on the
            // worker's profile this question appears under.
            $table->string('category');

            // Stable identifier, e.g. "shoe_size" — never shown to
            // anyone, just a durable reference (renaming the label
            // doesn't orphan existing answers).
            $table->string('key')->unique();

            $table->string('label');

            // text | number | boolean | select | date — determines which
            // input widget the frontend renders.
            $table->string('field_type');

            // Only used when field_type = "select" — a JSON array of
            // option strings, e.g. ["S","M","L","XL"].
            $table->json('options')->nullable();

            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            // Soft-disable rather than delete — keeps existing workers'
            // answers intact even if a company stops asking a question.
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_definitions');
    }
};
