<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a single ChatMessage row be identified as "this came from a
 * broadcast" (vs. an ordinary message either side typed directly) — not
 * knowable before this, since broadcast() created messages identically
 * to sendMessage(). Needed for the 30-day rolling archive: only
 * broadcast-origin messages age out of a worker's default thread view,
 * never anything typed directly by either party — see
 * ChatController::messages()'s docblock.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->foreignId('broadcast_id')->nullable()->after('sender_id')
                ->constrained('chat_broadcasts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('broadcast_id');
        });
    }
};
