<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            // Keyed by email (not user_id) — matches Laravel's own
            // convention for this table, and lets a lookup happen before
            // confirming the email even belongs to a real user (the
            // "forgot password" endpoint never reveals whether an email
            // exists either way).
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
