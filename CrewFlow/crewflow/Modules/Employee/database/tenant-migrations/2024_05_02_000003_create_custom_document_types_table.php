<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_document_types', function (Blueprint $table) {
            $table->id();
            // personal: identity-type docs shown under My info | work:
            // job/event-related uploads shown under the top-level
            // Documents section — matches the fixed baseline's own split.
            $table->string('category')->default('work');
            $table->string('key')->unique();
            $table->string('label');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_document_types');
    }
};
